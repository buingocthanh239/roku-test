<?php
/**
 * One-off CLI import: data/services.json (shipped inside this theme) -> tv_brand CPT.
 *
 * Two passes: brands first (no `brand` field) so their post IDs exist before
 * models (which have a `brand` field pointing at the parent's domain) are
 * inserted with post_parent set. Idempotent on post_name (domain) — safe to
 * re-run to pick up changes to the source JSON, e.g. after dropping real logo
 * files into data/icons/{icon}.png (see README in that folder / project memory
 * — no real TV icons shipped with the original source, so this is a no-op
 * until real assets are added).
 *
 * Local dev: run with the Local site's own PHP binary + php.ini (matches the
 * socket the bundled MySQL listens on):
 *   "<Local lightning-services php bin>" -c "<Local run/<site>/conf/php/php.ini>" \
 *     "wp-content/themes/universal-tv-remote/scripts/import-services.php"
 *
 * Docker: docker compose exec wordpress php wp-content/themes/universal-tv-remote/scripts/import-services.php
 */

if ( php_sapi_name() !== 'cli' ) {
	http_response_code( 403 );
	exit( 'CLI only.' );
}

define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 4 ) . '/wp-load.php';

$json_path = __DIR__ . '/../data/services.json';
$icons_dir = __DIR__ . '/../data/icons';

if ( ! file_exists( $json_path ) ) {
	fwrite( STDERR, "services.json not found at $json_path\n" );
	exit( 1 );
}

$services = json_decode( file_get_contents( $json_path ), true );
if ( ! is_array( $services ) ) {
	fwrite( STDERR, "Failed to parse services.json\n" );
	exit( 1 );
}

$brands  = array_filter( $services, function ( $s ) { return empty( $s['brand'] ); } );
$models  = array_filter( $services, function ( $s ) { return ! empty( $s['brand'] ); } );

echo 'Total: ' . count( $services ) . ' (brands: ' . count( $brands ) . ', models: ' . count( $models ) . ")\n";

function tvr_import_upsert( $entry, $post_parent = 0 ) {
	global $icons_dir;

	/*
	 * NOT get_page_by_path(): its path-matching algorithm only resolves a
	 * single-segment path when the found post's post_parent is 0 (it walks
	 * the parent chain looking for the rest of the path, and a bare
	 * single-segment input requires that walk to end immediately at a root
	 * post). Since 1100 of these entries are models with a non-zero
	 * post_parent, that function would silently fail to find them, causing
	 * wp_insert_post() to hit WordPress's slug-uniquifier and create a
	 * "-2"-suffixed duplicate on every re-run instead of updating the
	 * existing post. get_posts() with 'name' does a direct post_name match
	 * with no such requirement.
	 */
	$existing_posts = get_posts( array(
		'post_type'      => 'tv_brand',
		'name'           => $entry['domain'],
		'post_status'    => 'any',
		'posts_per_page' => 1,
	) );
	$existing = $existing_posts ? $existing_posts[0] : null;
	$post_args = array(
		'post_type'   => 'tv_brand',
		'post_status' => 'publish',
		'post_title'  => $entry['name'],
		'post_name'   => $entry['domain'],
		'post_parent' => $post_parent,
	);

	if ( $existing ) {
		$post_args['ID'] = $existing->ID;
		$post_id = wp_update_post( $post_args, true );
	} else {
		$post_id = wp_insert_post( $post_args, true );
	}

	if ( is_wp_error( $post_id ) ) {
		fwrite( STDERR, 'Failed: ' . $entry['domain'] . ' — ' . $post_id->get_error_message() . "\n" );
		return null;
	}

	update_post_meta( $post_id, '_tv_totp', ! empty( $entry['totp'] ) ? '1' : '0' );
	update_post_meta( $post_id, '_tv_primary_category', $entry['keywords'][0] ?? '' );
	if ( ! empty( $entry['model'] ) ) {
		update_post_meta( $post_id, '_tv_model', $entry['model'] );
	} else {
		delete_post_meta( $post_id, '_tv_model' );
	}

	if ( ! empty( $entry['keywords'] ) ) {
		wp_set_object_terms( $post_id, $entry['keywords'], 'tv_category', false );
	}

	// Icon sideload — no-op in this checkout (the real logo files aren't
	// present, see the conversion plan); harmless once real assets exist.
	if ( ! has_post_thumbnail( $post_id ) && ! empty( $entry['icon'] ) ) {
		$icon_path = $icons_dir . '/' . $entry['icon'] . '.png';
		if ( file_exists( $icon_path ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			$upload = wp_upload_bits( basename( $icon_path ), null, file_get_contents( $icon_path ) );
			if ( empty( $upload['error'] ) ) {
				$attachment_id = wp_insert_attachment( array(
					'post_mime_type' => 'image/png',
					'post_title'     => $entry['name'] . ' logo',
					'post_status'    => 'inherit',
				), $upload['file'], $post_id );
				wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}
	}

	return $post_id;
}

$domain_to_id = array();
$count = 0;
foreach ( $brands as $entry ) {
	$id = tvr_import_upsert( $entry, 0 );
	if ( $id ) {
		$domain_to_id[ $entry['domain'] ] = $id;
		$count++;
	}
}
echo "Imported $count brand posts.\n";

$count = 0;
$skipped = 0;
foreach ( $models as $entry ) {
	$parent_id = $domain_to_id[ $entry['brand'] ] ?? 0;
	if ( ! $parent_id ) {
		fwrite( STDERR, 'No parent found for model ' . $entry['domain'] . ' (brand: ' . $entry['brand'] . ")\n" );
		$skipped++;
		continue;
	}
	$id = tvr_import_upsert( $entry, $parent_id );
	if ( $id ) $count++;
}
echo "Imported $count model posts (" . $skipped . " skipped for missing parent).\n";

tvr_invalidate_caches();
flush_rewrite_rules();

echo "Done. wp_count_posts('tv_brand')->publish = " . wp_count_posts( 'tv_brand' )->publish . "\n";
