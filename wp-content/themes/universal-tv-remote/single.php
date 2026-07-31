<?php
/**
 * Single blog post. Previously index.php doubled as both this and the blog
 * archive/listing — since index.php's loop assumed exactly one post
 * (the_title() + full the_content() with no excerpt/pagination), visiting
 * the multi-post archive at /blog/ rendered every post's full content back
 * to back instead of a list of teaser cards (see index.php).
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
$blog_url = home_url( '/blog/' );
?>
<div class="mx-auto max-w-3xl px-4 py-16">
	<?php while ( have_posts() ) : the_post();
		$categories = get_the_category();
	?>
		<a href="<?php echo esc_url( $blog_url ); ?>" class="text-sm font-medium text-brand-600 hover:text-brand-700">&larr; Back to Blog</a>

		<div class="mt-4 flex items-center gap-2 text-sm text-slate-500">
			<?php if ( $categories ) : ?>
				<span class="font-medium text-brand-600"><?php echo esc_html( $categories[0]->name ); ?></span>
				<span class="text-slate-300">&middot;</span>
			<?php endif; ?>
			<span><?php echo esc_html( get_the_date() ); ?></span>
		</div>

		<h1 class="mt-2 text-4xl font-extrabold tracking-tight text-[#3D7DE3] sm:text-5xl"<?php echo tvr_heading_style_attr(); ?>><?php the_title(); ?></h1>

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="mt-8 aspect-video w-full overflow-hidden rounded-2xl bg-slate-100"><?php the_post_thumbnail( 'large', array( 'class' => 'h-full w-full object-cover' ) ); ?></div>
		<?php endif; ?>

		<div class="prose mt-8 max-w-none text-slate-600"><?php the_content(); ?></div>
	<?php endwhile; ?>
</div>
<?php get_footer(); ?>
