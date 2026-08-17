<?php
/**
 * Blog archive/listing fallback (also used for /blog/ when a "Posts page"
 * is assigned in Settings -> Reading, and as the generic search-results
 * fallback since the theme has no search.php). Renders teaser cards + a
 * pagination control — full single-post rendering now lives in single.php.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$posts_page_id = (int) get_option( 'page_for_posts' );
$archive_title = is_search()
	? sprintf( 'Search results for "%s"', get_search_query() )
	: ( $posts_page_id ? get_the_title( $posts_page_id ) : 'Blog' );
?>
<div class="mx-auto max-w-6xl px-4 py-16 lg:max-w-318">
	<div class="mb-10 text-center">
		<h1 class="text-3xl font-extrabold tracking-tight text-navy-900 sm:text-4xl"><?php echo esc_html( $archive_title ); ?></h1>
		<?php if ( ! is_search() ) : ?>
			<p class="mt-3 text-lg font-light text-slate-500">Setup tips, troubleshooting guides, and news from the Universal TV Remote team.</p>
		<?php endif; ?>
	</div>

	<?php if ( have_posts() ) : ?>
		<?php
		// First post on the unpaged/non-search view gets a wide horizontal
		// "Featured" banner (image left, info right) above the regular grid.
		$show_featured = ! is_search() && ! is_paged();
		$grid_open     = false;
		$i             = 0;
		?>
		<?php while ( have_posts() ) : the_post();
			$categories = get_the_category();
		?>
			<?php if ( $show_featured && 0 === $i ) : ?>
				<article class="animate-fade-up group mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white transition duration-300 hover:-translate-y-1 hover:shadow-lg sm:flex sm:items-stretch">
					<a href="<?php the_permalink(); ?>" class="block aspect-video w-full shrink-0 overflow-hidden bg-slate-100 sm:aspect-auto sm:w-1/2">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'large', array( 'class' => 'h-full w-full object-cover transition duration-300 group-hover:scale-105' ) ); ?>
						<?php else : ?>
							<img src="<?php echo esc_url( tvr_asset( 'logo.webp' ) ); ?>" alt="" class="h-full w-full object-contain p-10 opacity-30" />
						<?php endif; ?>
					</a>
					<div class="flex flex-1 flex-col justify-center p-6 sm:p-8">
						<div class="flex items-center gap-2 text-xs font-medium">
							<span class="rounded-full bg-brand-50 px-2.5 py-1 text-brand-600">Featured</span>
							<?php if ( $categories ) : ?>
								<span class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wide <?php echo esc_attr( tvr_category_badge_color( $categories[0]->name ) ); ?>"><?php echo esc_html( $categories[0]->name ); ?></span>
							<?php endif; ?>
						</div>
						<h2 class="mt-3 text-2xl font-semibold leading-snug text-navy-900">
							<a href="<?php the_permalink(); ?>" class="hover:text-brand-600"><?php the_title(); ?></a>
						</h2>
						<p class="mt-2 text-sm leading-relaxed text-slate-600 line-clamp-3"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
						<div class="mt-4 flex items-center gap-1.5 text-xs text-slate-400">
							<svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="10" cy="10" r="7.25" /><path stroke-linecap="round" d="M10 6v4l2.5 1.5" /></svg>
							<span><?php echo (int) tvr_reading_time( get_the_ID() ); ?> min</span>
							<span>&middot;</span>
							<span><?php echo esc_html( get_the_date( 'M j' ) ); ?></span>
						</div>
					</div>
				</article>
			<?php else : ?>
				<?php if ( ! $grid_open ) : $grid_open = true; ?>
				<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
				<?php endif; ?>
				<article class="reveal group overflow-hidden rounded-2xl border border-slate-200 bg-white transition duration-300 hover:-translate-y-1 hover:shadow-lg">
					<a href="<?php the_permalink(); ?>" class="block aspect-video w-full overflow-hidden bg-slate-100">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'large', array( 'class' => 'h-full w-full object-cover transition duration-300 group-hover:scale-105' ) ); ?>
						<?php else : ?>
							<img src="<?php echo esc_url( tvr_asset( 'logo.webp' ) ); ?>" alt="" class="h-full w-full object-contain p-10 opacity-30" />
						<?php endif; ?>
					</a>
					<div class="p-5">
						<?php if ( $categories ) : ?>
							<span class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wide <?php echo esc_attr( tvr_category_badge_color( $categories[0]->name ) ); ?>"><?php echo esc_html( $categories[0]->name ); ?></span>
						<?php endif; ?>
						<h2 class="mt-3 text-lg font-semibold leading-snug text-navy-900 line-clamp-2">
							<a href="<?php the_permalink(); ?>" class="hover:text-brand-600"><?php the_title(); ?></a>
						</h2>
						<div class="mt-3 flex items-center gap-1.5 text-xs text-slate-400">
							<svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="10" cy="10" r="7.25" /><path stroke-linecap="round" d="M10 6v4l2.5 1.5" /></svg>
							<span><?php echo (int) tvr_reading_time( get_the_ID() ); ?> min</span>
							<span>&middot;</span>
							<span><?php echo esc_html( get_the_date( 'M j' ) ); ?></span>
						</div>
					</div>
				</article>
			<?php endif; $i++; endwhile; ?>
		<?php if ( $grid_open ) : ?>
			</div>
		<?php endif; ?>

		<?php
		the_posts_pagination( array(
			'mid_size'           => 1,
			'prev_text'          => '&larr; Newer',
			'next_text'          => 'Older &rarr;',
			'class'              => 'tvr-pagination',
			'screen_reader_text' => 'Blog pagination',
		) );
		?>
	<?php else : ?>
		<p class="text-center text-slate-600">Nothing found.</p>
	<?php endif; ?>
</div>
<?php get_footer(); ?>
