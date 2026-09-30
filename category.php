<?php
/**
 * @package Guia Review
 * @since 0.0.1
 */
get_header();

$term = get_queried_object();
$trail = $term ? grv_category_ancestors( $term ) : array();
$trail[] = array( 'label' => single_cat_title( '', false ), 'url' => null );

$subcats = $term ? get_categories( array( 'parent' => $term->term_id, 'hide_empty' => false, 'orderby' => 'name' ) ) : array();
?>
<main>
	<?php grv_breadcrumbs( $trail ); ?>

	<header class="container grv-cat-header">
		<h1><?php single_cat_title(); ?></h1>
		<?php if ( $term && $term->description ) : ?>
			<p class="desc"><?php echo esc_html( $term->description ); ?></p>
		<?php else : ?>
			<p class="desc">Veja nossa seleção de <?php echo esc_html( mb_strtolower( single_cat_title( '', false ) ) ); ?>, com análises e comparativos para te ajudar a escolher.</p>
		<?php endif; ?>

		<?php if ( $subcats ) : ?>
			<nav class="grv-subcats" aria-label="Subcategorias de <?php single_cat_title(); ?>">
				<?php foreach ( $subcats as $sc ) : ?>
					<a href="<?php echo esc_url( get_category_link( $sc->term_id ) ); ?>"><?php echo esc_html( $sc->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</header>

	<section class="container grv-block">
		<?php if ( have_posts() ) : ?>
			<div class="grv-grid">
				<?php while ( have_posts() ) : the_post(); grv_product_card( get_the_ID() ); endwhile; ?>
			</div>
			<?php pagination(); ?>
		<?php else : ?>
			<p class="grv-empty">Ainda não há produtos publicados nesta categoria.</p>
		<?php endif; ?>
	</section>
</main>
<?php wp_reset_postdata(); get_footer(); ?>
