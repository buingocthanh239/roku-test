<?php
/**
 * Classic Editor doesn't bundle a Table button by default — only the CTA
 * shortcode button (inc/cta-button.php) is registered, and WP core's own
 * TinyMCE plugin set (wp-includes/js/tinymce/plugins/) never included the
 * stock "table" plugin at all (confirmed: not present alongside wplink,
 * wpgallery, etc.). Vendoring the matching plugin build directly, same
 * pattern as inc/cta-button.php's own custom TinyMCE plugin.
 *
 * assets/js/tinymce-table.js is TinyMCE 4.9.11's official `table` plugin
 * (this WP core's exact bundled TinyMCE version — see
 * wp-includes/js/tinymce/tinymce.min.js's majorVersion/minorVersion),
 * fetched as-is from the tinymce npm package. If WP core ever bumps its
 * bundled TinyMCE major version, re-fetch from
 * https://cdn.jsdelivr.net/npm/tinymce@<version>/plugins/table/plugin.min.js
 * to match.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'mce_buttons_2', function ( $buttons ) {
	array_unshift( $buttons, 'table' );
	return $buttons;
} );

add_filter( 'mce_external_plugins', function ( $plugins ) {
	if ( ! current_user_can( 'edit_posts' ) ) return $plugins;
	$js = get_template_directory() . '/assets/js/tinymce-table.js';
	$plugins['table'] = get_template_directory_uri() . '/assets/js/tinymce-table.js?v=' . filemtime( $js );
	return $plugins;
} );
