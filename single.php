<?php
/**
 * @package Os 10 Melhores Livros
 * @since 0.0.1
 */
get_header();
$categories = get_the_category();
?>
	<main>
		<article <?php if (is_single() && has_category('blog')) echo 'class="category-blog"'; ?>>

			<?php if (have_posts()): ?>
				<?php while (have_posts()) : the_post();
				$url_product = get_field("url");
				$image_product = get_field("image");
				?>
					<section class="container-page">
						<div class="center">
							<div class="group-logo">
								<div></div>
								<div>
									<a href="<?php bloginfo('url') ?>" title="<?php bloginfo('site_name') ?>">
										<img class="logo" width="200px" height="35px" src="<?php bloginfo('template_url') ?>/src/images/logo-guia-review.png" alt="Logo Guia Review">
										<span class="title-hide">Guia Review</span>
									</a>
								</div>
								<div>
			                        <button id="openSearchBtn" aria-label="Abrir busca">
			                            <img src="<?php bloginfo('template_url') ?>/src/images/icon-search.svg" alt="Buscar">
			                        </button>
			                    </div>
							</div>
							<div class="format-post">
								<?php if (function_exists('rank_math_the_breadcrumbs')) rank_math_the_breadcrumbs(); ?>
								<div class="wrapper-post">
									<div class="image">
										<?php if($image_product == ""){ ?>

											<?php
		                                        if (has_post_thumbnail()) {
		                                            the_post_thumbnail('medium', ['width' => 320, 'height' => 213]);
		                                        } else {
		                                            $default_image = get_template_directory_uri() . '/src/images/thumbnail-default.jpg';
		                                            echo '<img width="320" height="213" src="' . esc_url($default_image) . '" alt="' . esc_attr(get_the_title()) . '" />';
		                                        }
		                                    ?>
		                                <?php } else { ?>
											<img alt="<?php the_title(); ?>" src="<?php echo $image_product; ?>">
										<?php } ?>
									</div>
									<div class="text">
										<h1><?php the_title(); ?></h1>
										<?php if (!has_category('blog')) : ?>
											<h2 class="title-h2">
												<?php 
												$titulo_livro = "Conheça agora um dos " . esc_html($categories[0]->name);
												$titulo_sem_os = str_replace("Os ", "", $titulo_livro);
												echo $titulo_sem_os;
												?>
											</h2>
										<?php endif; ?>
										<?php the_content(); ?>
										<h2></h2>
										<ul class="buttons">
											<li>
												<a class="button-1" href="<?php echo $url_product; ?>" title="Comprar" target="_blank">
													<img src="<?php bloginfo('template_url') ?>/src/images/icon-cart.svg" alt="Comprar">
													<span>Comprar</span>
												</a>
											</li>
											<li>
												<a class="button-2" href="<?php echo $url_product; ?>" title="Ver preço" target="_blank">
													<img src="<?php bloginfo('template_url') ?>/src/images/icon-price.svg" alt="Preço">
													<span>Ver preço</span>
												</a>
											</li>
										</ul>
									</div>
								</div>
								<?php
								$isbn = get_field("isbn");
								if($isbn != ""){ ?>
									<div class="product-detail">
										<div class="description">
											<?php
											$pages = get_field("pages");
											$language = get_field("language");
											$measurements = get_field("measurements");
											$company = get_field("company");
											if($pages != ""){ ?>
												<div class="item detail">
													<label>Páginas</label>
													<span><?php echo $pages; ?></span>
												</div>
											<?php }
											if($language != ""){ ?>
												<div class="item detail">
													<label>Idioma</label>
													<span><?php echo $language; ?></span>
												</div>
											<?php }
											if($isbn != ""){ ?>
												<div class="item detail">
													<label>ISBN</label>
													<span><?php echo $isbn; ?></span>
												</div>
											<?php }
											if($measurements != ""){ ?>
												<div class="item detail">
													<label>Medidas</label>
													<span><?php echo $measurements; ?></span>
												</div>
											<?php }
											if($company != ""){ ?>
												<div class="item detail">
													<label>Editora</label>
													<span><?php echo $company; ?></span>
												</div>
											<?php } ?>
										</div>
									</div>
								<?php } ?>
							</div>
						</div>
					</section>
				<?php endwhile; ?>
			<?php endif; wp_reset_postdata(); ?>
			<?php if (!has_category('blog')) : ?>
				<div class="other-books">
					<div class="center">
						<h3 class="title-other-books title-padding-mobile"><?php echo esc_html($categories[0]->name); ?></h3>
						<?php
						$current_category = get_the_category()[0]->cat_ID;
					    $args = array(
					        'cat'            => $current_category,  
					        'posts_per_page' => 10,
					        'post__not_in'   => array(get_the_ID()),
					    );
					    $query = new WP_Query($args);
					    if ($query->have_posts()):
					    	echo '<div class="books-container">';
						        while ($query->have_posts()) : $query->the_post();
						        	$url_product = get_field("url");
									$image_product = get_field("image");
									$thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'small_thumbnail');
						        ?>

						        <div class="book-item">
							        <a href="<?php echo $url_product; ?>" rel="nofollow" target="_blank" title="<?php the_title(); ?>">
							        	<img src="<?php echo $thumbnail_url; ?>" alt="<?php the_title(); ?>">
							        </a>
							        <a class="buy" href="<?php echo $url_product; ?>" rel="nofollow" target="_blank" title="Comprar livro">
							        	<span>Ver preço</span>
							        	<svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="12" height="12" x="0" y="0" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="m506.134 241.843-.018-.019-104.504-104c-7.829-7.791-20.492-7.762-28.285.068-7.792 7.829-7.762 20.492.067 28.284L443.558 236H20c-11.046 0-20 8.954-20 20s8.954 20 20 20h423.557l-70.162 69.824c-7.829 7.792-7.859 20.455-.067 28.284 7.793 7.831 20.457 7.858 28.285.068l104.504-104 .018-.019c7.833-7.818 7.808-20.522-.001-28.314z" fill="#fff" opacity="1" data-original="#000000" class=""></path></g></svg>
							        </a>
						        </div>

						        <?php endwhile;
					        echo '</div>';
					        wp_reset_postdata();
					    else :
					        echo 'Não há posts nesta categoria.';
					    endif;
						?>
					</div>
				</div>
			<?php endif; ?>
			<div class="ads-amazon">
				<div class="center">
					<h2>Como escolher o melhor produto para comprar?</h2>

					<ul>
						<li>
							Analise quais características são realmente importantes para o seu uso, como tamanho, capacidade, desempenho, funcionalidades e facilidade de utilização.
						</li>

						<li>
							Compare diferentes modelos da mesma categoria para identificar qual oferece o melhor equilíbrio entre qualidade, recursos e preço.
						</li>

						<li>
							Verifique as especificações técnicas, as dimensões e os requisitos de instalação para garantir que o produto seja adequado ao espaço e às suas necessidades.
						</li>

						<li>
							Consulte as avaliações de outros consumidores, observando principalmente os comentários sobre durabilidade, desempenho e possíveis problemas recorrentes.
						</li>

						<li>
							Considere a reputação da marca, o período de garantia e a disponibilidade de assistência técnica antes de concluir a compra.
						</li>

						<li>
							Por fim, pesquise os preços em diferentes lojas e confira as condições de entrega para encontrar a opção com o melhor custo-benefício.
						</li>
					</ul>
				</div>
			</div>
			<?php if (!has_category('blog')) : ?>
				<div class="list-books">
					<div class="center">
						<h3 class="title-padding-mobile"><?php 
							$titulo_livro = "Conheça outros " . esc_html($categories[0]->name);
							$titulo_sem_os = str_replace("Os ", "", $titulo_livro);
							echo $titulo_sem_os;
							?> recomendado por usuários</h3>
					</div>
					<?php
						$current_category = get_the_category()[0]->cat_ID;
					    $args = array(
					        'cat'            => $current_category,  
					        'posts_per_page' => 10,
					        'post__not_in'   => array(get_the_ID()),
					    );
					    $query = new WP_Query($args);
					    if ($query->have_posts()):
					    	echo '<div class="books-description">';
						        while ($query->have_posts()) : $query->the_post();
						        	$url_product = get_field("url");
									$image_product = get_field("image");
									$thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'small_thumbnail');
						        ?>

						        <div class="book-item-description">
						        	<div class="center">
						        		<div class="group">
								        	<div class="image">
										        <a href="<?php echo $url_product; ?>" rel="nofollow" target="_blank" title="<?php the_title(); ?>">
										        	<img src="<?php echo $thumbnail_url; ?>" alt="<?php the_title(); ?>">
										        </a>
									        </div>
									        <div class="text">
										        <h4><?php the_title(); ?></h4>
										        <?php the_content(); ?>
										        <a class="buy" href="<?php echo $url_product; ?>" rel="nofollow" target="_blank" title="Ver preço">
										        	<span>Ver preço</span>
										        	<svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="12" height="12" x="0" y="0" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="m506.134 241.843-.018-.019-104.504-104c-7.829-7.791-20.492-7.762-28.285.068-7.792 7.829-7.762 20.492.067 28.284L443.558 236H20c-11.046 0-20 8.954-20 20s8.954 20 20 20h423.557l-70.162 69.824c-7.829 7.792-7.859 20.455-.067 28.284 7.793 7.831 20.457 7.858 28.285.068l104.504-104 .018-.019c7.833-7.818 7.808-20.522-.001-28.314z" fill="#fff" opacity="1" data-original="#000000" class=""></path></g></svg>
										        </a>
									        </div>
								        </div>
							        </div>
						        </div>

						        <?php endwhile;
					        echo '</div>';
					        wp_reset_postdata();
					    else :
					        echo 'Não há posts nesta categoria.';
					    endif;
						?>
				</div>
			<?php endif; ?>
			<div class="center">
				<a class="banner" href="https://amzn.to/4bycyaW" target="_blank" rel="nofollow" title="Amazon Ofertas">
					<img src="<?php bloginfo("template_url"); ?>/src/images/banner-ofertas-amazon.jpg" alt="Amazon Ofertas">
				</a>

				<h5 class="title-page title-padding-mobile">Termos relacionados a <?php the_title(); ?></h5>

				<?php the_tags('<ul class="list-categories list-tags"><li>', '</li><li>', '</li></ul>'); ?>

				<h6 class="title-page">Confira outras seleções de melhores produtos</h6>

				<?php
				$categories = get_categories();
					if (!empty($categories)) {
					    echo '<ul class="list-categories">';
					    foreach ($categories as $category) {
					        echo '<li><a href="' . get_category_link($category->term_id) . '">' . $category->name . '</a></li>';
					    }				    
					    echo '</ul>';
					} else {
					    echo 'Nenhuma categoria encontrada.';
					}
				?>
			</div>
		</article>
	</main>
<?php get_footer(); ?>