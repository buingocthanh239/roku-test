<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$tvr_links = tvr_app_links();
$tvr_total = wp_count_posts( 'tv_brand' )->publish;

$tvr_blurb = get_theme_mod( 'tvr_footer_blurb' );
if ( ! $tvr_blurb ) {
	$tvr_blurb = sprintf(
		'A directory of %s TV brands supported by Universal TV Remote — the number one remote control app for iPhone.',
		number_format_i18n( $tvr_total )
	);
}
$tvr_privacy_url = get_theme_mod( 'tvr_privacy_url' );
$tvr_terms_url   = get_theme_mod( 'tvr_terms_url' );
$tvr_copyright   = get_theme_mod( 'tvr_copyright_text' );
if ( ! $tvr_copyright ) {
	$tvr_copyright = date_i18n( 'Y' ) . ' ' . TVR_SITE_NAME . '. All rights reserved.';
}
?>
<footer class="mt-20 bg-navy-900 text-slate-300">
	<div class="mx-auto max-w-6xl px-4 py-10 sm:py-14">
		<div class="grid gap-8 lg:grid-cols-4">
			<div class="lg:col-span-1">
				<div class="mb-3 flex items-center gap-2.5">
					<?php if ( has_custom_logo() ) : ?>
						<span class="h-8 w-8 [&_img]:h-full [&_img]:w-full [&_img]:object-contain">
							<?php the_custom_logo(); ?>
						</span>
					<?php else : ?>
						<img src="<?php echo esc_url( tvr_asset( 'logo.png' ) ); ?>" alt="<?php echo esc_attr( TVR_SITE_NAME ); ?>" class="h-8 w-8" />
					<?php endif; ?>
					<span class="flex flex-col leading-none">
						<span class="text-base font-bold text-white"><?php echo esc_html( TVR_SITE_NAME ); ?></span>
						<span class="text-[11px] font-medium text-slate-400"><?php echo esc_html( get_theme_mod( 'tvr_site_tagline', '1000+ TV brands' ) ); ?></span>
					</span>
				</div>
				<p class="text-sm text-slate-400"><?php echo esc_html( $tvr_blurb ); ?></p>
			</div>
			<div class="grid grid-cols-3 gap-4 lg:col-span-3 lg:grid-cols-3">
				<div>
					<h4 class="mb-3 text-sm font-semibold text-white"><?php echo esc_html( get_theme_mod( 'tvr_footer_company_heading', 'Company' ) ); ?></h4>
					<ul class="space-y-1 text-sm text-slate-400">
						<li><a href="#" class="block py-1 hover:text-brand-300">About</a></li>
						<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="block py-1 hover:text-brand-300">Contact</a></li>
					</ul>
				</div>
				<div>
					<h4 class="mb-3 text-sm font-semibold text-white"><?php echo esc_html( get_theme_mod( 'tvr_footer_resources_heading', 'Resources' ) ); ?></h4>
					<ul class="space-y-1 text-sm text-slate-400">
						<li><a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" class="block py-1 hover:text-brand-300">Supported TVs</a></li>
						<li><a href="<?php echo esc_url( home_url( '/troubleshooting/' ) ); ?>" class="block py-1 hover:text-brand-300">Troubleshooting</a></li>
						<li><a href="<?php echo esc_url( home_url( '/help/' ) ); ?>" class="block py-1 hover:text-brand-300">Help</a></li>
					</ul>
				</div>
				<div>
					<h4 class="mb-3 text-sm font-semibold text-white"><?php echo esc_html( get_theme_mod( 'tvr_footer_downloads_heading', 'Downloads' ) ); ?></h4>
					<ul class="space-y-1 text-sm text-slate-400">
						<li><a href="<?php echo esc_url( $tvr_links['ios'] ); ?>" target="_blank" rel="noopener noreferrer" class="block py-1 hover:text-brand-300">iPhone / iPad</a></li>
						<li><a href="<?php echo esc_url( home_url( '/download-app/' ) ); ?>" class="block py-1 hover:text-brand-300">Download App</a></li>
					</ul>
				</div>
			</div>
		</div>
		<div class="mt-8 flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 text-sm text-slate-400 sm:flex-row">
			<?php if ( $tvr_privacy_url || $tvr_terms_url ) : ?>
				<div class="flex items-center gap-5">
					<?php if ( $tvr_privacy_url ) : ?>
						<a href="<?php echo esc_url( $tvr_privacy_url ); ?>" class="hover:text-brand-300">Privacy Policy</a>
					<?php endif; ?>
					<?php if ( $tvr_terms_url ) : ?>
						<a href="<?php echo esc_url( $tvr_terms_url ); ?>" class="hover:text-brand-300">Terms of Use</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<p>&copy; <?php echo esc_html( $tvr_copyright ); ?></p>
		</div>
	</div>
</footer>
