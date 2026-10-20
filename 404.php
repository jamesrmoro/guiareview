<?php
/**
 * @package Guia Review
 * @since 0.0.1
 */
status_header( 404 );
nocache_headers();
get_header(); ?>
<main>
    <?php grv_breadcrumbs( array( array( 'label' => 'Página não encontrada', 'url' => null ) ) ); ?>
    <section class="container grv-not-found" aria-labelledby="grv-404-title">
        <div class="grv-404-box">
            <p class="grv-404-code" aria-hidden="true">404</p>
            <p class="grv-eyebrow">Vamos encontrar outro caminho</p>
            <h1 id="grv-404-title">Página não encontrada</h1>
            <p class="muted">O endereço pode ter mudado ou o conteúdo não está disponível. Busque um produto ou explore as categorias.</p>
            <form class="grv-form-search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search">
                <label class="sr-only" for="grv-404-query">Buscar produtos</label>
                <input id="grv-404-query" type="search" name="s" placeholder="Qual produto você procura?" required>
                <input type="hidden" name="post_type" value="post">
                <button type="submit">Buscar</button>
            </form>
            <div class="grv-404-actions">
                <a class="btn-buy" href="<?php echo esc_url( home_url( '/' ) ); ?>">Voltar ao início</a>
                <a class="btn-buy-2" href="<?php echo esc_url( home_url( '/contato/' ) ); ?>">Fale com a gente</a>
            </div>
            <nav class="grv-subcats" aria-label="Explorar categorias">
                <?php foreach ( get_categories( array( 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name' ) ) as $category ) : ?>
                    <a href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </section>
</main>
<?php get_footer(); ?>
