<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function tvr_enqueue_assets() {
	$dir = get_template_directory();
	$uri = get_template_directory_uri();

	wp_enqueue_style( 'tvr-style', $uri . '/assets/css/style.css', array(), filemtime( $dir . '/assets/css/style.css' ) );

	wp_enqueue_script( 'tvr-adjust', $uri . '/assets/js/adjust.js', array(), filemtime( $dir . '/assets/js/adjust.js' ), true );
	wp_enqueue_script( 'tvr-analytics', $uri . '/assets/js/analytics.js', array(), filemtime( $dir . '/assets/js/analytics.js' ), true );
	wp_enqueue_script( 'tvr-main', $uri . '/assets/js/main.js', array( 'tvr-adjust', 'tvr-analytics' ), filemtime( $dir . '/assets/js/main.js' ), true );

	wp_localize_script( 'tvr-main', 'TVR_CONFIG', array(
		'gtagId'         => defined( 'TVR_GTAG_ID' ) ? TVR_GTAG_ID : '',
		'conversionId'   => defined( 'TVR_ADS_CONVERSION_ID' ) ? TVR_ADS_CONVERSION_ID : '',
	) );
}
add_action( 'wp_enqueue_scripts', 'tvr_enqueue_assets' );

// Defer non-critical scripts (all of ours are behavior, not layout-critical).
function tvr_script_defer( $tag, $handle ) {
	if ( in_array( $handle, array( 'tvr-adjust', 'tvr-analytics', 'tvr-main' ), true ) ) {
		return str_replace( ' src', ' defer src', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'tvr_script_defer', 10, 2 );
