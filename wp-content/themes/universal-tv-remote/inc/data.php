<?php
/**
 * Site constants + seed data for scripts/migrate-static-content.php.
 *
 * tvr_help_data() / tvr_quick_fixes() / tvr_issues() /
 * tvr_advanced_troubleshooting() / tvr_guides() are NOT read by any live
 * template — that content now lives in the faq_item/quick_fix/issue/
 * advanced_step/guide CPTs (wp-admin → Site Content), editable without
 * touching code. These functions remain only as the migration script's
 * one-time seed source (e.g. for re-running it against a fresh install).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'TVR_SITE_NAME', 'Universal TV Remote' );
define( 'TVR_SITE_TAGLINE', 'Supported TVs Directory' );
define( 'TVR_SITE_DESCRIPTION', 'A directory of 1000+ TV brands and models supported by Universal TV Remote. Find setup guides for Samsung, LG, Sony, TCL, Hisense, Roku TV and more.' );
define( 'TVR_CONTACT_EMAIL', 'customer@begamob.com' );

function tvr_app_links() {
	return array(
		'ios' => 'https://apps.apple.com/app/apple-store/id1581765635?pt=123227430&ct=backlinks&mt=8',
		'mac' => 'https://apps.apple.com/app/apple-store/id1581765635?pt=123227430&ct=backlinks&mt=12',
	);
}

// Home page's App Store / Mac App Store badges go through these begamob.com
// landing pages instead of tvr_app_links()'s direct Apple links, so the
// hero-banner click is attributed in analytics before it forwards to the App
// Store (per 29/7 feedback doc — "utm tracking trên trang chủ").
function tvr_home_app_links() {
	return array(
		'ios' => 'https://remotetv.begamob.com/dowload-appstore/?utm_source=home&utm_medium=hero_banner&utm_campaign=SEO',
		'mac' => 'https://remotetv.begamob.com/dowload-mac-app-store/?utm_source=home&utm_medium=hero_banner&utm_campaign=SEO',
	);
}

// Category key => display label. Mirrors scripts/build-data.mjs's hardcoded map.
function tvr_category_label_map() {
	return array(
		'smart-tv'  => 'Smart TVs',
		'streaming' => 'Streaming Devices',
		'audio'     => 'Audio / AV Receivers',
		'projector' => 'Projectors',
		'popular'   => 'Most Popular',
	);
}

function tvr_category_label( $key ) {
	$map = tvr_category_label_map();
	return isset( $map[ $key ] ) ? $map[ $key ] : $key;
}

function tvr_help_data() {
	return array(
		array(
			'category' => 'Compatibility',
			'items'    => array(
				array(
					'q' => 'Which TV brands are supported?',
					'a' => 'Universal TV Remote supports 1000+ brands including Samsung, LG, Sony, TCL, Hisense, Philips, Panasonic, Sharp, Vizio, Toshiba, Roku TV, Fire TV, Apple TV, Chromecast, and many more. Check the Supported TVs directory for the full list.',
				),
				array(
					'q' => 'Does the app work with Apple TV or streaming devices?',
					'a' => 'Yes. The app supports Apple TV, Roku TV, Amazon Fire TV, Chromecast, Nvidia Shield, Android TV, and Google TV in addition to traditional TV brands. Any device connected to the same Wi-Fi network can be controlled.',
				),
			),
		),
		array(
			'category' => 'Setup',
			'items'    => array(
				array(
					'q' => 'How do I set up the app with my TV?',
					'a' => "Download Universal TV Remote, open the app, tap Add TV, select your TV brand, and the app will automatically scan your Wi-Fi network to find your TV. Once found, tap it to connect. Most TVs pair within seconds. If your TV is not detected automatically, you can enter the TV's IP address manually.",
				),
				array(
					'q' => "Why isn't my TV showing up in the app?",
					'a' => "Make sure both your phone and TV are connected to the same Wi-Fi network — this is the most common reason a TV is not detected. Also check that your TV has network/IP control enabled in its settings (e.g. Samsung: Settings → General → External Device Manager; LG: Settings → All Settings → General → Mobile TV On). If the issue persists, try restarting both devices.",
				),
				array(
					'q' => 'What if my TV remote is lost or broken?',
					'a' => "Universal TV Remote is a perfect replacement for a lost or broken physical remote. Download the app, add your TV brand, and you'll have full remote control from your phone including power, volume, channels, inputs, and smart TV navigation — usually within a minute.",
				),
			),
		),
		array(
			'category' => 'Features',
			'items'    => array(
				array(
					'q' => 'Can I control multiple TVs with one app?',
					'a' => 'Yes. You can add as many TVs as you like. Switch between them instantly using the device selector at the top of the remote screen. Each TV remembers its settings independently.',
				),
				array(
					'q' => 'How do I use voice control?',
					'a' => 'Universal TV Remote supports Siri Shortcuts on iPhone. Go to Settings → Siri Shortcuts in the app and tap Add to Siri next to any action. You can then say things like "Hey Siri, mute the TV", "Hey Siri, turn on Netflix", or "Hey Siri, volume up" to control your TV hands-free.',
				),
				array(
					'q' => 'Can I customize the remote layout?',
					'a' => 'Yes. You can rearrange buttons, hide ones you never use, and add custom macro buttons that execute a sequence of commands in one tap — for example: power on → switch to HDMI 2 → open a specific app.',
				),
				array(
					'q' => 'Can I use the app without internet?',
					'a' => 'Yes, once your TV is added you do not need an internet connection to use the remote. Control works over your local Wi-Fi network. An internet connection is only needed to download the app or receive updates.',
				),
			),
		),
		array(
			'category' => 'Network & Connection',
			'items'    => array(
				array(
					'q' => 'Do I need Wi-Fi to use the app?',
					'a' => 'For most smart TVs, yes — both your phone and TV need to be on the same Wi-Fi network. This enables instant, low-latency control. Some older TVs can also be controlled via IR (infrared) if your device has IR hardware, which does not require Wi-Fi.',
				),
				array(
					'q' => 'How do I troubleshoot connection issues?',
					'a' => 'Start with the basics: confirm both devices are on the same Wi-Fi network, restart your TV and phone, and check that the TV has remote/IP control enabled in its settings. For more detailed steps, visit the Troubleshooting page.',
				),
			),
		),
		array(
			'category' => 'Privacy & Performance',
			'items'    => array(
				array(
					'q' => 'Does the app drain my iPhone battery?',
					'a' => 'No more than any other app used actively. The app only communicates with your TV when you press a button — it does not run background processes or maintain a persistent connection when not in use.',
				),
				array(
					'q' => 'Is my data secure?',
					'a' => 'Universal TV Remote does not collect or transmit personal data. All remote control communication happens entirely on your local network between your phone and TV. The app does not require an account and does not track usage.',
				),
				array(
					'q' => 'Is there a cost to use the app?',
					'a' => 'The app is free to download and covers basic remote control features. A premium subscription unlocks advanced features such as macros, custom button layouts, smart home integration, and priority support.',
				),
			),
		),
		array(
			'category' => 'Support',
			'items'    => array(
				array(
					'q' => 'How do I get help or report issues?',
					'a' => 'Use the Contact page to reach our support team. Please include your TV brand and model, your phone model, iOS/Android version, and a brief description of the issue. We aim to respond within one business day.',
				),
			),
		),
	);
}

function tvr_quick_fixes() {
	return array(
		array(
			'title' => 'Restart Both Devices',
			'desc'  => 'Turn off your TV and phone, wait 30 seconds, then turn them back on. This resolves 70% of connection issues.',
		),
		array(
			'title' => 'Check Wi-Fi Connection',
			'desc'  => 'Ensure both devices are connected to the same Wi-Fi network. Different networks prevent communication.',
		),
		array(
			'title' => 'Update the App',
			'desc'  => 'Check the App Store or Google Play for updates. Newer versions often fix bugs and add support for more TV models.',
		),
		array(
			'title' => 'Reset Network Settings',
			'desc'  => 'On iPhone go to Settings → General → Transfer or Reset iPhone → Reset → Reset Network Settings if connection issues persist.',
		),
	);
}

function tvr_issues() {
	return array(
		array(
			'title'      => 'TV Not Detected',
			'difficulty' => 'Easy',
			'time'       => '2–5 minutes',
			'causes'     => array(
				'TV and phone on different Wi-Fi networks',
				"TV's network features disabled in settings",
				'Router has AP isolation or device isolation enabled',
				"TV is too old and doesn't support network control",
			),
			'solutions'  => array(
				'Verify both devices are connected to the same Wi-Fi network',
				"Enable network features in TV's settings menu",
				'Check router settings and disable AP / device isolation',
				"Try manual setup by entering your TV's IP address",
				"Ensure TV's firmware is up to date",
			),
		),
		array(
			'title'      => 'Commands Not Working',
			'difficulty' => 'Medium',
			'time'       => '3–10 minutes',
			'causes'     => array(
				'Connection lost between phone and TV',
				'TV in special mode (HDMI-CEC disabled)',
				'Incorrect TV brand or model selected in app',
				"TV's remote control feature is disabled",
			),
			'solutions'  => array(
				'Restart both TV and phone',
				'Reconnect to TV in the app',
				'Verify correct TV brand and model is selected',
				'Check if HDMI-CEC is enabled on your TV',
				'Try removing and re-adding the TV in the app',
				'Ensure no other devices are controlling the TV simultaneously',
			),
		),
		array(
			'title'      => 'App Crashes or Freezes',
			'difficulty' => 'Easy',
			'time'       => '2–5 minutes',
			'causes'     => array(
				'App needs update to latest version',
				'Phone storage is full or nearly full',
				'Corrupted app data or cache',
				'iOS / Android version is outdated or incompatible',
			),
			'solutions'  => array(
				'Update app to latest version from App Store / Google Play',
				'Free up storage space on your phone (at least 1 GB free recommended)',
				'Delete and reinstall app to clear corrupted data',
				'Update your phone to the latest OS version',
				'Restart phone to clear temporary issues',
			),
		),
		array(
			'title'      => 'Slow or Delayed Response',
			'difficulty' => 'Medium',
			'time'       => '5–10 minutes',
			'causes'     => array(
				'Weak Wi-Fi signal or network congestion',
				'Too many devices connected to network',
				'Router is far from TV or phone',
				'Network interference from other devices',
			),
			'solutions'  => array(
				'Move closer to your Wi-Fi router',
				'Reduce the number of devices on the network',
				'Switch to a less congested Wi-Fi channel on your router',
				'Use 5 GHz Wi-Fi instead of 2.4 GHz if available',
				'Restart router to clear network congestion',
				'Check for firmware updates for your router',
			),
		),
	);
}

function tvr_advanced_troubleshooting() {
	return array(
		array(
			'category' => 'Network Configuration',
			'steps'    => array(
				'Assign a static IP address to your TV in router settings',
				'Disable VPN on your phone while using the app',
				'Configure router firewall to allow local network communication',
				'Enable UPnP (Universal Plug and Play) on your router',
				'Check if MAC address filtering is blocking devices',
			),
		),
		array(
			'category' => 'TV-Specific Settings',
			'steps'    => array(
				"Enable 'Mobile' or 'Remote App' feature in TV settings",
				'Turn on HDMI-CEC or Anynet+ (Samsung) / Simplink (LG)',
				"Disable 'Quick Start' or 'Instant On' mode temporarily",
				"Reset TV's Smart Hub or network settings",
				"Check your TV manufacturer's app for additional requirements",
			),
		),
		array(
			'category' => 'Phone Settings',
			'steps'    => array(
				"Ensure 'Local Network' permission is enabled for the app",
				"Disable 'Low Power Mode' which limits network activity",
				"Turn off 'Private Wi-Fi Address' for your home network",
				"Check that your phone's firewall isn't blocking the app",
				'Reset all settings on your phone if problems persist',
			),
		),
	);
}

// Setup guides — ported from data/guides.json (documented in the original
// README as a real route; not wired up in the Next.js app we ported from, but
// the data is real and self-contained so we give it a page here).
function tvr_guides() {
	static $guides = null;
	if ( $guides === null ) {
		$path   = get_template_directory() . '/data/guides.json';
		$json   = file_exists( $path ) ? file_get_contents( $path ) : '[]';
		// Strip a leading UTF-8 BOM if present — json_decode() fails silently
		// (returns null, no warning) on one, which previously made this
		// function return an empty array with no visible error.
		$json   = preg_replace( '/^\xEF\xBB\xBF/', '', $json );
		$guides = json_decode( $json, true );
		if ( ! is_array( $guides ) ) $guides = array();
	}
	return $guides;
}
