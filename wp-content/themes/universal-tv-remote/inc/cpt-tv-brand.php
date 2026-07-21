<?php
/**
 * `tv_brand` CPT + `tv_category` taxonomy.
 *
 * hierarchical is deliberately FALSE even though brand/model posts have a
 * parent/child relationship via post_parent: WordPress resolves hierarchical
 * CPT permalinks/queries through get_page_uri()/get_page_by_path(), which
 * only matches a bare single-segment path when post_parent === 0. Since ~1100
 * of the 1202 posts are models (post_parent !== 0), turning hierarchical on
 * would 404 every one of them. post_parent is a plain column — it works fine
 * for querying/relating posts without the "hierarchical post type" machinery.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function tvr_register_cpt() {
	register_post_type( 'tv_brand', array(
		'labels' => array(
			'name'               => 'TV Brands',
			'singular_name'      => 'TV Brand',
			'add_new_item'       => 'Add New TV Brand',
			'edit_item'          => 'Edit TV Brand',
			'all_items'          => 'All TV Brands',
			'search_items'       => 'Search TV Brands',
			'not_found'          => 'No TV brands found',
		),
		'public'        => true,
		'hierarchical'  => false,
		'supports'      => array( 'title', 'thumbnail' ),
		'menu_icon'     => 'dashicons-desktop',
		'has_archive'   => 'services',
		'rewrite'       => array( 'slug' => 'services', 'with_front' => false ),
		'show_in_rest'  => true,
	) );

	/*
	 * rewrite => false: any CPT registered with a rewrite slug gets an
	 * automatic "attachment shorthand" rule of the shape
	 * {slug}/([^/]+)/([^/]+)/?$ (WP_Rewrite::generate_rewrite_rules(), the
	 * unconditional $sub1 block for any non-hierarchical post type struct).
	 * That pattern is positionally identical to our desired
	 * services/category/{key}/ taxonomy URL and — because it's part of the
	 * post type's own permastruct block — ends up ordered before a normally
	 * registered taxonomy permastruct, swallowing every category URL as a
	 * 404 attachment lookup. We register the rewrite rule ourselves with
	 * 'top' priority instead, which WordPress always merges in ahead of every
	 * generated rule regardless of registration order, and provide the term
	 * link via the term_link filter below since automatic link generation is
	 * tied to the (disabled) taxonomy rewrite.
	 */
	register_taxonomy( 'tv_category', 'tv_brand', array(
		'labels' => array(
			'name'          => 'Categories',
			'singular_name' => 'Category',
		),
		'hierarchical' => false,
		'public'       => true,
		'show_in_rest' => true,
		'query_var'    => 'tv_category',
		'rewrite'      => false,
	) );

	add_rewrite_rule( '^services/category/([^/]+)/?$', 'index.php?tv_category=$matches[1]', 'top' );
}
add_action( 'init', 'tvr_register_cpt' );

add_filter( 'term_link', function ( $url, $term, $taxonomy ) {
	if ( $taxonomy === 'tv_category' ) {
		return home_url( '/services/category/' . $term->slug . '/' );
	}
	return $url;
}, 10, 3 );

// Flush once on theme activation (not on every load).
function tvr_flush_rewrites_on_activation() {
	tvr_register_cpt();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'tvr_flush_rewrites_on_activation' );
