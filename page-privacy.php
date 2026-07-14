<?php
/**
 * @package Os 10 Melhores Livros
 * @since 0.0.1
 */
get_header();?>
	<main>
		<article>
			<section class="container-page">
				<div class="center">
					<div class="format-post">
						<?php if (have_posts()): ?>
						<?php while (have_posts()) : the_post(); ?>
							<h1><?php the_title(); ?></h1>
							<?php the_content(); ?>
						<?php endwhile; ?>
						<?php endif; wp_reset_postdata(); ?>
					</div>
				</div>
			</section>
		</article>
	</main>
<?php get_footer(); ?>