<?php
/**
 * Rank Math SEO integration. Rank Math (free) is installed to give blog
 * posts (`post`) the SEO Title / Meta Description / Focus Keyword meta box
 * this theme has no equivalent for — inc/seo.php's tvr_current_page_seo()
 * only ever handled per-page-type SEO (front page, tv_brand, page
 * templates), not per-post.
 *
 * Left unconfigured, Rank Math emits its own title/description/canonical/
 * OG/Twitter tags on every public post type, which would duplicate the
 * hand-rolled tags inc/seo.php already prints for the front page, tv_brand,
 * and every ACF-driven page template. Scoping it to `post` only is done via
 * Rank Math's own frontend/* filters rather than unhooking anything:
 * everything Rank Math prints (title, description, canonical, OpenGraph,
 * Twitter) is read from one memoized "Paper" value per request
 * (includes/frontend/paper/class-paper.php), and each consumer silently
 * skips its own output the moment that value is empty — so forcing it
 * empty here is enough to fully silence Rank Math's front end everywhere
 * except real blog posts, without touching inc/seo.php at all.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Activating/configuring Rank Math (activate_plugin(), the
 * rank_math_registration_skip + rank_math_modules options — see
 * scripts/setup-rank-math.php) is DB state, not code — pushing this
 * theme's git history to a deploy does nothing to a live site's database.
 * Production found this out the hard way: the plugin's files arrived via
 * deploy, but nothing ever ran the one-time setup against production's own
 * DB, so Rank Math sat inactive/unconfigured and every blog-post SEO field
 * (Title/Meta Description/Focus Keyword) silently never got filled in.
 * This gives any admin a one-click fix from wp-admin itself — no
 * WP-CLI/SSH access to the server required, same reasoning as the
 * Import-from-Folder upload button (Posts -> Import from Folder) needing
 * to work without a terminal.
 */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( ! function_exists( 'is_plugin_active' ) ) require_once ABSPATH . 'wp-admin/includes/plugin.php';

	$plugin_file = 'seo-by-rank-math/rank-math.php';
	if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) return; // not deployed here yet

	$active     = is_plugin_active( $plugin_file );
	$skip_set   = (bool) get_option( 'rank_math_registration_skip' );
	$modules    = get_option( 'rank_math_modules' );
	$modules_ok = is_array( $modules ) && in_array( 'seo-analysis', $modules, true );

	if ( $active && $skip_set && $modules_ok ) return; // already fully set up

	$url = wp_nonce_url( admin_url( 'admin-post.php?action=tvr_setup_rank_math' ), 'tvr_setup_rank_math' );
	echo '<div class="notice notice-warning"><p><strong>Rank Math SEO</strong> needs a one-time setup on this site before blog-post SEO fields (Title/Meta Description/Focus Keyword) will work — this is separate per environment (local/staging/production each have their own database). <a href="' . esc_url( $url ) . '" class="button button-primary">Activate &amp; Configure Rank Math</a></p></div>';
} );

add_action( 'admin_post_tvr_setup_rank_math', function () {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Not allowed.' );
	check_admin_referer( 'tvr_setup_rank_math' );

	if ( ! function_exists( 'activate_plugin' ) ) require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$plugin_file = 'seo-by-rank-math/rank-math.php';
	if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) && ! is_plugin_active( $plugin_file ) ) {
		activate_plugin( $plugin_file );
	}
	// Same two settings as scripts/setup-rank-math.php's CLI version —
	// keep both in sync if either ever changes.
	update_option( 'rank_math_registration_skip', true );
	update_option( 'rank_math_modules', array( 'seo-analysis', 'rich-snippet' ) );

	wp_safe_redirect( add_query_arg( 'tvr_rank_math_setup', '1', wp_get_referer() ?: admin_url() ) );
	exit;
} );

add_action( 'admin_notices', function () {
	if ( isset( $_GET['tvr_rank_math_setup'] ) && current_user_can( 'manage_options' ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Rank Math SEO activated and configured.</p></div>';
	}
} );

if ( ! defined( 'RANK_MATH_VERSION' ) ) return;

add_filter( 'rank_math/frontend/title', 'tvr_rank_math_scope_to_posts' );
add_filter( 'rank_math/frontend/description', 'tvr_rank_math_scope_to_posts' );
add_filter( 'rank_math/frontend/canonical', 'tvr_rank_math_scope_to_posts' );
function tvr_rank_math_scope_to_posts( $value ) {
	return is_singular( 'post' ) ? $value : '';
}

// Keep the SEO meta box + on-page analysis out of Pages/CPT edit screens —
// those are fully covered by inc/seo.php + ACF, so the box would just be a
// second, confusing, unused SEO UI there.
add_filter( 'rank_math/excluded_post_types', function ( $post_types ) {
	return array( 'post' );
} );
