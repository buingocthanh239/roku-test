<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function tvr_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails', array( 'tv_brand' ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
	add_theme_support( 'custom-logo' );

	register_nav_menus( array(
		'primary'          => 'Primary Navigation',
		'footer-company'   => 'Footer — Company',
		'footer-resources' => 'Footer — Resources',
		'footer-downloads' => 'Footer — Downloads',
	) );

	add_image_size( 'tv-icon', 64, 64, false );
}
add_action( 'after_setup_theme', 'tvr_theme_setup' );

// Renders the "primary" menu location as a flat row of <a> links (matching
// SiteHeader.jsx's design, which isn't a <ul> list) if a menu is assigned in
// Appearance → Menus, otherwise falls back to the original hardcoded nav —
// so the header works correctly with zero admin configuration.
function tvr_primary_nav() {
	$items = array();
	$menu  = has_nav_menu( 'primary' ) ? wp_get_nav_menu_items( wp_get_nav_menu_object( get_nav_menu_locations()['primary'] ) ) : null;

	if ( $menu ) {
		foreach ( $menu as $item ) {
			$items[] = array( 'url' => $item->url, 'label' => $item->title );
		}
	} else {
		$items = array(
			array( 'url' => home_url( '/services/' ), 'label' => 'Supported TVs' ),
			array( 'url' => home_url( '/troubleshooting/' ), 'label' => 'Troubleshooting' ),
			array( 'url' => home_url( '/help/' ), 'label' => 'Help' ),
			array( 'url' => home_url( '/contact/' ), 'label' => 'Contact' ),
		);
	}

	foreach ( $items as $item ) {
		$active = tvr_is_active_url( $item['url'] );
		printf(
			'<a href="%1$s" class="text-sm font-medium transition-colors %2$s">%3$s</a>',
			esc_url( $item['url'] ),
			$active ? 'text-brand-500' : 'text-slate-600 hover:text-slate-900',
			esc_html( $item['label'] )
		);
	}
}

function tvr_is_active_url( $url ) {
	$current = home_url( add_query_arg( array(), $GLOBALS['wp']->request ) ) . '/';
	$path    = trailingslashit( wp_parse_url( $url, PHP_URL_PATH ) );
	$current_path = trailingslashit( wp_parse_url( $current, PHP_URL_PATH ) );
	return $current_path === $path || strpos( $current_path, $path ) === 0;
}
