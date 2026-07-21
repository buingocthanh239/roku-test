<?php
/**
 * One-off CLI migration: moves the content that used to be hardcoded in
 * front-page.php / inc/data.php / page-templates/*.php / data/guides.json
 * into the CPTs + ACF fields registered in inc/content-cpts.php +
 * inc/acf-fields.php, so it becomes editable in wp-admin under "Site
 * Content" and the respective Pages. Content itself is unchanged — this
 * only changes WHERE it lives. Idempotent: matches existing posts by title
 * within each post type, updates instead of duplicating on re-run.
 *
 * Run: docker compose exec wordpress php wp-content/themes/universal-tv-remote/scripts/migrate-static-content.php
 * Local: see scripts/import-services.php header for the Local PHP CLI invocation.
 */

if ( php_sapi_name() !== 'cli' ) {
	http_response_code( 403 );
	exit( 'CLI only.' );
}

define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 4 ) . '/wp-load.php';

function tvr_migrate_upsert_post( $post_type, $title, $fields = array(), $order = 0, $taxonomy = null, $terms = array() ) {
	// wp_insert_post() runs post_title through kses on save, which
	// normalizes a bare "&" to "&amp;" (but leaves quotes/apostrophes
	// alone — esc_html() over-encodes those and would mismatch instead).
	// wp_kses_normalize_entities() replicates exactly that transform so the
	// lookup matches what's actually stored, keeping this idempotent.
	$existing = get_posts( array(
		'post_type'      => $post_type,
		'title'          => wp_kses_normalize_entities( $title ),
		'post_status'    => 'any',
		'posts_per_page' => 1,
	) );

	$args = array(
		'post_type'   => $post_type,
		'post_status' => 'publish',
		'post_title'  => $title,
		'menu_order'  => $order,
	);

	if ( $existing ) {
		$args['ID'] = $existing[0]->ID;
		$post_id    = wp_update_post( $args, true );
	} else {
		$post_id = wp_insert_post( $args, true );
	}

	if ( is_wp_error( $post_id ) ) {
		fwrite( STDERR, "Failed [$post_type] $title: " . $post_id->get_error_message() . "\n" );
		return null;
	}

	foreach ( $fields as $key => $value ) {
		update_field( $key, $value, $post_id );
	}

	if ( $taxonomy && $terms ) {
		wp_set_object_terms( $post_id, $terms, $taxonomy, false );
	}

	return $post_id;
}

function tvr_migrate_page_fields( $page_id, $fields ) {
	foreach ( $fields as $key => $value ) {
		update_field( $key, $value, $page_id );
	}
}

$count = 0;

// ============================================================
// HOME PAGE
// ============================================================
$home_page_id = (int) get_option( 'tvr_home_page_id' );
if ( $home_page_id ) {
	tvr_migrate_page_fields( $home_page_id, array(
		'hero_title'                 => 'Universal TV Remote',
		'hero_subtitle'              => 'Turn your iPhone into a powerful remote control for 1000+ TV brands — Samsung, LG, Sony, Roku, and more.',
		'stars_caption'              => 'Loved by 1M+ users worldwide!',
		'marquee_brands'             => implode( "\n", array( 'Samsung', 'LG', 'Sony', 'TCL', 'Hisense', 'Philips', 'Roku TV', 'Fire TV', 'Apple TV', 'Panasonic', 'Sharp', 'Vizio' ) ),
		'works_heading'              => 'Works with Your TV Brand',
		'works_subheading'           => 'Instant Wi-Fi control for the world most popular smart TVs and streaming devices',
		'features_heading'           => 'Features',
		'features_subheading'        => 'Everything you need to control your TV from your iPhone',
		'reviews_heading'            => 'What people say',
		'reviews_subheading'         => 'Feedback from people who use Universal TV Remote every day',
		'compare_heading'            => 'Why Universal TV Remote',
		'compare_competitor_1_name'  => 'TVRemote+',
		'compare_competitor_2_name'  => 'AnyMote',
		'cta_heading'                => 'Download Universal TV Remote',
		'cta_subheading'             => 'The easiest way to control your TV from your iPhone — 1000+ brands, zero setup headaches.',
	) );
	echo "Home page fields set.\n";
}

