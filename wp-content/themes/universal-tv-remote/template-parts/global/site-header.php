<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<header class="safe-top sticky top-0 z-40 border-b border-slate-200 bg-white/80 backdrop-blur">
	<div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2.5">
			<?php if ( has_custom_logo() ) : ?>
				<span class="h-8 w-8 [&_img]:h-full [&_img]:w-full [&_img]:object-contain">
					<?php the_custom_logo(); ?>
				</span>
			<?php else : ?>
				<img src="<?php echo esc_url( tvr_asset( 'logo.png' ) ); ?>" alt="<?php echo esc_attr( TVR_SITE_NAME ); ?>" class="h-8 w-8" />
			<?php endif; ?>
			<span class="flex flex-col leading-none">
				<span class="text-lg font-bold tracking-tight text-slate-900"><?php echo esc_html( TVR_SITE_NAME ); ?></span>
				<span class="text-[11px] font-medium text-slate-400"><?php echo esc_html( get_theme_mod( 'tvr_site_tagline', '1000+ TV brands' ) ); ?></span>
			</span>
		</a>

		<nav class="hidden items-center gap-8 md:flex">
			<?php tvr_primary_nav(); ?>
			<a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" class="rounded-full bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600">
				Browse Supported TVs
			</a>
		</nav>

		<button id="tvr-menu-toggle" class="-mr-2 flex h-11 w-11 items-center justify-center rounded-lg md:hidden" aria-label="Toggle menu" aria-expanded="false">
			<svg class="h-6 w-6 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path id="tvr-menu-icon-open" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
				<path id="tvr-menu-icon-close" stroke-linecap="round" d="M6 6l12 12M18 6 6 18" class="hidden" />
			</svg>
		</button>
	</div>

	<div id="tvr-mobile-nav" class="hidden border-t border-slate-200 bg-white md:hidden">
		<nav class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-3">
			<a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" class="rounded-lg px-3 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100">Supported TVs</a>
			<a href="<?php echo esc_url( home_url( '/troubleshooting/' ) ); ?>" class="rounded-lg px-3 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100">Troubleshooting</a>
			<a href="<?php echo esc_url( home_url( '/help/' ) ); ?>" class="rounded-lg px-3 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100">Help</a>
			<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rounded-lg px-3 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100">Contact</a>
		</nav>
	</div>
</header>
