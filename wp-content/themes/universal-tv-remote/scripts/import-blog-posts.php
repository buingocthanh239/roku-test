<?php
/**
 * CLI entry point for the content-drops/ -> `post` (Draft) import.
 *
 * Parsing/import logic lives in inc/blog-import.php (loaded automatically
 * via wp-load.php below, same as every other inc/*.php) so this script and
 * the wp-admin "Import from Folder" page (Posts -> Import from Folder,
 * inc/admin-blog-import.php) share one implementation. Folder convention is
 * documented in content-drops-README.md.
 *
 * Local dev: run with the Local site's own PHP binary + php.ini, same as
 * import-services.php (see that file's header / reference-local-wp-cli
 * project memory):
 *   "<Local lightning-services php bin>" -c "<Local run/<site>/conf/php/php.ini>" \
 *     "wp-content/themes/universal-tv-remote/scripts/import-blog-posts.php"
 */

if ( php_sapi_name() !== 'cli' ) {
	http_response_code( 403 );
	exit( 'CLI only.' );
}

define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 4 ) . '/wp-load.php';

/*
 * WP_Query strips draft/private posts out of its results for whichever
 * user is "logged in" when the lookup targets a specific post by slug
 * (`is_singular` capability check) — with no user set (the CLI default,
 * uid 0), every draft this script looks up by slug comes back empty even
 * though the SQL genuinely matched it, so every re-run silently created a
 * new duplicate post instead of finding and updating the old one. Running
 * as a real administrator (same as the editor who'll review these Drafts
 * in wp-admin) is both the fix and the semantically correct thing to do.
 * (The wp-admin "Import from Folder" page doesn't need this — a real
 * logged-in admin is already making that request.)
 */
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
if ( empty( $admins ) ) {
	fwrite( STDERR, "No administrator user found — can't run this import.\n" );
	exit( 1 );
}
wp_set_current_user( $admins[0] );

if ( empty( tvr_blog_list_content_drop_folders() ) ) {
	echo "No folders found in content-drops/. Drop one folder per post there first — see content-drops-README.md.\n";
	exit( 0 );
}

$results = tvr_blog_import_run_all();

$created = 0;
$updated = 0;
$errors  = 0;

foreach ( $results as $r ) {
	if ( 'error' === $r['status'] ) {
		echo "[ERROR]   {$r['slug']} — {$r['message']}\n";
		$errors++;
		continue;
	}

	echo '[' . strtoupper( $r['status'] ) . "] {$r['slug']} — {$r['message']}\n";
	if ( $r['auto_placed'] ) {
		echo '          Auto-placed one per H2 section (no [image: ...] marker used): ' . implode( ', ', $r['auto_placed'] ) . "\n";
	}
	if ( $r['orphaned'] ) {
		echo '          Warning: image(s) left over (more unused images than H2 sections): ' . implode( ', ', $r['orphaned'] ) . "\n";
	}
	'created' === $r['status'] ? $created++ : $updated++;
}

echo "\nDone. Created: $created, Updated: $updated, Errors: $errors.\n";