// ---- home_feature ----
$features = array(
	array( 'title' => '1000+ TV Brands', 'desc' => 'Works with Samsung, LG, Sony, TCL, Hisense, Roku TV, Philips, Panasonic, and hundreds more brands out of the box.', 'icon' => 'no-account-required' ),
	array( 'title' => 'Smart Auto-Detection', 'desc' => 'Instantly finds and connects to your TV over Wi-Fi — no manual IP setup needed for most brands.', 'icon' => 'sync-backup' ),
	array( 'title' => 'Voice Control', 'desc' => 'Control your TV hands-free with Siri Shortcuts. Set up phrases like "Hey Siri, mute the TV" in seconds.', 'icon' => 'biometric-authentication' ),
	array( 'title' => 'No Ads, No Tracking', 'desc' => 'Your privacy matters. Universal TV Remote never shows ads, never tracks you, and never sells your data.', 'icon' => 'secure-private' ),
	array( 'title' => 'Macros & Custom Buttons', 'desc' => 'Record button sequences as one-tap macros — turn on the TV, switch to HDMI 2, and open Netflix with a single tap.', 'icon' => 'user-friendly' ),
	array( 'title' => 'Smart Home Ready', 'desc' => 'Integrates with Apple HomeKit, Amazon Alexa, and Google Assistant for whole-home automation.', 'icon' => 'friendly-support' ),
);
foreach ( $features as $i => $f ) {
	tvr_migrate_upsert_post( 'home_feature', $f['title'], array( 'description' => $f['desc'], 'icon' => $f['icon'] ), $i );
	$count++;
}

// ---- home_review ----
$reviews = array(
	array( 'title' => 'Works perfectly with my Samsung', 'body' => 'I lost my Samsung remote and found this app the same day. Setup took under a minute — it found my TV on the network automatically and every button works exactly as expected.', 'author' => 'James T.', 'date' => '05/20/2026' ),
	array( 'title' => 'Best TV remote app I tried', 'body' => 'Tried three other remote apps before this one and they were all either riddled with ads or could not find my LG TV reliably. This one connected instantly and the interface is clean.', 'author' => 'Anna P.', 'date' => '04/15/2026' ),
	array( 'title' => 'Siri shortcuts are a game changer', 'body' => 'Being able to say "Hey Siri, turn on the living room TV" without touching my phone is something I did not know I needed. Works flawlessly with my Sony Google TV.', 'author' => 'Carlos M.', 'date' => '03/28/2026' ),
	array( 'title' => 'Replaced my Roku remote', 'body' => 'My kids kept losing the physical Roku remote so I set this up on a spare iPhone. It finds the Roku instantly and the keyboard is so much faster for searching.', 'author' => 'Linda K.', 'date' => '03/05/2026' ),
	array( 'title' => 'Surprisingly full-featured', 'body' => 'Controls everything on my TCL TV including the Roku channel grid and all the streaming apps. The custom button layout lets me put my most-used apps front and center.', 'author' => 'Marco R.', 'date' => '02/12/2026' ),
	array( 'title' => 'Simple, reliable, no nonsense', 'body' => 'Downloaded it when my Hisense remote died. Found the TV on my first try, every button works, and the app opens in under a second. Exactly what you want from a remote app.', 'author' => 'Sophie W.', 'date' => '01/22/2026' ),
);
foreach ( $reviews as $i => $r ) {
	tvr_migrate_upsert_post( 'home_review', $r['title'], array( 'body' => $r['body'], 'author' => $r['author'], 'date' => $r['date'] ), $i );
	$count++;
}

