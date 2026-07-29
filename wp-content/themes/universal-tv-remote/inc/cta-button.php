<?php
/**
 * [tvr_cta] shortcode — a self-serve CTA button a content writer can drop
 * anywhere inside a Post/Page body via the "CTA" Classic Editor toolbar
 * button (assets/js/tinymce-cta.js) instead of hand-typing shortcode syntax.
 * `the_content()` already runs shortcodes, so no extra wiring is needed in
 * page.php/index.php/the page-templates.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// `color`/`style` are rendered as inline CSS (not new Tailwind utility
// classes) so a writer can pick any color or the outline look without ever
// needing a CSS rebuild — same reasoning as tvr_heading_style_attr().
// Defaults to var(--color-brand-500), so a button with no explicit color
// still follows the site-wide Brand Color from the Customizer.
add_shortcode( 'tvr_cta', function ( $atts ) {
	$atts = shortcode_atts( array(
		'text'  => 'Download on the App Store',
		'url'   => tvr_app_links()['ios'],
		'style' => 'solid',
		'color' => '',
	), $atts, 'tvr_cta' );

	$color = $atts['color'] ?: 'var(--color-brand-500)';
	$css   = $atts['style'] === 'outline'
		? sprintf( 'color:%1$s;border:2px solid %1$s;background:transparent;', $color )
		: sprintf( 'background-color:%1$s;color:#fff;border:2px solid transparent;', $color );

	return sprintf(
		'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="not-prose tvr-cta-btn inline-block rounded-full px-6 py-3 text-sm font-semibold shadow-sm transition" style="%2$s">%3$s</a>',
		esc_url( $atts['url'] ),
		esc_attr( $css ),
		esc_html( $atts['text'] )
	);
} );

// ---- Classic Editor toolbar button ----
add_filter( 'mce_external_plugins', function ( $plugins ) {
	if ( ! current_user_can( 'edit_posts' ) ) return $plugins;
	$js = get_template_directory() . '/assets/js/tinymce-cta.js';
	$plugins['tvr_cta'] = get_template_directory_uri() . '/assets/js/tinymce-cta.js?v=' . filemtime( $js );
	return $plugins;
} );

add_filter( 'mce_buttons', function ( $buttons ) {
	$buttons[] = 'tvr_cta_button';
	return $buttons;
} );

// Feeds the default (App Store) URL into the TinyMCE plugin so the popup can
// pre-fill it without a separate AJAX round-trip.
add_filter( 'tiny_mce_before_init', function ( $init ) {
	$init['tvr_cta_default_url'] = tvr_app_links()['ios'];
	return $init;
} );
