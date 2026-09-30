<?php
/**
 * Fallback de arquivo — hoje usado principalmente para páginas de tag
 * (the_tags() em single.php aponta pra cá, pois não existe tag.php).
 *
 * @package Guia Review
 * @since 0.0.1
 */
get_header();

$title = is_tag() ? single_tag_title( '', false ) : ( is_category() ? single_cat_title( '', false ) : get_the_archive_title() );
$trail = array( array( 'label' => $title, 'url' => null ) );
?>
<main>
	<?php grv_breadcrumbs( $trail ); ?>

	<header class="container grv-cat-header">
		<h1><?php echo esc_html( $title ); ?></h1>
		<?php if ( is_tag() ) : ?>
			<p class="desc">Produtos relacionados a "<?php echo esc_html( $title ); ?>".</p>
		<?php endif; ?>
	</header>

	<section class="container grv-block">
		<?php if ( have_posts() ) : ?>
			<div class="grv-grid">
				<?php while ( have_posts() ) : the_post(); grv_product_card( get_the_ID() ); endwhile; ?>
			</div>
			<?php pagination(); ?>
		<?php else : ?>
			<p class="grv-empty">Nenhum produto encontrado.</p>
		<?php endif; ?>
	</section>
</main>
<?php wp_reset_postdata(); get_footer(); ?>