// ---- compare_row ----
$compare = array(
	array( '1000+ TV Brands', 'Supports Samsung, LG, Sony, TCL, Hisense, Roku TV, and 1000+ more brands.', true, false, false ),
	array( 'Wi-Fi Smart Auto-Detection', 'Automatically finds your TV on the network — no manual IP entry.', true, false, true ),
	array( 'No Ads', 'The app is 100% ad-free for a clean remote experience.', true, false, false ),
	array( 'No Account Required', 'Use the app immediately — no sign-up or email needed.', true, true, true ),
	array( 'Voice Control (Siri)', 'Control your TV with Siri Shortcuts on iPhone.', true, false, false ),
	array( 'Macros & Custom Buttons', 'Record multi-step sequences and run them with one tap.', true, false, false ),
	array( 'Apple HomeKit Integration', 'Add TV controls to your Apple Home scenes and automations.', true, false, false ),
	array( 'Amazon Alexa Support', 'Control your TV with Alexa voice commands.', true, false, false ),
	array( 'Family Sharing', 'Share the app with your family via Apple Family Sharing.', true, true, true ),
	array( 'Multiple TVs', 'Add and switch between multiple TVs in the same app.', true, true, true ),
);
foreach ( $compare as $i => $row ) {
	list( $label, $desc, $us, $c1, $c2 ) = $row;
	tvr_migrate_upsert_post( 'compare_row', $label, array( 'description' => $desc, 'us' => $us, 'competitor_1' => $c1, 'competitor_2' => $c2 ), $i );
	$count++;
}

// ============================================================
// HELP PAGE + FAQ
// ============================================================
$help_page = get_posts( array( 'post_type' => 'page', 'name' => 'help', 'posts_per_page' => 1 ) );
if ( $help_page ) {
	tvr_migrate_page_fields( $help_page[0]->ID, array(
		'hero_title'    => 'Frequently Asked Questions',
		'hero_subtitle' => 'Answers to the most common questions about Universal TV Remote — compatibility, setup, features, and more.',
	) );
	echo "Help page fields set.\n";
}

$help_data = tvr_help_data();
foreach ( $help_data as $group ) {
	$term = term_exists( $group['category'], 'faq_category' );
	if ( ! $term ) $term = wp_insert_term( $group['category'], 'faq_category' );
	// term_exists()/wp_insert_term() return term_id as a numeric STRING.
	// wp_set_object_terms() only recognizes an already-int $term as a term
	// ID (is_int() check); a numeric string that doesn't happen to match an
	// existing term by NAME falls through to wp_insert_term($string, ...),
	// silently creating a garbage term literally named e.g. "8". Must cast.
	$term_id = is_wp_error( $term ) ? null : (int) $term['term_id'];

	foreach ( $group['items'] as $i => $item ) {
		tvr_migrate_upsert_post( 'faq_item', $item['q'], array( 'answer' => $item['a'] ), $i, 'faq_category', $term_id ? array( $term_id ) : array() );
		$count++;
	}
}

// ============================================================
// CONTACT PAGE
// ============================================================
$contact_page = get_posts( array( 'post_type' => 'page', 'name' => 'contact', 'posts_per_page' => 1 ) );
if ( $contact_page ) {
	tvr_migrate_page_fields( $contact_page[0]->ID, array(
		'heading' => 'Contact us',
		'intro'   => 'Spotted an outdated guide or a missing TV brand? Have a question about Universal TV Remote? Send us a message — real humans reply.',
	) );
	echo "Contact page fields set.\n";
}

// ============================================================
// TROUBLESHOOTING PAGE
// ============================================================
$ts_page = get_posts( array( 'post_type' => 'page', 'name' => 'troubleshooting', 'posts_per_page' => 1 ) );
if ( $ts_page ) {
	tvr_migrate_page_fields( $ts_page[0]->ID, array(
		'heading'                 => 'Troubleshooting Guide',
		'subheading'              => 'Having trouble with Universal TV Remote? Follow these steps to resolve the most common issues quickly.',
		'quick_fixes_heading'     => 'Quick Fixes',
		'quick_fixes_subheading'  => 'Try these first — they resolve most issues in under a minute.',
		'issues_heading'          => 'Common Issues & Solutions',
		'issues_subheading'       => 'Click any issue to see causes and step-by-step solutions.',
		'advanced_heading'        => 'Advanced Troubleshooting',
		'advanced_subheading'     => 'For persistent issues that the above steps did not resolve.',
		'support_cta_heading'     => 'Still need help?',
		'support_cta_text'        => 'If the issue persists, our support team is happy to help.',
	) );
	echo "Troubleshooting page fields set.\n";
}

foreach ( tvr_quick_fixes() as $i => $fix ) {
	tvr_migrate_upsert_post( 'quick_fix', $fix['title'], array( 'description' => $fix['desc'] ), $i );
	$count++;
}

