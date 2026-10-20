<?php
get_header();
global $wp_query;
$search_term = get_search_query();
$total = (int) $wp_query->found_posts;
?>
<main>
  <?php grv_breadcrumbs( array( array( 'label' => 'Resultados da busca', 'url' => null ) ) ); ?>
  <section class="container grv-search-results">
    <header class="grv-results-head">
      <p class="grv-eyebrow">Encontre seu próximo produto</p>
      <h1>Resultados para “<?php echo esc_html( $search_term ); ?>”</h1>
      <p class="muted"><?php echo esc_html( sprintf( _n( '%s produto encontrado', '%s produtos encontrados', $total ), number_format_i18n( $total ) ) ); ?></p>
      <form class="grv-form-search grv-results-search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search">
        <label class="sr-only" for="grv-results-query">Buscar produtos</label>
        <input id="grv-results-query" type="search" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="Digite uma marca ou produto" required>
        <input type="hidden" name="post_type" value="post">
        <button type="submit">Buscar</button>
      </form>
    </header>
    <?php if ( have_posts() ) : ?>
      <div class="grv-grid">
        <?php while ( have_posts() ) : the_post(); grv_product_card( get_the_ID() ); endwhile; ?>
      </div>
      <nav class="pagination" aria-label="Páginas de resultados">
        <?php echo paginate_links( array( 'total' => $wp_query->max_num_pages, 'current' => max( 1, get_query_var( 'paged' ) ), 'type' => 'list', 'prev_text' => 'Anterior', 'next_text' => 'Próxima' ) ); ?>
      </nav>
    <?php else : ?>
      <div class="grv-search-empty">
        <span class="grv-empty-icon" aria-hidden="true">⌕</span>
        <h2>Nenhum produto encontrado</h2>
        <p>Tente usar menos palavras, confira a escrita ou procure pelo nome da marca.</p>
        <a class="btn-buy" href="<?php echo esc_url( home_url( '/' ) ); ?>">Explorar categorias</a>
      </div>
    <?php endif; ?>
  </section>
</main>
<?php get_footer(); ?>
