<?php
/**
 * Header/Footer chrome settings — Appearance → Customize → "Header & Footer".
 * Uses the native Customizer (theme_mod, get_theme_mod()) rather than ACF:
 * this is site-wide chrome, not content tied to one post/page, and ACF free
 * has no Options Page for that (PRO-only). custom-logo support was already
 * declared (inc/setup.php) but never actually rendered anywhere — wired up
 * here via the_custom_logo().
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'customize_register', function ( $wp_customize ) {
	$wp_customize->add_panel( 'tvr_header_footer', array(
		'title'    => 'Header & Footer',
		'priority' => 25,
	) );

	// ---- Header ----
	$wp_customize->add_section( 'tvr_header', array(
		'title' => 'Header',
		'panel' => 'tvr_header_footer',
	) );

	$wp_customize->add_setting( 'tvr_site_tagline', array(
		'default'           => '1000+ TV brands',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'tvr_site_tagline', array(
		'label'   => 'Tagline under site name (header)',
		'section' => 'tvr_header',
		'type'    => 'text',
	) );

	// ---- Footer ----
	$wp_customize->add_section( 'tvr_footer', array(
		'title' => 'Footer',
		'panel' => 'tvr_header_footer',
	) );

	$wp_customize->add_setting( 'tvr_footer_blurb', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'tvr_footer_blurb', array(
		'label'       => 'Footer blurb (leave empty to auto-generate "A directory of N TV brands…")',
		'section'     => 'tvr_footer',
		'type'        => 'textarea',
	) );

	$wp_customize->add_setting( 'tvr_footer_company_heading', array(
		'default'           => 'Company',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'tvr_footer_company_heading', array(
		'label'   => 'Column heading — Company',
		'section' => 'tvr_footer',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'tvr_footer_resources_heading', array(
		'default'           => 'Resources',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'tvr_footer_resources_heading', array(
		'label'   => 'Column heading — Resources',
		'section' => 'tvr_footer',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'tvr_footer_downloads_heading', array(
		'default'           => 'Downloads',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'tvr_footer_downloads_heading', array(
		'label'   => 'Column heading — Downloads',
		'section' => 'tvr_footer',
		'type'    => 'text',
	) );

	$wp_customize->add_setting( 'tvr_privacy_url', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'tvr_privacy_url', array(
		'label'       => 'Privacy Policy URL (leave empty to hide the link)',
		'section'     => 'tvr_footer',
		'type'        => 'url',
	) );

	$wp_customize->add_setting( 'tvr_terms_url', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'tvr_terms_url', array(
		'label'       => 'Terms of Use URL (leave empty to hide the link)',
		'section'     => 'tvr_footer',
		'type'        => 'url',
	) );

	$wp_customize->add_setting( 'tvr_copyright_text', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'tvr_copyright_text', array(
		'label'       => 'Copyright line (leave empty for "© {year} Universal TV Remote. All rights reserved.")',
		'section'     => 'tvr_footer',
		'type'        => 'text',
	) );
} );
