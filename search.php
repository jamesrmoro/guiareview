<?php get_header(); ?>
<main>
  <article>
    <section class="container-page">
      <div class="center">
        <div class="group-logo">
          <div></div>
          <div>
            <a href="<?php bloginfo('siteurl'); ?>" title="Guia Review"><img class="logo" width="200px" height="35px" src="<?php bloginfo('template_url'); ?>/src/images/logo-guia-review.png" alt="Logo Guia Review"></a>
            <h1 class="title-hide">Resultados da busca</h1>
          </div>
          <div>
            <button id="openSearchBtn" aria-label="Abrir busca">
              <img src="<?php bloginfo('template_url'); ?>/src/images/icon-search.svg" alt="Buscar">
            </button>
          </div>
        </div>

        <div class="cards">
          <?php
          $posts_array = [];
          $search_term = get_search_query();

          // Captura os posts
          if (have_posts()) {
            while (have_posts()) {
              the_post();
              $posts_array[] = $post;
            }
          }

          $total_resultados = count($posts_array);

          // Envia email se nenhum resultado
          if ($total_resultados === 0 && !empty($search_term)) {
            $to = 'jamesrmoro@gmail.com';
            $subject = 'Busca sem resultado | ' . sanitize_text_field($search_term);
            $message = "Nenhum resultado encontrado para o termo de busca: \"" . sanitize_text_field($search_term) . "\"\n\n";
            $message .= "URL da busca: " . esc_url(home_url('/?s=' . urlencode($search_term))) . "\n";
            $headers = ['Content-Type: text/plain; charset=UTF-8'];

            wp_mail($to, $subject, $message, $headers);
          }
          ?>

          <h2 class="title-result">Resultados para: <strong><?php echo esc_html($search_term); ?></strong></h2>

          <div class="wrapper">
            <?php if ($total_resultados > 0): ?>
              <?php foreach ($posts_array as $post): setup_postdata($post); ?>
                <div class="card">
                  <a class="link-image" href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                    <?php if (has_post_thumbnail()) : ?>
                      <?php the_post_thumbnail('medium', ['width' => '320', 'height' => '213']); ?>
                    <?php else : ?>
                      <img src="<?php bloginfo('template_url'); ?>/src/images/thumb-placeholder.jpg" width="320" height="213" alt="<?php the_title_attribute(); ?>">
                    <?php endif; ?>
                  </a>
                  <div class="text">
                    <a class="group" href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                      <h2 class="title"><?php the_title(); ?></h2>
                      <span class="link">Ver mais</span>
                    </a>
                  </div>
                </div>
              <?php endforeach; wp_reset_postdata(); ?>
            <?php else : ?>
              <p style="width: 100%;text-align: center;color:#fff">Nenhum resultado encontrado para "<strong><?php echo esc_html($search_term); ?></strong>".</p>
            <?php endif; ?>
          </div>

          <div class="line-pagination">
            <div class="pagination">
              <?php
              echo paginate_links(array(
                'total' => $wp_query->max_num_pages,
                'current' => max(1, get_query_var('paged')),
              ));
              ?>
            </div>
          </div>
        </div>
      </div>
    </section>
  </article>
</main>
<?php get_footer(); ?>
