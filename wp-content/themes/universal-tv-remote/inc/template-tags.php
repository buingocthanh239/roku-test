<?php
/**
 * Template helpers: letter-avatar fallback (port of ServiceIcon.jsx), cached
 * full-directory queries (the 1202-post archive/taxonomy pages can't use the
 * paginated main query), category label lookups, and the deterministic
 * "related TVs" picker (port of lib/services.js relatedServices()).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function tvr_asset( $path ) {
	return get_template_directory_uri() . '/assets/images/' . ltrim( $path, '/' );
}

// Ordered list of all published posts of a "content block" CPT (Home
// features/reviews/comparison rows, FAQ items, troubleshooting content,
// download reasons, guides — see inc/content-cpts.php) — small collections,
// no pagination/caching needed.
function tvr_get_content_posts( $post_type, $args = array() ) {
	return get_posts( array_merge( array(
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	), $args ) );
}

// One-per-line textarea field (ACF `causes`/`solutions`/`steps`/`marquee_brands`
// fields) -> a clean array, blank lines and stray whitespace dropped.
function tvr_lines( $text ) {
	if ( ! $text ) return array();
	return array_values( array_filter( array_map( 'trim', explode( "\n", $text ) ) ) );
}

// get_field() with a hardcoded fallback so a page renders sensibly before
// its ACF fields have been filled in (fresh install, before migration/entry).
function tvr_field( $selector, $post_id, $fallback = '' ) {
	$value = function_exists( 'get_field' ) ? get_field( $selector, $post_id ) : null;
	return ( $value === null || $value === false || $value === '' ) ? $fallback : $value;
}

// CSS declarations (no wrapping style="") for a page/post's H1, driven by its
// "heading_color" (color_picker) + "heading_size" (select) ACF fields —
// inline style always wins over the Tailwind utility classes on the tag by
// CSS specificity, so this works regardless of what's already compiled into
// style.css. Returned as bare declarations so a caller that already has its
// own style="" (e.g. front-page.php's --reveal-delay) can append it inline.
function tvr_heading_style_css( $post_id = false ) {
	$color    = get_field( 'heading_color', $post_id );
	$size     = get_field( 'heading_size', $post_id );
	$size_map = array( 'lg' => '2rem', 'xl' => '2.5rem' );
	$style    = '';
	if ( $color ) $style .= 'color:' . $color . ';';
	if ( isset( $size_map[ $size ] ) ) $style .= 'font-size:' . $size_map[ $size ] . ';';
	return $style;
}

// Full style="" attribute (including the leading space) for a callsite with
// no other inline styles to merge — see tvr_heading_style_css() above.
function tvr_heading_style_attr( $post_id = false ) {
	$style = tvr_heading_style_css( $post_id );
	return $style ? ' style="' . esc_attr( $style ) . '"' : '';
}

function tvr_stars_svg( $class = '' ) {
	$star  = '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 15l-5.2 2.6 1-5.8L1.5 7.7l5.9-.9L10 1.5z" /></svg>';
	return '<div class="flex gap-0.5 text-amber-400 ' . esc_attr( $class ) . '">' . str_repeat( $star, 5 ) . '</div>';
}

function tvr_check_svg( $on ) {
	if ( $on ) {
		return '<svg class="mx-auto h-6 w-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>';
	}
	return '<svg class="mx-auto h-5 w-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>';
}

// Reveal.jsx port: pairs with a literal `class="reveal ...more"` at the call
// site (this only emits `style`, since an element can't have two `class`
// attributes — the caller is responsible for including "reveal" itself).
// $delay is milliseconds.
function tvr_reveal_attrs( $delay = 0 ) {
	return 'style="--reveal-delay:' . (int) $delay . 'ms"';
}

// ---- Letter avatar (ServiceIcon.jsx fallback) ----

function tvr_avatar_colors() {
	return array(
		'bg-rose-100 text-rose-700',
		'bg-amber-100 text-amber-700',
		'bg-emerald-100 text-emerald-700',
		'bg-sky-100 text-sky-700',
		'bg-violet-100 text-violet-700',
		'bg-fuchsia-100 text-fuchsia-700',
		'bg-teal-100 text-teal-700',
		'bg-indigo-100 text-indigo-700',
	);
}

function tvr_letter_avatar_html( $name, $size = 40, $class = '' ) {
	$colors = tvr_avatar_colors();
	$letter = strtoupper( mb_substr( $name, 0, 1 ) );
	$seed   = ord( substr( $name, 0, 1 ) ) % count( $colors );
	return sprintf(
		'<span style="width:%1$dpx;height:%1$dpx" class="inline-flex shrink-0 items-center justify-center rounded-lg font-semibold %2$s %3$s">%4$s</span>',
		(int) $size,
		esc_attr( $colors[ $seed ] ),
		esc_attr( $class ),
		esc_html( $letter )
	);
}

// Featured image if present (with onerror JS fallback to the letter avatar),
// otherwise the letter avatar directly.
function tvr_service_icon_html( $post_id, $name, $size = 40, $class = '' ) {
	if ( has_post_thumbnail( $post_id ) ) {
		$src = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
		$fallback = esc_js( tvr_letter_avatar_html( $name, $size, $class ) );
		return sprintf(
			'<img src="%1$s" alt="%2$s logo" loading="lazy" width="%3$d" height="%3$d" style="width:%3$dpx;height:%3$dpx" onerror="this.outerHTML=\'%4$s\'" class="shrink-0 rounded-lg bg-white object-contain %5$s" />',
			esc_url( $src ),
			esc_attr( $name ),
			(int) $size,
			$fallback,
			esc_attr( $class )
		);
	}
	return tvr_letter_avatar_html( $name, $size, $class );
}

// ---- Categories (replaces the build-time categories.json tally) ----

function tvr_all_categories() {
	$cache = get_transient( 'tvr_categories' );
	if ( false !== $cache ) return $cache;

	$terms = get_terms( array( 'taxonomy' => 'tv_category', 'hide_empty' => true ) );
	$out   = array();
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$out[] = array(
				'key'   => $term->slug,
				'label' => tvr_category_label( $term->slug ),
				'count' => (int) $term->count,
			);
		}
		usort( $out, function ( $a, $b ) { return $b['count'] - $a['count']; } );
	}
	set_transient( 'tvr_categories', $out, DAY_IN_SECONDS );
	return $out;
}

function tvr_invalidate_caches() {
	delete_transient( 'tvr_categories' );
	delete_transient( 'tvr_all_brands' );
}
add_action( 'save_post_tv_brand', 'tvr_invalidate_caches' );
add_action( 'delete_post', 'tvr_invalidate_caches' );
add_action( 'edited_tv_category', 'tvr_invalidate_caches' );

// ---- Full directory list (uncached WP_Query would re-run on every hit of a
// 1200-row archive/taxonomy page — cache it as a flat array). ----

function tvr_get_all_brands() {
	$cache = get_transient( 'tvr_all_brands' );
	if ( false !== $cache ) return $cache;

	$posts = get_posts( array(
		'post_type'      => 'tv_brand',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'post_status'    => 'publish',
		'no_found_rows'  => true,
	) );

	$out = array();
	foreach ( $posts as $p ) {
		$terms = wp_get_post_terms( $p->ID, 'tv_category', array( 'fields' => 'slugs' ) );
		$out[] = array(
			'id'               => $p->ID,
			'name'             => $p->post_title,
			'domain'           => $p->post_name,
			'totp'             => get_post_meta( $p->ID, '_tv_totp', true ) === '1',
			'categories'       => $terms,
			'primary_category' => get_post_meta( $p->ID, '_tv_primary_category', true ) ?: ( $terms[0] ?? '' ),
			'parent_id'        => (int) $p->post_parent,
			'has_thumbnail'    => has_post_thumbnail( $p->ID ),
		);
	}
	set_transient( 'tvr_all_brands', $out, DAY_IN_SECONDS );
	return $out;
}

function tvr_brands_in_category( $key ) {
	return array_values( array_filter( tvr_get_all_brands(), function ( $s ) use ( $key ) {
		return in_array( $key, $s['categories'], true );
	} ) );
}

// isIndexable() port: TOTP true or it's a model page (has a parent).
function tvr_is_indexable( $service ) {
	return $service['totp'] || $service['parent_id'] > 0;
}

// Deterministic pseudo-random related-brands pick (port of relatedServices()).
function tvr_related_brands( $service, $limit = 3 ) {
	$cat = $service['primary_category'];
	if ( ! $cat ) return array();

	$pool = array_values( array_filter( tvr_get_all_brands(), function ( $s ) use ( $service, $cat ) {
		return $s['domain'] !== $service['domain'] && in_array( $cat, $s['categories'], true );
	} ) );
	if ( empty( $pool ) ) return array();

	$seed = 0;
	foreach ( str_split( $service['domain'] ) as $ch ) $seed += ord( $ch );

	$out  = array();
	$seen = array();
	for ( $i = 0; $i < $limit; $i++ ) {
		$pick = $pool[ ( $seed + $i * 37 ) % count( $pool ) ];
		if ( ! isset( $seen[ $pick['domain'] ] ) ) {
			$seen[ $pick['domain'] ] = true;
			$out[] = $pick;
		}
	}
	return $out;
}

function tvr_models_by_brand( $brand_post_id ) {
	return get_posts( array(
		'post_type'      => 'tv_brand',
		'post_parent'    => $brand_post_id,
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'post_status'    => 'publish',
	) );
}

// Direct post_name lookup — deliberately not get_page_by_path(), which only
// resolves single-segment paths for posts with post_parent === 0 and would
// silently miss every model post (see scripts/import-services.php for the
// full explanation of this WP core landmine).
function tvr_get_brand_by_domain( $domain ) {
	$posts = get_posts( array(
		'post_type'      => 'tv_brand',
		'name'           => $domain,
		'post_status'    => 'publish',
		'posts_per_page' => 1,
	) );
	return $posts ? $posts[0] : null;
}

function tvr_find_brand_by_domain( $service ) {
	foreach ( tvr_get_all_brands() as $s ) {
		if ( $s['id'] === $service['parent_id'] ) return $s;
	}
	return null;
}

function tvr_service_by_post( $post ) {
	$terms = wp_get_post_terms( $post->ID, 'tv_category', array( 'fields' => 'slugs' ) );
	return array(
		'id'               => $post->ID,
		'name'             => $post->post_title,
		'domain'           => $post->post_name,
		'model'            => get_post_meta( $post->ID, '_tv_model', true ),
		'totp'             => get_post_meta( $post->ID, '_tv_totp', true ) === '1',
		'categories'       => $terms,
		'primary_category' => get_post_meta( $post->ID, '_tv_primary_category', true ) ?: ( $terms[0] ?? '' ),
		'parent_id'        => (int) $post->post_parent,
		'has_thumbnail'    => has_post_thumbnail( $post->ID ),
	);
}
