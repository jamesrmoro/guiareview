<?php get_header();
$blog_category = get_category_by_slug('blog');
$exclude_id = $blog_category ? $blog_category->term_id : 0;
?>
<main>
    <article>
        <section class="container-page">
            <div class="center">
                <div class="group-logo">
                    <div></div>
                    <div>
                        <img class="logo" width="200px" height="35px" src="<?php bloginfo('template_url') ?>/src/images/logo-guia-review.png" alt="Logo Guia Review">
                        <h1 class="title-hide">Guia Review</h1>
                    </div>
                    <div>
                        <button id="openSearchBtn" aria-label="Abrir busca">
                            <img src="<?php bloginfo('template_url') ?>/src/images/icon-search.svg" alt="Buscar">
                        </button>
                    </div>
                </div>

                <div class="cards">
                    <div class="wrapper">

                        <?php
                        $categories_per_page = 15;
                        $paged = get_query_var('page') ? get_query_var('page') : 1;
                        $offset = ($paged - 1) * $categories_per_page;

                        $categories = get_categories(array(
                            'number' => $categories_per_page,
                            'offset' => $offset,
                            'exclude' => array($exclude_id),
                        ));

                        if ($categories) {
                            foreach ($categories as $category) {
                                $category_image = get_field('image', 'category_' . $category->term_id);
                                $category_flag = get_field('flag', 'category_' . $category->term_id);
                                ?>
                                <div class="card">
                                    <a class="link-image" title="Como ter boas ideias para contar histórias" href="<?php echo esc_url(get_term_link($category)); ?>">
                                        <img width="320px" height="213px" src="<?php echo $category_image; ?>" alt="<?php echo esc_html($category->name); ?>">
                                    </a>
                                    <div class="text">
                                        <div class="category">
                                            <a href="<?php echo esc_url(get_term_link($category)); ?>" title="<?php echo $category_flag; ?>"><?php echo $category_flag; ?></a>
                                        </div>
                                        <a class="group" href="<?php echo esc_url(get_term_link($category)); ?>" title="<?php echo esc_html($category->name); ?>">
                                            <?php echo '<h2 class="title">' . esc_html($category->name) . '</h2>'; ?>
                                            <span class="link">Ver produtos</span>
                                        </a>
                                    </div>
                                </div>

                            <?php }
                        } else {
                            echo '<p>Nenhuma categoria encontrada.</p>';
                        }

                        // Pagination
                        $total_categories = wp_count_terms('category');
                        $total_pages = ceil($total_categories / $categories_per_page);

                        echo '<div class="line-pagination">';
                        echo '<div class="pagination">';
                        echo paginate_links(array(
                            'base' => get_pagenum_link(1) . '%_%',
                            'format' => 'page/%#%',
                            'current' => $paged,
                            'total' => $total_pages,
                        ));
                        echo '</div>';
                        echo '</div>';
                        ?>
                    </div>
                </div>
            </div>
        </section>
    </article>
</main>
<?php get_footer(); ?>
