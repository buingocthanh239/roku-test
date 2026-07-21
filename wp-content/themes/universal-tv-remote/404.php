<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<div class="mx-auto flex max-w-2xl flex-col items-center px-4 py-28 text-center">
	<span class="text-6xl font-extrabold text-brand-600">404</span>
	<h1 class="mt-4 text-2xl font-bold text-slate-900">Page not found</h1>
	<p class="mt-2 text-slate-600">We couldn't find what you were looking for.</p>
	<a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" class="mt-6 rounded-full bg-brand-600 px-6 py-3 font-semibold text-white transition hover:bg-brand-700">
		Browse supported TVs
	</a>
</div>
<?php get_footer(); ?>
