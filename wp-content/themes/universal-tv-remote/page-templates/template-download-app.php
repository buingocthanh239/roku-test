<?php
/**
 * Template Name: Download App
 * Port of app/download-app/page.jsx — content sourced from the
 * `download_reason` CPT + this page's ACF fields, editable in wp-admin.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$page_id = get_the_ID();
$hero_title         = tvr_field( 'hero_title', $page_id, 'Download ' . TVR_SITE_NAME );
$hero_subtitle       = tvr_field( 'hero_subtitle', $page_id, 'Turn your iPhone into a remote for your smart TV. Control Samsung, LG, Sony, Roku, TCL, and Hisense TVs over Wi-Fi — no extra hardware, no lost remote.' );
$reasons_heading      = tvr_field( 'reasons_heading', $page_id, 'Why download ' . TVR_SITE_NAME );
$reasons_subheading   = tvr_field( 'reasons_subheading', $page_id, 'One app to control all of your smart TVs from your phone' );
$cta_heading          = tvr_field( 'cta_heading', $page_id, 'Get ' . TVR_SITE_NAME . ' today' );
$cta_subheading       = tvr_field( 'cta_subheading', $page_id, 'Download for free and control every smart TV in your home from your iPhone — Samsung, LG, Sony, Roku, TCL, and Hisense.' );

$reasons = tvr_get_content_posts( 'download_reason' );

tvr_json_ld( tvr_software_application_ld( '/download-app/#app', 'iOS, macOS' ) );
?>

<section class="bg-white">
	<div class="mx-auto max-w-5xl px-4 pt-14 pb-6 text-center sm:pt-20">
		<img src="<?php echo esc_url( tvr_asset( 'logo.png' ) ); ?>" alt="<?php echo esc_attr( TVR_SITE_NAME ); ?> app logo" class="mx-auto mb-5 h-20 w-20" />
		<h1 class="text-4xl font-extrabold tracking-tight text-navy-900 sm:text-5xl"><?php echo esc_html( $hero_title ); ?></h1>
		<p class="mx-auto mt-4 max-w-2xl text-lg font-light text-slate-500 sm:text-xl"><?php echo esc_html( $hero_subtitle ); ?></p>
		<div class="mt-9 flex justify-center">
			<?php get_template_part( 'template-parts/global/store-badges', null, array( 'light' => true ) ); ?>
		</div>
	</div>
	<img src="<?php echo esc_url( tvr_asset( 'app-devices.webp' ) ); ?>" alt="<?php echo esc_attr( TVR_SITE_NAME ); ?> on iPhone and Mac" width="1600" height="1120" fetchpriority="high" class="mx-auto w-full max-w-3xl px-4 pb-8" />
</section>

<section class="mx-auto max-w-6xl px-4 py-20">
	<?php get_template_part( 'template-parts/global/section-head', null, array(
		'title'    => $reasons_heading,
		'subtitle' => $reasons_subheading,
	) ); ?>
	<div class="grid gap-6 sm:grid-cols-2">
		<?php foreach ( $reasons as $i => $r ) : ?>
			<div style="--reveal-delay:<?php echo (int) ( ( $i % 2 ) * 100 ); ?>ms" class="reveal rounded-2xl border border-slate-200 bg-white p-6 transition duration-300 hover:-translate-y-1 hover:shadow-lg">
				<img src="<?php echo esc_url( tvr_asset( 'features/' . get_field( 'icon', $r->ID ) . '.svg' ) ); ?>" alt="" class="mb-4 h-12 w-12" />
				<h3 class="text-lg font-semibold text-navy-900"><?php echo esc_html( $r->post_title ); ?></h3>
				<p class="mt-2 text-sm text-slate-600"><?php echo esc_html( get_field( 'description', $r->ID ) ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<section class="mx-auto mb-4 max-w-6xl px-4">
	<div class="reveal relative overflow-hidden rounded-3xl bg-navy-900 px-6 py-10 text-white sm:px-8 sm:py-14">
		<div class="pointer-events-none absolute -top-24 left-1/2 h-64 w-64 -translate-x-1/2 rounded-full bg-brand-500 opacity-20 blur-3xl"></div>
		<div class="relative flex flex-col items-center gap-8 sm:flex-row sm:items-center sm:justify-between sm:gap-12">
			<div class="flex flex-col items-center text-center sm:items-start sm:text-left">
				<h2 class="text-2xl font-bold tracking-tight sm:text-4xl"><?php echo esc_html( $cta_heading ); ?></h2>
				<p class="mt-3 max-w-sm font-light text-slate-300"><?php echo esc_html( $cta_subheading ); ?></p>
			</div>
			<div class="w-full flex-shrink-0 sm:w-auto">
				<?php get_template_part( 'template-parts/global/store-badges', null, array( 'qr' => true, 'center' => true ) ); ?>
			</div>
		</div>
	</div>
</section>

<?php get_footer(); ?>
