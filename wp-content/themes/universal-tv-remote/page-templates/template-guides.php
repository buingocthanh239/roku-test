<?php
/**
 * Template Name: Setup Guides
 * Content sourced from the `guide` CPT (editable in wp-admin, including the
 * step list as a WYSIWYG field) + this page's ACF fields.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$page_id    = get_the_ID();
$heading    = tvr_field( 'heading', $page_id, 'Setup Guides' );
$subheading = tvr_field( 'subheading', $page_id, 'Step-by-step instructions for connecting Universal TV Remote to your TV, brand by brand.' );

$guides = tvr_get_content_posts( 'guide' );
?>

<div class="mx-auto max-w-3xl px-4 py-12">
	<div class="mb-10">
		<h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl"<?php echo tvr_heading_style_attr( $page_id ); ?>><?php echo esc_html( $heading ); ?></h1>
		<p class="mt-3 text-slate-500"><?php echo esc_html( $subheading ); ?></p>
	</div>

	<div class="space-y-8">
		<?php foreach ( $guides as $guide ) :
			$domain     = get_field( 'domain', $guide->ID );
			$updated    = get_field( 'updated', $guide->ID );
			$steps_html = get_field( 'steps', $guide->ID );
			$brand_post = $domain ? tvr_get_brand_by_domain( $domain ) : null;
			?>
			<section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
				<div class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-5">
					<h2 class="text-lg font-bold text-slate-900"><?php echo esc_html( $guide->post_title ); ?></h2>
					<?php if ( $updated ) : ?>
						<span class="text-xs text-slate-400">Updated <?php echo esc_html( $updated ); ?></span>
					<?php endif; ?>
				</div>
				<div class="tvr-guide-steps px-6 py-5">
					<?php echo wp_kses_post( $steps_html ); ?>
				</div>
				<?php if ( $brand_post ) : ?>
					<div class="border-t border-slate-100 px-6 py-4">
						<a href="<?php echo esc_url( get_permalink( $brand_post ) ); ?>" class="text-sm font-medium text-brand-600 hover:text-brand-700"><?php echo esc_html( $guide->post_title ); ?> TV details &rarr;</a>
					</div>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>
	</div>
</div>

<?php get_footer(); ?>
