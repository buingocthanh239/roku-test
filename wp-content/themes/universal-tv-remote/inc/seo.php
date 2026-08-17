<?php
/**
 * robots.txt (port of app/robots.js) + native WP sitemap tuning.
 * We intentionally rely on core's /wp-sitemap.xml instead of reimplementing
 * the original's custom priority/changefreq sitemap.js — WP core sitemaps
 * don't support per-URL priority (Google ignores it anyway), so there's
 * nothing meaningful to port beyond making sure tv_brand is included.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'robots_txt', function ( $output, $public ) {
	if ( ! $public ) return $output;

	$theme_assets = get_template_directory_uri() . '/assets/';
	$theme_path   = wp_parse_url( $theme_assets, PHP_URL_PATH );

	$lines = array(
		'User-agent: *',
		'Disallow: /wp-admin/',
		'Allow: /wp-admin/admin-ajax.php',
		'Disallow: /wp-includes/',
		'Disallow: /wp-content/plugins/',
		// Theme dir hides PHP templates, but CSS/JS/images (logo, screenshots,
		// icons — all real content) live under assets/ and must stay
		// crawlable, or Google can't render the page or index those images.
		'Disallow: /wp-content/themes/',
		'Allow: ' . $theme_path,
		'Disallow: /search/',
		'Disallow: /?s=',
		'Disallow: */feed',
		'Disallow: */?',
		'',
		'Sitemap: ' . home_url( '/wp-sitemap.xml' ),
	);
	return implode( "\n", $lines ) . "\n";
}, 10, 2 );

// tv_brand is public + show_in_rest, so core includes it in /wp-sitemap.xml
// automatically. Nothing else to wire up here.

/**
 * Per-page-type title/description/canonical/OG — port of the `metadata`
 * exports scattered across the original app/*.jsx pages. WP has no built-in
 * equivalent without an SEO plugin, so this is hand-rolled and kept small.
 */
function tvr_current_page_seo() {
	if ( is_front_page() ) {
		return array(
			'title'       => TVR_SITE_NAME . ' — ' . TVR_SITE_TAGLINE,
			'description' => TVR_SITE_DESCRIPTION,
			'type'        => 'website',
		);
	}
	if ( is_post_type_archive( 'tv_brand' ) ) {
		return array(
			'title'       => 'Supported TVs Directory',
			'description' => 'Browse 1000+ TV brands and models supported by Universal TV Remote. Search by brand, model or category.',
			'type'        => 'website',
		);
	}
	if ( is_tax( 'tv_category' ) ) {
		$term  = get_queried_object();
		$label = tvr_category_label( $term->slug );
		$count = $term->count;
		return array(
			'title'       => $label . ' Supported by Universal TV Remote',
			'description' => "Browse {$count} {$label} supported by Universal TV Remote. Open any one to see Wi-Fi control support and how to set it up with the app.",
			'type'        => 'website',
		);
	}
	if ( is_singular( 'tv_brand' ) ) {
		$name = get_the_title();
		return array(
			'title'       => 'Universal TV Remote Control for ' . $name,
			'description' => 'How to Use Your Phone as a Remote for ' . $name,
			'type'        => 'article',
			'image'       => tvr_asset( 'app-devices.webp' ),
		);
	}
	if ( is_page_template( 'page-templates/template-help.php' ) ) {
		return array(
			'title'       => 'FAQs',
			'description' => 'Answers to common questions about Universal TV Remote — compatibility, setup, features, Wi-Fi, voice control, privacy, and support.',
			'type'        => 'website',
		);
	}
	if ( is_page_template( 'page-templates/template-contact.php' ) ) {
		return array(
			'title'       => 'Contact Us',
			'description' => 'Spotted an outdated TV guide or a missing brand? Have a question about Universal TV Remote? Send us a message.',
			'type'        => 'website',
		);
	}
	if ( is_page_template( 'page-templates/template-troubleshooting.php' ) ) {
		return array(
			'title'       => 'Troubleshooting',
			'description' => 'Fix common Universal TV Remote issues — TV not detected, commands not working, app crashes, slow response, and advanced network configuration.',
			'type'        => 'website',
		);
	}
	if ( is_page_template( 'page-templates/template-download-app.php' ) ) {
		return array(
			'title'       => 'Download Universal TV Remote — Control Your Smart TV from iPhone',
			'description' => 'Download Universal TV Remote and turn your iPhone into a remote for Samsung, LG, Sony, Roku, TCL, and Hisense TVs. Control your TV over Wi-Fi — no extra hardware, no lost remote.',
			'type'        => 'website',
			'image'       => tvr_asset( 'og.png' ),
		);
	}
	if ( is_page_template( 'page-templates/template-guides.php' ) ) {
		return array(
			'title'       => 'Setup Guides',
			'description' => 'Step-by-step setup guides for connecting Universal TV Remote to Samsung, LG, Sony, TCL, Hisense, and Roku TVs.',
			'type'        => 'website',
		);
	}
	return array(
		'title'       => get_the_title() ?: TVR_SITE_NAME,
		'description' => TVR_SITE_DESCRIPTION,
		'type'        => 'website',
	);
}

add_filter( 'pre_get_document_title', function ( $title ) {
	// Rank Math (inc/seo-rank-math.php) owns title/meta/canonical/OG for
	// single blog posts — don't compete with it here.
	if ( is_singular( 'post' ) ) return $title;
	if ( is_404() ) return 'Page not found — ' . TVR_SITE_NAME;
	$seo = tvr_current_page_seo();
	return is_front_page() ? $seo['title'] : $seo['title'] . ' — ' . TVR_SITE_NAME;
} );

add_action( 'wp_head', function () {
	if ( is_404() ) return;

	// Site-wide Organization/WebSite JSON-LD stays on every page, including
	// blog posts — Rank Math's own per-post Article schema doesn't cover it.
	tvr_json_ld( tvr_organization_website_ld() );

	// Rank Math (inc/seo-rank-math.php) owns title/meta/canonical/OG for
	// single blog posts; printing our own here too would duplicate every tag.
	if ( is_singular( 'post' ) ) return;

	$seo = tvr_current_page_seo();
	$url = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );
	if ( is_front_page() ) $url = home_url( '/' );
	$image = $seo['image'] ?? tvr_asset( 'og.png' );

	echo "\n" . '<meta name="description" content="' . esc_attr( $seo['description'] ) . '" />' . "\n";
	echo '<link rel="canonical" href="' . esc_url( trailingslashit( $url ) ) . '" />' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $seo['type'] ) . '" />' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( TVR_SITE_NAME ) . '" />' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $seo['title'] ) . '" />' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $seo['description'] ) . '" />' . "\n";
	echo '<meta property="og:url" content="' . esc_url( trailingslashit( $url ) ) . '" />' . "\n";
	echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $seo['title'] ) . '" />' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $seo['description'] ) . '" />' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
}, 5 );
