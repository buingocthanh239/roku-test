<?php
/**
 * JSON-LD helpers — port of components/JsonLd.jsx (same "</script>" escaping)
 * plus the Organization/WebSite/SoftwareApplication/BreadcrumbList/FAQPage
 * builders that lived inline in the various app/*.jsx pages.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function tvr_json_ld( $data ) {
	$json = wp_json_encode( $data );
	// Escape "<" so a "</script>" inside any value can't break out of the tag.
	$json = str_replace( chr( 60 ), chr( 92 ) . 'u003c', $json );
	echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
}

function tvr_organization_website_ld() {
	$site_url = home_url( '/' );
	return array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			array(
				'@type' => 'Organization',
				'@id'   => $site_url . '#organization',
				'name'  => TVR_SITE_NAME,
				'url'   => $site_url,
				'logo'  => tvr_asset( 'logo.webp' ),
			),
			array(
				'@type'           => 'WebSite',
				'@id'             => $site_url . '#website',
				'url'             => $site_url,
				'name'            => TVR_SITE_NAME,
				'description'     => TVR_SITE_DESCRIPTION,
				'publisher'       => array( '@id' => $site_url . '#organization' ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => $site_url . 'services/?q={search_term_string}',
					),
					'query-input' => 'required name=search_term_string',
				),
			),
		),
	);
}

function tvr_software_application_ld( $id_suffix = '#app', $os = 'iOS' ) {
	return array(
		'@context'            => 'https://schema.org',
		'@type'               => 'SoftwareApplication',
		'@id'                 => home_url( '/' ) . $id_suffix,
		'name'                => TVR_SITE_NAME,
		'applicationCategory' => 'UtilitiesApplication',
		'operatingSystem'     => $os,
		'offers'              => array( '@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD' ),
	);
}

function tvr_breadcrumb_ld( $items ) {
	$list = array();
	foreach ( array_values( $items ) as $i => $item ) {
		$list[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $item['name'],
			'item'     => $item['url'],
		);
	}
	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $list,
	);
}

function tvr_faq_ld( $qa_pairs ) {
	$entities = array();
	foreach ( $qa_pairs as $pair ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $pair['q'],
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $pair['a'] ),
		);
	}
	return array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	);
}
