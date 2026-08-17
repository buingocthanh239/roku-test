<?php
/**
 * One-off CLI setup: activate Rank Math SEO (free) and trim it down to a
 * minimal module set. Post-type scoping (limiting it to `post` so it
 * doesn't compete with inc/seo.php's hand-rolled SEO on Pages/tv_brand) is
 * done in inc/seo-rank-math.php via Rank Math's own frontend/* filters, not
 * here — that's the part that needs to ship with the theme, not a one-off
 * DB write.
 *
 * Assumes the plugin has already been downloaded and unzipped into
 * wp-content/plugins/seo-by-rank-math/ (from
 * https://downloads.wordpress.org/plugin/seo-by-rank-math.latest-stable.zip).
 *
 * Local dev: run with the Local site's own PHP binary + php.ini, see
 * import-services.php's header / reference-local-wp-cli project memory.
 */

if ( php_sapi_name() !== 'cli' ) {
	http_response_code( 403 );
	exit( 'CLI only.' );
}

define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 4 ) . '/wp-load.php';

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin = 'seo-by-rank-math/rank-math.php';

if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
	fwrite( STDERR, "Plugin not found at wp-content/plugins/$plugin — unzip it there first.\n" );
	exit( 1 );
}

if ( ! is_plugin_active( $plugin ) ) {
	$result = activate_plugin( $plugin );
	if ( is_wp_error( $result ) ) {
		fwrite( STDERR, 'Activation failed: ' . $result->get_error_message() . "\n" );
		exit( 1 );
	}
	echo "Activated $plugin\n";
} else {
	echo "$plugin already active\n";
}

/*
 * Rank Math gates ALL frontend output (title/description/canonical/OG —
 * everything in includes/frontend/) behind a "site registration" check
 * (\RankMath\Helpers\Conditional::is_invalid_registration()) — it's not
 * merely a dismissible admin banner, it's a real functional gate. The only
 * non-interactive way to satisfy it without connecting a Rank Math account
 * is the same option the wizard's own "Skip" button writes
 * (includes/admin/class-registration.php:skip_wizard()).
 */
update_option( 'rank_math_registration_skip', true );
echo "Set rank_math_registration_skip=true (bypasses the account-connection gate)\n";

/*
 * Minimal module set: seo-analysis (on-page checklist: keyword usage, title
 * length, etc. — the post-edit "KIỂM TRA" tab) + rich-snippet (Article
 * schema). Sitemap/Redirections/Analytics/Content AI/AI Visibility/etc. all
 * stay off — core's own /wp-sitemap.xml already covers sitemaps (see
 * inc/seo.php's robots_txt filter), and the rest either duplicates existing
 * functionality or needs a Rank Math account we don't want to require.
 */
update_option( 'rank_math_modules', array( 'seo-analysis', 'rich-snippet' ) );
echo "Set rank_math_modules to: seo-analysis, rich-snippet\n";

echo "Done.\n";
