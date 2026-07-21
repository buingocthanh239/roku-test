<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$home_id = (int) get_option( 'tvr_home_page_id' );

$hero_title    = tvr_field( 'hero_title', $home_id, 'Universal TV Remote' );
$hero_subtitle = tvr_field( 'hero_subtitle', $home_id, 'Turn your iPhone into a powerful remote control for 1000+ TV brands — Samsung, LG, Sony, Roku, and more.' );
$stars_caption = tvr_field( 'stars_caption', $home_id, 'Loved by 1M+ users worldwide!' );
$marquee       = tvr_lines( tvr_field( 'marquee_brands', $home_id, '' ) );
if ( ! $marquee ) $marquee = array( 'Samsung', 'LG', 'Sony', 'TCL', 'Hisense', 'Philips', 'Roku TV', 'Fire TV', 'Apple TV', 'Panasonic', 'Sharp', 'Vizio' );

$works_heading       = tvr_field( 'works_heading', $home_id, 'Works with Your TV Brand' );
$works_subheading    = tvr_field( 'works_subheading', $home_id, 'Instant Wi-Fi control for the world most popular smart TVs and streaming devices' );
$features_heading    = tvr_field( 'features_heading', $home_id, 'Features' );
$features_subheading = tvr_field( 'features_subheading', $home_id, 'Everything you need to control your TV from your iPhone' );
$reviews_heading      = tvr_field( 'reviews_heading', $home_id, 'What people say' );
$reviews_subheading   = tvr_field( 'reviews_subheading', $home_id, 'Feedback from people who use Universal TV Remote every day' );
$compare_heading   = tvr_field( 'compare_heading', $home_id, 'Why Universal TV Remote' );
$competitor_1_name = tvr_field( 'compare_competitor_1_name', $home_id, 'TVRemote+' );
$competitor_2_name = tvr_field( 'compare_competitor_2_name', $home_id, 'AnyMote' );
$cta_heading    = tvr_field( 'cta_heading', $home_id, 'Download Universal TV Remote' );
$cta_subheading = tvr_field( 'cta_subheading', $home_id, 'The easiest way to control your TV from your iPhone — 1000+ brands, zero setup headaches.' );

$features = tvr_get_content_posts( 'home_feature' );
$reviews  = tvr_get_content_posts( 'home_review' );
$compare  = tvr_get_content_posts( 'compare_row' );

tvr_json_ld( tvr_software_application_ld() );
?>

<section class="bg-white">
	<div class="mx-auto max-w-5xl px-4 pt-14 pb-6 text-center sm:pt-20">
		<img src="<?php echo esc_url( tvr_asset( 'logo.png' ) ); ?>" alt="Universal TV Remote logo" class="animate-fade-up mx-auto mb-5 h-20 w-20" />
		<h1 class="animate-fade-up text-4xl font-extrabold tracking-tight text-navy-900 sm:text-6xl" style="--reveal-delay:80ms"><?php echo esc_html( $hero_title ); ?></h1>
		<p class="animate-fade-up mx-auto mt-4 max-w-2xl text-lg font-light text-slate-500 sm:text-xl" style="--reveal-delay:160ms">
			<?php echo esc_html( $hero_subtitle ); ?>
		</p>
		<div class="animate-fade-up mt-9 flex justify-center" style="--reveal-delay:240ms">
			<?php get_template_part( 'template-parts/global/store-badges', null, array( 'light' => true ) ); ?>
		</div>
		<div class="animate-fade-up mt-8 flex flex-col items-center" style="--reveal-delay:320ms">
			<?php echo tvr_stars_svg( '[&_svg]:h-8 [&_svg]:w-8' ); ?>
			<div class="mt-3">
				<span class="text-4xl font-bold text-navy-900">4.8</span>
				<span class="ml-1 text-lg text-slate-500">out of 5</span>
			</div>
			<p class="mt-2 text-sm font-medium text-slate-600"><?php echo esc_html( $stars_caption ); ?></p>
		</div>
	</div>
	<img src="<?php echo esc_url( tvr_asset( 'app-devices.webp' ) ); ?>" alt="Universal TV Remote on iPhone" width="4172" height="2856" fetchpriority="high" class="mx-auto w-full max-w-3xl px-4 pb-8" />
</section>

