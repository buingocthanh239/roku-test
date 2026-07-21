<?php
/**
 * ACF (free) field group definitions, registered in code rather than the ACF
 * UI so they ship with the theme and don't depend on a database export.
 * Deliberately avoids Repeater/Flexible Content/Options Pages (ACF PRO only)
 * — repeating item lists use dedicated CPTs (inc/content-cpts.php) instead,
 * and multi-line lists within a single item (FAQ causes/solutions,
 * troubleshooting steps) use a plain textarea, one entry per line.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

add_action( 'acf/init', function () {

	$icon_choices = array(
		'no-account-required'      => 'No Account Required',
		'sync-backup'               => 'Smart Auto-Detection / Sync',
		'biometric-authentication' => 'Voice Control',
		'secure-private'            => 'Secure / Private',
		'user-friendly'             => 'User Friendly',
		'friendly-support'          => 'Friendly Support',
	);

	// ---- home_feature ----
	acf_add_local_field_group( array(
		'key'      => 'group_home_feature',
		'title'    => 'Feature Details',
		'fields'   => array(
			array( 'key' => 'field_hf_description', 'label' => 'Description', 'name' => 'description', 'type' => 'textarea', 'rows' => 3 ),
			array( 'key' => 'field_hf_icon', 'label' => 'Icon', 'name' => 'icon', 'type' => 'select', 'choices' => $icon_choices ),
		),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'home_feature' ) ) ),
	) );

	// ---- home_review ----
	acf_add_local_field_group( array(
		'key'      => 'group_home_review',
		'title'    => 'Review Details',
		'fields'   => array(
			array( 'key' => 'field_hr_body', 'label' => 'Review body', 'name' => 'body', 'type' => 'textarea', 'rows' => 4 ),
			array( 'key' => 'field_hr_author', 'label' => 'Author', 'name' => 'author', 'type' => 'text' ),
			array( 'key' => 'field_hr_date', 'label' => 'Date', 'name' => 'date', 'type' => 'text', 'placeholder' => 'MM/DD/YYYY' ),
		),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'home_review' ) ) ),
	) );

	// ---- compare_row ----
	acf_add_local_field_group( array(
		'key'      => 'group_compare_row',
		'title'    => 'Comparison Row Details',
		'fields'   => array(
			array( 'key' => 'field_cr_description', 'label' => 'Description', 'name' => 'description', 'type' => 'textarea', 'rows' => 2 ),
			array( 'key' => 'field_cr_us', 'label' => 'Universal TV Remote has this?', 'name' => 'us', 'type' => 'true_false', 'ui' => 1 ),
			array( 'key' => 'field_cr_c1', 'label' => 'Competitor 1 has this?', 'name' => 'competitor_1', 'type' => 'true_false', 'ui' => 1 ),
			array( 'key' => 'field_cr_c2', 'label' => 'Competitor 2 has this?', 'name' => 'competitor_2', 'type' => 'true_false', 'ui' => 1 ),
		),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'compare_row' ) ) ),
	) );

	// ---- faq_item ----
	acf_add_local_field_group( array(
		'key'      => 'group_faq_item',
		'title'    => 'FAQ Answer',
		'fields'   => array(
			array( 'key' => 'field_faq_answer', 'label' => 'Answer', 'name' => 'answer', 'type' => 'textarea', 'rows' => 4 ),
		),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'faq_item' ) ) ),
	) );

	// ---- quick_fix ----
	acf_add_local_field_group( array(
		'key'      => 'group_quick_fix',
		'title'    => 'Quick Fix Details',
		'fields'   => array(
			array( 'key' => 'field_qf_description', 'label' => 'Description', 'name' => 'description', 'type' => 'textarea', 'rows' => 2 ),
		),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'quick_fix' ) ) ),
	) );

	// ---- issue ----
	acf_add_local_field_group( array(
		'key'      => 'group_issue',
		'title'    => 'Issue Details',
		'fields'   => array(
			array( 'key' => 'field_issue_difficulty', 'label' => 'Difficulty', 'name' => 'difficulty', 'type' => 'select', 'choices' => array( 'Easy' => 'Easy', 'Medium' => 'Medium', 'Hard' => 'Hard' ), 'default_value' => 'Easy' ),
			array( 'key' => 'field_issue_time', 'label' => 'Time estimate', 'name' => 'time', 'type' => 'text', 'placeholder' => 'e.g. 2–5 minutes' ),
			array( 'key' => 'field_issue_causes', 'label' => 'Common causes', 'name' => 'causes', 'type' => 'textarea', 'rows' => 5, 'instructions' => 'Mỗi dòng là một nguyên nhân.' ),
			array( 'key' => 'field_issue_solutions', 'label' => 'Solutions', 'name' => 'solutions', 'type' => 'textarea', 'rows' => 6, 'instructions' => 'Mỗi dòng là một bước xử lý, theo thứ tự.' ),
		),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'issue' ) ) ),
	) );

	// ---- advanced_step ----
	acf_add_local_field_group( array(
		'key'      => 'group_advanced_step',
		'title'    => 'Advanced Section Steps',
		'fields'   => array(
			array( 'key' => 'field_adv_steps', 'label' => 'Steps', 'name' => 'steps', 'type' => 'textarea', 'rows' => 6, 'instructions' => 'Mỗi dòng là một bước, theo thứ tự.' ),
		),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'advanced_step' ) ) ),
	) );

	// ---- download_reason ----
	acf_add_local_field_group( array(
		'key'      => 'group_download_reason',
		'title'    => 'Reason Details',
		'fields'   => array(
			array( 'key' => 'field_dr_description', 'label' => 'Description', 'name' => 'description', 'type' => 'textarea', 'rows' => 3 ),
			array( 'key' => 'field_dr_icon', 'label' => 'Icon', 'name' => 'icon', 'type' => 'select', 'choices' => $icon_choices ),
		),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'download_reason' ) ) ),
	) );

	// ---- guide ----
	acf_add_local_field_group( array(
		'key'      => 'group_guide',
		'title'    => 'Guide Details',
		'fields'   => array(
			array( 'key' => 'field_guide_domain', 'label' => 'TV brand domain (slug)', 'name' => 'domain', 'type' => 'text', 'instructions' => 'Khớp với slug của bài TV Brand tương ứng (vd: samsung) để tự động link sang trang chi tiết.' ),
			array( 'key' => 'field_guide_updated', 'label' => 'Updated', 'name' => 'updated', 'type' => 'text', 'placeholder' => 'e.g. June 1, 2026' ),
			array( 'key' => 'field_guide_steps', 'label' => 'Steps', 'name' => 'steps', 'type' => 'wysiwyg', 'tabs' => 'visual', 'toolbar' => 'basic', 'media_upload' => 0 ),
		),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'guide' ) ) ),
	) );

	// ---- Home page (front-page.php) ----
	$home_page_id = (string) get_option( 'tvr_home_page_id' );
	if ( $home_page_id ) {
		acf_add_local_field_group( array(
			'key'      => 'group_home_page',
			'title'    => 'Home Page Content',
			'fields'   => array(
				array( 'key' => 'field_home_hero_title', 'label' => 'Hero title', 'name' => 'hero_title', 'type' => 'text' ),
				array( 'key' => 'field_home_hero_subtitle', 'label' => 'Hero subtitle', 'name' => 'hero_subtitle', 'type' => 'textarea', 'rows' => 2 ),
				array( 'key' => 'field_home_stars_caption', 'label' => 'Stars caption', 'name' => 'stars_caption', 'type' => 'text' ),
				array( 'key' => 'field_home_marquee', 'label' => 'Marquee brand names', 'name' => 'marquee_brands', 'type' => 'textarea', 'rows' => 6, 'instructions' => 'Mỗi dòng một tên thương hiệu.' ),
				array( 'key' => 'field_home_works_heading', 'label' => '"Works with Your TV Brand" heading', 'name' => 'works_heading', 'type' => 'text' ),
				array( 'key' => 'field_home_works_subheading', 'label' => '"Works with Your TV Brand" subheading', 'name' => 'works_subheading', 'type' => 'text' ),
				array( 'key' => 'field_home_features_heading', 'label' => 'Features heading', 'name' => 'features_heading', 'type' => 'text' ),
				array( 'key' => 'field_home_features_subheading', 'label' => 'Features subheading', 'name' => 'features_subheading', 'type' => 'text' ),
				array( 'key' => 'field_home_reviews_heading', 'label' => 'Reviews heading', 'name' => 'reviews_heading', 'type' => 'text' ),
				array( 'key' => 'field_home_reviews_subheading', 'label' => 'Reviews subheading', 'name' => 'reviews_subheading', 'type' => 'text' ),
				array( 'key' => 'field_home_compare_heading', 'label' => 'Comparison heading', 'name' => 'compare_heading', 'type' => 'text' ),
				array( 'key' => 'field_home_compare_c1_name', 'label' => 'Competitor 1 name', 'name' => 'compare_competitor_1_name', 'type' => 'text' ),
				array( 'key' => 'field_home_compare_c2_name', 'label' => 'Competitor 2 name', 'name' => 'compare_competitor_2_name', 'type' => 'text' ),
				array( 'key' => 'field_home_cta_heading', 'label' => 'Bottom CTA heading', 'name' => 'cta_heading', 'type' => 'text' ),
				array( 'key' => 'field_home_cta_subheading', 'label' => 'Bottom CTA subheading', 'name' => 'cta_subheading', 'type' => 'textarea', 'rows' => 2 ),
			),
			'location' => array( array( array( 'param' => 'page', 'operator' => '==', 'value' => $home_page_id ) ) ),
		) );
	}

	// ---- Help page ----
	acf_add_local_field_group( array(
		'key'      => 'group_help_page',
		'title'    => 'Help Page Content',
		'fields'   => array(
			array( 'key' => 'field_help_title', 'label' => 'Hero title', 'name' => 'hero_title', 'type' => 'text' ),
			array( 'key' => 'field_help_subtitle', 'label' => 'Hero subtitle', 'name' => 'hero_subtitle', 'type' => 'textarea', 'rows' => 2 ),
		),
		'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/template-help.php' ) ) ),
	) );

	// ---- Contact page ----
	acf_add_local_field_group( array(
		'key'      => 'group_contact_page',
		'title'    => 'Contact Page Content',
		'fields'   => array(
			array( 'key' => 'field_contact_heading', 'label' => 'Heading', 'name' => 'heading', 'type' => 'text' ),
			array( 'key' => 'field_contact_intro', 'label' => 'Intro text', 'name' => 'intro', 'type' => 'textarea', 'rows' => 2 ),
		),
		'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/template-contact.php' ) ) ),
	) );

	// ---- Troubleshooting page ----
	acf_add_local_field_group( array(
		'key'      => 'group_troubleshooting_page',
		'title'    => 'Troubleshooting Page Content',
		'fields'   => array(
			array( 'key' => 'field_ts_heading', 'label' => 'Heading', 'name' => 'heading', 'type' => 'text' ),
			array( 'key' => 'field_ts_subheading', 'label' => 'Subheading', 'name' => 'subheading', 'type' => 'textarea', 'rows' => 2 ),
			array( 'key' => 'field_ts_qf_heading', 'label' => 'Quick Fixes heading', 'name' => 'quick_fixes_heading', 'type' => 'text' ),
			array( 'key' => 'field_ts_qf_subheading', 'label' => 'Quick Fixes subheading', 'name' => 'quick_fixes_subheading', 'type' => 'text' ),
			array( 'key' => 'field_ts_issues_heading', 'label' => 'Issues heading', 'name' => 'issues_heading', 'type' => 'text' ),
			array( 'key' => 'field_ts_issues_subheading', 'label' => 'Issues subheading', 'name' => 'issues_subheading', 'type' => 'text' ),
			array( 'key' => 'field_ts_adv_heading', 'label' => 'Advanced heading', 'name' => 'advanced_heading', 'type' => 'text' ),
			array( 'key' => 'field_ts_adv_subheading', 'label' => 'Advanced subheading', 'name' => 'advanced_subheading', 'type' => 'text' ),
			array( 'key' => 'field_ts_cta_heading', 'label' => 'Support CTA heading', 'name' => 'support_cta_heading', 'type' => 'text' ),
			array( 'key' => 'field_ts_cta_text', 'label' => 'Support CTA text', 'name' => 'support_cta_text', 'type' => 'text' ),
		),
		'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/template-troubleshooting.php' ) ) ),
	) );

	// ---- Download App page ----
	acf_add_local_field_group( array(
		'key'      => 'group_download_page',
		'title'    => 'Download App Page Content',
		'fields'   => array(
			array( 'key' => 'field_da_hero_title', 'label' => 'Hero title', 'name' => 'hero_title', 'type' => 'text' ),
			array( 'key' => 'field_da_hero_subtitle', 'label' => 'Hero subtitle', 'name' => 'hero_subtitle', 'type' => 'textarea', 'rows' => 2 ),
			array( 'key' => 'field_da_reasons_heading', 'label' => 'Reasons heading', 'name' => 'reasons_heading', 'type' => 'text' ),
			array( 'key' => 'field_da_reasons_subheading', 'label' => 'Reasons subheading', 'name' => 'reasons_subheading', 'type' => 'text' ),
			array( 'key' => 'field_da_cta_heading', 'label' => 'Bottom CTA heading', 'name' => 'cta_heading', 'type' => 'text' ),
			array( 'key' => 'field_da_cta_subheading', 'label' => 'Bottom CTA subheading', 'name' => 'cta_subheading', 'type' => 'textarea', 'rows' => 2 ),
		),
		'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/template-download-app.php' ) ) ),
	) );

	// ---- Guides page ----
	acf_add_local_field_group( array(
		'key'      => 'group_guides_page',
		'title'    => 'Guides Page Content',
		'fields'   => array(
			array( 'key' => 'field_guides_heading', 'label' => 'Heading', 'name' => 'heading', 'type' => 'text' ),
			array( 'key' => 'field_guides_subheading', 'label' => 'Subheading', 'name' => 'subheading', 'type' => 'textarea', 'rows' => 2 ),
		),
		'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/template-guides.php' ) ) ),
	) );

} );
