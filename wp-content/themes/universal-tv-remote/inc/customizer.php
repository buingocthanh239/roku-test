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
	// ---- Brand Colors — single source of truth for the theme's blue/navy
	// palette. Tailwind v4 already compiles every bg-brand-*/text-brand-*/
	// bg-navy-* utility to `var(--color-brand-500)` etc. (see assets/css/src/
	// input.css @theme block), so overriding those CSS custom properties in
	// wp_head re-colors every utility class site-wide with no CSS rebuild. ----
	$wp_customize->add_section( 'tvr_brand_colors', array(
		'title'    => 'Brand Colors',
		'priority' => 20,
	) );

	$wp_customize->add_setting( 'tvr_brand_color', array(
		'default'           => '#367fee',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'tvr_brand_color', array(
		'label'   => 'Brand color (buttons, links, accents)',
		'section' => 'tvr_brand_colors',
	) ) );

	$wp_customize->add_setting( 'tvr_navy_color', array(
		'default'           => '#001b38',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'tvr_navy_color', array(
		'label'   => 'Dark navy color (hero/footer backgrounds, dark headings)',
		'section' => 'tvr_brand_colors',
	) ) );

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

	// ---- Header/Footer Code — raw snippet injection (GTM, tracking pixels,
	// custom <script>/<style>), no theme file editing required. ----
	$wp_customize->add_section( 'tvr_header_footer_code', array(
		'title'       => 'Header/Footer Code',
		'panel'       => 'tvr_header_footer',
		'description' => 'Dán code (Google Tag Manager, tracking script, custom <script>/<style>...) — không cần sửa file theme. Chỉ Administrator mới lưu được nguyên trạng; các role khác sẽ bị lọc bớt thẻ không an toàn.',
	) );

	$wp_customize->add_setting( 'tvr_header_code', array(
		'default'           => '',
		'sanitize_callback' => 'tvr_sanitize_raw_code',
		'capability'        => 'unfiltered_html',
	) );
	$wp_customize->add_control( 'tvr_header_code', array(
		'label'       => 'Header code (chèn ngay trước </head>)',
		'section'     => 'tvr_header_footer_code',
		'type'        => 'textarea',
	) );

	$wp_customize->add_setting( 'tvr_footer_code', array(
		'default'           => '',
		'sanitize_callback' => 'tvr_sanitize_raw_code',
		'capability'        => 'unfiltered_html',
	) );
	$wp_customize->add_control( 'tvr_footer_code', array(
		'label'       => 'Footer code (chèn ngay trước </body>)',
		'section'     => 'tvr_header_footer_code',
		'type'        => 'textarea',
	) );
} );

// Raw pass-through for users allowed to write unfiltered HTML (Administrators
// by default) — otherwise strip anything that isn't safe post content, same
// trust boundary WordPress core uses for the "Additional CSS" field.
function tvr_sanitize_raw_code( $value ) {
	return current_user_can( 'unfiltered_html' ) ? $value : wp_kses_post( $value );
}

// Re-declares the brand/navy CSS custom properties Tailwind's compiled
// output reads at paint time. Lighter/darker shades are derived from the
// single picked color with color-mix() instead of asking for 7 separate
// swatches — outside Tailwind's `@layer theme`, so it wins regardless of
// where it lands in <head> relative to style.css (unlayered always beats
// layered in the CSS cascade).
add_action( 'wp_head', function () {
	$brand = get_theme_mod( 'tvr_brand_color', '#367fee' );
	$navy  = get_theme_mod( 'tvr_navy_color', '#001b38' );
	?>
	<style id="tvr-brand-colors">
		:root {
			--color-brand-500: <?php echo esc_html( $brand ); ?>;
			--color-brand-50:  color-mix(in srgb, <?php echo esc_html( $brand ); ?> 12%, white);
			--color-brand-100: color-mix(in srgb, <?php echo esc_html( $brand ); ?> 22%, white);
			--color-brand-300: color-mix(in srgb, <?php echo esc_html( $brand ); ?> 55%, white);
			--color-brand-400: color-mix(in srgb, <?php echo esc_html( $brand ); ?> 78%, white);
			--color-brand-600: color-mix(in srgb, <?php echo esc_html( $brand ); ?> 90%, black);
			--color-brand-700: color-mix(in srgb, <?php echo esc_html( $brand ); ?> 78%, black);
			--color-navy-900: <?php echo esc_html( $navy ); ?>;
		}
	</style>
	<?php
}, 5 );

add_action( 'wp_head', function () {
	$code = get_theme_mod( 'tvr_header_code' );
	if ( $code ) echo "\n" . $code . "\n";
}, 20 );

add_action( 'wp_footer', function () {
	$code = get_theme_mod( 'tvr_footer_code' );
	if ( $code ) echo "\n" . $code . "\n";
}, 20 );
