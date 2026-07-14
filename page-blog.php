<?php get_header(); ?>
<main>
    <article>
        <section class="container-page container-page-blog">
            <div class="center">
                <div class="group-logo">
                    <img class="logo" width="200px" height="55px" src="<?php bloginfo('template_url') ?>/src/images/logo-os-10-melhores-livros.svg" alt="Logo Os 10 Melhores Livros">
                    <h1 class="title-hide">Os 10 Melhores Livros</h1>
                </div>
                <div class="cards">
                    <div class="wrapper">

                        <?php
                        $posts_per_page = 9;
                        $paged = get_query_var('paged') ? get_query_var('paged') : 1;

                        $args = array(
                            'post_type' => 'post',
                            'category_name' => 'blog', // Slug da categoria
                            'posts_per_page' => $posts_per_page,
                            'paged' => $paged,
                        );

                        $query = new WP_Query($args);

                        if ($query->have_posts()) :
                            while ($query->have_posts()) : $query->the_post();
                                $category = get_the_category();
                                $category_name = !empty($category) ? $category[0]->name : '';
                                $category_link = !empty($category) ? get_category_link($category[0]->term_id) : '';
                                ?>
                                <div class="card">
                                    <a class="link-image" href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                                        <?php
                                        if (has_post_thumbnail()) {
                                            the_post_thumbnail('medium', ['width' => 320, 'height' => 213]);
                                        } else {
                                            $default_image = get_template_directory_uri() . '/src/images/thumbnail-default.jpg';
                                            echo '<img width="320" height="213" src="' . esc_url($default_image) . '" alt="' . esc_attr(get_the_title()) . '" />';
                                        }
                                        ?>
                                    </a>
                                    <div class="text">
                                        <div class="category">
                                            <a href="<?php echo esc_url($category_link); ?>" title="<?php echo esc_attr($category_name); ?>"><?php echo esc_html($category_name); ?></a>
                                        </div>
                                        <a class="group" href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                                            <h2 class="title"><?php the_title(); ?></h2>
                                            <span class="link">Continuar lendo</span>
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile;
                        else :
                            echo '<p>Nenhuma postagem encontrada.</p>';
                        endif;
                        ?>

                        <?php
                        // Paginação
                        echo '<div class="line-pagination">';
                        echo '<div class="pagination">';
                        echo paginate_links(array(
                            'total' => $query->max_num_pages,
                            'current' => $paged,
                            'mid_size' => 2,
                            'prev_text' => __('&laquo; Anterior', 'textdomain'),
                            'next_text' => __('Próximo &raquo;', 'textdomain'),
                        ));
                        echo '</div>';
                        echo '</div>';

                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
            </div>
        </section>
    </article>
</main>
<?php get_footer(); ?>
