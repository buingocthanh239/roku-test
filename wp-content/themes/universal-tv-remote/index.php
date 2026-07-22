<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<div class="mx-auto max-w-3xl px-4 py-16">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<article>
			<h1 class="text-2xl font-bold text-slate-900"><?php the_title(); ?></h1>
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="mt-6 overflow-hidden rounded-2xl"><?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-auto object-cover' ) ); ?></div>
			<?php endif; ?>
			<div class="prose mt-4"><?php the_content(); ?></div>
		</article>
	<?php endwhile; else : ?>
		<p class="text-slate-600">Nothing found.</p>
	<?php endif; ?>
</div>
<?php get_footer(); ?>
