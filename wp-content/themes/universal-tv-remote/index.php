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
$post_index = 0;
?>
<div class="mx-auto max-w-3xl px-4 py-16">
	<div class="mb-10 text-center">
		<h1 class="text-3xl font-extrabold tracking-tight text-navy-900 sm:text-4xl"><?php echo esc_html( $archive_title ); ?></h1>
		<?php if ( ! is_search() ) : ?>
			<p class="mt-3 text-lg font-light text-slate-500">Setup tips, troubleshooting guides, and news from the Universal TV Remote team.</p>
		<?php endif; ?>
	</div>

	<?php if ( have_posts() ) : ?>
		<div class="flex flex-col gap-6">
			<?php while ( have_posts() ) : the_post();
				$is_featured = ! is_search() && ! is_paged() && $post_index === 0;
				$categories  = get_the_category();
			?>
				<article class="reveal group overflow-hidden rounded-2xl border border-slate-200 bg-white transition duration-300 hover:-translate-y-1 hover:shadow-lg <?php echo $is_featured ? 'flex flex-col' : 'flex flex-col sm:flex-row sm:items-stretch'; ?>">
					<a href="<?php the_permalink(); ?>" class="block shrink-0 overflow-hidden bg-slate-100 <?php echo $is_featured ? 'aspect-[16/9] w-full' : 'aspect-[16/9] w-full sm:aspect-auto sm:h-auto sm:w-56'; ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'large', array( 'class' => 'h-full w-full object-cover transition duration-300 group-hover:scale-105' ) ); ?>
						<?php else : ?>
							<img src="<?php echo esc_url( tvr_asset( 'logo.png' ) ); ?>" alt="" class="h-full w-full object-contain p-10 opacity-30" />
						<?php endif; ?>
					</a>
					<div class="flex flex-1 flex-col justify-center p-5 sm:p-6">
						<div class="flex items-center gap-2 text-xs font-medium">
							<?php if ( $is_featured ) : ?>
								<span class="rounded-full bg-brand-50 px-2.5 py-1 text-brand-600">Featured</span>
							<?php endif; ?>
							<?php if ( $categories ) : ?>
								<span class="text-brand-600"><?php echo esc_html( $categories[0]->name ); ?></span>
								<span class="text-slate-300">&middot;</span>
							<?php endif; ?>
							<span class="text-slate-400"><?php echo esc_html( get_the_date() ); ?></span>
						</div>
						<h2 class="<?php echo $is_featured ? 'mt-3 text-2xl' : 'mt-2 text-lg'; ?> font-semibold text-navy-900">
							<a href="<?php the_permalink(); ?>" class="hover:text-brand-600"><?php the_title(); ?></a>
						</h2>
						<p class="mt-2 text-sm leading-relaxed text-slate-600 <?php echo $is_featured ? 'line-clamp-3' : 'line-clamp-2'; ?>"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
					</div>
				</article>
			<?php $post_index++; endwhile; ?>
		</div>

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
