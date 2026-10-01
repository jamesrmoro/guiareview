<?php
/** Standard layout for institutional pages. */
get_header();
?>
<main>
<?php while ( have_posts() ) : the_post(); ?>
    <?php grv_breadcrumbs( array( array( 'label' => get_the_title(), 'url' => null ) ) ); ?>
    <article class="container grv-page">
        <header><h1><?php the_title(); ?></h1></header>
        <div class="grv-page-content"><?php the_content(); ?></div>
    </article>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
