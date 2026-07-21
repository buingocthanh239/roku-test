<?php
/**
 * Small "content block" CPTs backing the repeating lists on the static
 * marketing pages (Home features/reviews/comparison, Help FAQ,
 * Troubleshooting, Download App reasons, Setup Guides) — same pattern as
 * `tv_brand`, but these are admin-only data (not public, no front-end URL of
 * their own): `public => false, show_ui => true`. Ordering uses core
 * page-attributes (menu_order), draggable via the standard admin list table.
 * All nest under one "Site Content" top-level admin menu.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function () {
	add_menu_page( 'Site Content', 'Site Content', 'edit_posts', 'tvr-content-hub', 'tvr_render_content_hub', 'dashicons-layout', 24 );
}, 5 );

function tvr_render_content_hub() {
	echo '<div class="wrap"><h1>Site Content</h1><p>Quản lý nội dung các khối lặp lại trên trang tĩnh (Home, Help, Troubleshooting, Download App, Guides) — chọn mục ở menu bên trái.</p></div>';
}

function tvr_register_content_cpts() {
	$common = array(
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => 'tvr-content-hub',
		'supports'     => array( 'title', 'page-attributes' ),
		'show_in_rest' => true,
	);

	register_post_type( 'home_feature', array_merge( $common, array(
		'labels' => tvr_cpt_labels( 'Home Feature', 'Home Features' ),
	) ) );

	register_post_type( 'home_review', array_merge( $common, array(
		'labels' => tvr_cpt_labels( 'Review', 'Home Reviews' ),
	) ) );

	register_post_type( 'compare_row', array_merge( $common, array(
		'labels' => tvr_cpt_labels( 'Comparison Row', 'Comparison Table Rows' ),
	) ) );

	register_post_type( 'faq_item', array_merge( $common, array(
		'labels' => tvr_cpt_labels( 'FAQ Item', 'FAQ Items' ),
	) ) );

	register_post_type( 'quick_fix', array_merge( $common, array(
		'labels' => tvr_cpt_labels( 'Quick Fix', 'Quick Fixes' ),
	) ) );

	register_post_type( 'issue', array_merge( $common, array(
		'labels' => tvr_cpt_labels( 'Issue', 'Troubleshooting Issues' ),
	) ) );

	register_post_type( 'advanced_step', array_merge( $common, array(
		'labels' => tvr_cpt_labels( 'Advanced Section', 'Advanced Troubleshooting' ),
	) ) );

	register_post_type( 'download_reason', array_merge( $common, array(
		'labels' => tvr_cpt_labels( 'Reason', 'Download Reasons' ),
	) ) );

	register_post_type( 'guide', array_merge( $common, array(
		'labels' => tvr_cpt_labels( 'Guide', 'Setup Guides' ),
	) ) );

	register_taxonomy( 'faq_category', 'faq_item', array(
		'labels'       => array( 'name' => 'FAQ Categories', 'singular_name' => 'FAQ Category' ),
		'hierarchical' => true,
		'public'       => false,
		'show_ui'      => true,
		'show_in_rest' => true,
	) );
}
add_action( 'init', 'tvr_register_content_cpts' );

function tvr_cpt_labels( $singular, $plural ) {
	return array(
		'name'          => $plural,
		'singular_name' => $singular,
		'add_new_item'  => 'Add New ' . $singular,
		'edit_item'     => 'Edit ' . $singular,
		'all_items'     => $plural,
		'search_items'  => 'Search ' . $plural,
		'not_found'     => 'None found',
		'menu_name'     => $plural,
	);
}