foreach ( tvr_issues() as $i => $issue ) {
	tvr_migrate_upsert_post( 'issue', $issue['title'], array(
		'difficulty' => $issue['difficulty'],
		'time'       => $issue['time'],
		'causes'     => implode( "\n", $issue['causes'] ),
		'solutions'  => implode( "\n", $issue['solutions'] ),
	), $i );
	$count++;
}

foreach ( tvr_advanced_troubleshooting() as $i => $section ) {
	tvr_migrate_upsert_post( 'advanced_step', $section['category'], array( 'steps' => implode( "\n", $section['steps'] ) ), $i );
	$count++;
}

// ============================================================
// DOWNLOAD APP PAGE
// ============================================================
$da_page = get_posts( array( 'post_type' => 'page', 'name' => 'download-app', 'posts_per_page' => 1 ) );
if ( $da_page ) {
	tvr_migrate_page_fields( $da_page[0]->ID, array(
		'hero_title'          => 'Download Universal TV Remote',
		'hero_subtitle'       => 'Turn your iPhone into a remote for your smart TV. Control Samsung, LG, Sony, Roku, TCL, and Hisense TVs over Wi-Fi — no extra hardware, no lost remote.',
		'reasons_heading'     => 'Why download Universal TV Remote',
		'reasons_subheading'  => 'One app to control all of your smart TVs from your phone',
		'cta_heading'         => 'Get Universal TV Remote today',
		'cta_subheading'      => 'Download for free and control every smart TV in your home from your iPhone — Samsung, LG, Sony, Roku, TCL, and Hisense.',
	) );
	echo "Download App page fields set.\n";
}

$reasons = array(
	array( 'title' => 'Never hunt for the remote again', 'desc' => 'Lost or broke your TV remote? Your iPhone is always within reach — power, volume, channels, and navigation, all in one app.', 'icon' => 'user-friendly' ),
	array( 'title' => 'Works with every major brand', 'desc' => 'One app for Samsung, LG, Sony, Roku, TCL, and Hisense smart TVs. Switch between rooms and brands without switching apps.', 'icon' => 'no-account-required' ),
	array( 'title' => 'Connects over Wi-Fi — no hardware', 'desc' => 'Control your TV over your home Wi-Fi network. No dongles, no infrared blasters, no extra gadgets to buy or charge.', 'icon' => 'secure-private' ),
	array( 'title' => 'A full remote, plus more', 'desc' => 'Type with the on-screen keyboard, launch apps, adjust volume, and navigate menus faster than with the original remote.', 'icon' => 'friendly-support' ),
);
foreach ( $reasons as $i => $r ) {
	tvr_migrate_upsert_post( 'download_reason', $r['title'], array( 'description' => $r['desc'], 'icon' => $r['icon'] ), $i );
	$count++;
}

// ============================================================
// GUIDES PAGE
// ============================================================
$guides_page = get_posts( array( 'post_type' => 'page', 'name' => 'guides', 'posts_per_page' => 1 ) );
if ( $guides_page ) {
	tvr_migrate_page_fields( $guides_page[0]->ID, array(
		'heading'    => 'Setup Guides',
		'subheading' => 'Step-by-step instructions for connecting Universal TV Remote to your TV, brand by brand.',
	) );
	echo "Guides page fields set.\n";
}

foreach ( tvr_guides() as $i => $guide ) {
	$brand_post = tvr_get_brand_by_domain( $guide['domain'] );
	$title      = $brand_post ? $brand_post->post_title : ucfirst( $guide['domain'] );

	$steps_html = '';
	foreach ( $guide['steps'] as $step ) {
		$steps_html .= '<li><strong>' . esc_html( $step['title'] ) . '</strong><br>' . esc_html( $step['body'] ) . '</li>' . "\n";
	}
	$steps_html = '<ol>' . "\n" . $steps_html . '</ol>';

	tvr_migrate_upsert_post( 'guide', $title, array(
		'domain'  => $guide['domain'],
		'updated' => $guide['updated'],
		'steps'   => $steps_html,
	), $i );
	$count++;
}

echo "Done. $count content items migrated.\n";