<section class="border-t border-slate-100 bg-slate-50 py-14">
	<div class="mx-auto max-w-5xl px-4 text-center">
		<?php get_template_part( 'template-parts/global/section-head', null, array(
			'title'    => $works_heading,
			'subtitle' => $works_subheading,
		) ); ?>
		<div class="overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_12%,black_88%,transparent)]">
			<div class="marquee-track">
				<?php foreach ( array_merge( $marquee, $marquee ) as $brand ) : ?>
					<span class="mx-8 text-base font-semibold text-slate-500 opacity-70 whitespace-nowrap"><?php echo esc_html( $brand ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="reveal" style="--reveal-delay:200ms">
			<a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" class="mt-8 inline-block rounded-full bg-brand-500 px-7 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600">
				Browse all 1000+ Supported TVs &rarr;
			</a>
		</div>
	</div>
</section>

<section id="features" class="mx-auto max-w-6xl px-4 py-20">
	<?php get_template_part( 'template-parts/global/section-head', null, array(
		'title'    => $features_heading,
		'subtitle' => $features_subheading,
	) ); ?>
	<div class="-mx-4 flex snap-x snap-mandatory gap-5 overflow-x-auto px-4 pb-4 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-3">
		<?php foreach ( $features as $i => $f ) : ?>
			<div style="--reveal-delay:<?php echo (int) ( ( $i % 3 ) * 100 ); ?>ms" class="reveal w-[78vw] max-w-xs shrink-0 snap-start rounded-2xl border border-slate-200 bg-white p-6 transition duration-300 hover:-translate-y-1 hover:shadow-lg sm:w-auto sm:max-w-none">
				<img src="<?php echo esc_url( tvr_asset( 'features/' . get_field( 'icon', $f->ID ) . '.svg' ) ); ?>" alt="" class="mb-4 h-12 w-12" />
				<h3 class="text-lg font-semibold text-navy-900"><?php echo esc_html( $f->post_title ); ?></h3>
				<p class="mt-2 text-sm text-slate-600"><?php echo esc_html( get_field( 'description', $f->ID ) ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<section class="bg-slate-50 py-20">
	<div class="mx-auto max-w-6xl px-4">
		<?php get_template_part( 'template-parts/global/section-head', null, array(
			'title'    => $reviews_heading,
			'subtitle' => $reviews_subheading,
		) ); ?>
		<div class="-mx-4 flex snap-x snap-mandatory gap-5 overflow-x-auto px-4 pb-4 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-3">
			<?php foreach ( $reviews as $i => $r ) : ?>
				<figure style="--reveal-delay:<?php echo (int) ( ( $i % 3 ) * 100 ); ?>ms" class="reveal w-[78vw] max-w-xs shrink-0 snap-start flex flex-col rounded-2xl border border-slate-200 bg-white p-6 transition duration-300 hover:-translate-y-1 hover:shadow-lg sm:w-auto sm:max-w-none">
					<?php echo tvr_stars_svg(); ?>
					<figcaption class="mt-3 font-bold text-navy-900"><?php echo esc_html( $r->post_title ); ?></figcaption>
					<blockquote class="mt-2 flex-1 text-sm font-light leading-relaxed text-slate-600"><?php echo esc_html( get_field( 'body', $r->ID ) ); ?></blockquote>
					<div class="mt-4 flex items-center justify-between">
						<span class="text-xs font-medium text-slate-400">&mdash; <?php echo esc_html( get_field( 'author', $r->ID ) ); ?></span>
						<span class="text-xs text-slate-400"><?php echo esc_html( get_field( 'date', $r->ID ) ); ?></span>
					</div>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="bg-white py-20">
	<div class="mx-auto max-w-4xl px-4">
		<?php get_template_part( 'template-parts/global/section-head', null, array( 'title' => $compare_heading ) ); ?>
		<div class="reveal overflow-hidden rounded-2xl border border-slate-200">
			<table class="w-full text-sm">
				<thead>
					<tr class="border-b border-slate-200 bg-slate-50">
						<th class="p-4 text-left font-medium text-slate-500" style="width:40%">Feature</th>
						<th class="p-4 text-center align-bottom">
							<img src="<?php echo esc_url( tvr_asset( 'logo.png' ) ); ?>" alt="" class="mx-auto h-10 w-10 rounded-lg" />
							<span class="mt-2 block text-xs font-semibold text-brand-600">Universal TV Remote</span>
						</th>
						<th class="p-4 text-center align-bottom"><span class="mt-2 block text-xs font-medium text-slate-500"><?php echo esc_html( $competitor_1_name ); ?></span></th>
						<th class="p-4 text-center align-bottom"><span class="mt-2 block text-xs font-medium text-slate-500"><?php echo esc_html( $competitor_2_name ); ?></span></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-100">
					<?php foreach ( $compare as $row ) : ?>
						<tr>
							<td class="p-4 text-left align-top">
								<div class="font-medium text-navy-900"><?php echo esc_html( $row->post_title ); ?></div>
								<div class="mt-1 text-xs font-light leading-relaxed text-slate-500"><?php echo esc_html( get_field( 'description', $row->ID ) ); ?></div>
							</td>
							<td class="p-4 align-top"><?php echo tvr_check_svg( (bool) get_field( 'us', $row->ID ) ); ?></td>
							<td class="p-4 align-top"><?php echo tvr_check_svg( (bool) get_field( 'competitor_1', $row->ID ) ); ?></td>
							<td class="p-4 align-top"><?php echo tvr_check_svg( (bool) get_field( 'competitor_2', $row->ID ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</section>

<section class="mx-auto mb-4 max-w-6xl px-4">
	<div class="reveal relative overflow-hidden rounded-3xl bg-navy-900 px-8 py-14 text-white">
		<div class="pointer-events-none absolute -top-24 left-1/2 h-64 w-64 -translate-x-1/2 rounded-full bg-brand-500 opacity-20 blur-3xl"></div>
		<div class="relative flex flex-col items-center gap-10 sm:flex-row sm:items-center sm:justify-between sm:gap-12">
			<div class="flex flex-col items-center text-center sm:items-start sm:text-left">
				<img src="<?php echo esc_url( tvr_asset( 'logo.png' ) ); ?>" alt="Universal TV Remote" class="mb-5 h-16 w-16 rounded-2xl shadow-lg" />
				<h2 class="text-3xl font-bold tracking-tight sm:text-4xl"><?php echo esc_html( $cta_heading ); ?></h2>
				<p class="mt-3 max-w-sm font-light text-slate-300"><?php echo esc_html( $cta_subheading ); ?></p>
			</div>
			<div class="flex-shrink-0">
				<?php get_template_part( 'template-parts/global/store-badges', null, array( 'qr' => true, 'center' => true ) ); ?>
			</div>
		</div>
	</div>
</section>

<?php get_footer(); ?>
