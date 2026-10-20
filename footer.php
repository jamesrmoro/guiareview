<?php
/**
 * The template for displaying the footer.
 *
 * @package Guia Review
 * @since 0.0.1
 */
?>

	<footer class="grv-footer">
		<div class="container grv-footer-cols">
			<div class="grv-footer-about">
				<a class="grv-footer-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Guia Review — página inicial">
					<img src="<?php echo esc_url( get_template_directory_uri() . '/src/images/logo-guia-review.png' ); ?>" width="205" height="36" alt="Guia Review" loading="lazy">
				</a>
				<p>O Guia Review ajuda você a comparar produtos com mais clareza. Reunimos características, imagens e informações de diferentes categorias para apoiar sua escolha antes de acessar a oferta na loja.</p>
				<nav aria-label="Sobre o Guia Review"><ul>
					<li><a href="<?php echo esc_url( home_url( '/sobre' ) ); ?>">Sobre nós</a></li>
					<li><a href="<?php echo esc_url( home_url( '/contato' ) ); ?>">Contato</a></li>
					<li><a href="<?php echo esc_url( home_url( '/politica-de-privacidade' ) ); ?>">Política de privacidade</a></li>
				</ul></nav>
			</div>
			<?php
			$grv_footer_catalog = grv_catalog_categories();
			$grv_footer_roots = array();
			$grv_footer_technology = array();
			$grv_footer_lifestyle = array();
			$grv_footer_names = array_count_values( wp_list_pluck( $grv_footer_catalog['terms'], 'name' ) );
			foreach ( $grv_footer_catalog['terms'] as $grv_footer_term ) {
				if ( ! $grv_footer_term->parent ) {
					$grv_footer_roots[] = $grv_footer_term;
					continue;
				}
				if ( empty( $grv_footer_catalog['counts'][ $grv_footer_term->term_id ] ) ) { continue; }
				$grv_footer_ancestors = get_ancestors( $grv_footer_term->term_id, 'category' );
				$grv_footer_root = get_term( end( $grv_footer_ancestors ), 'category' );
				if ( $grv_footer_root instanceof WP_Term && in_array( $grv_footer_root->slug, array( 'computadores-e-informatica', 'casa', 'eletronicos', 'games-e-consoles', 'tv-e-video' ), true ) ) {
					$grv_footer_technology[] = $grv_footer_term;
				} else {
					$grv_footer_lifestyle[] = $grv_footer_term;
				}
			}
			// Prioritize categories with products; keep the three link columns similarly sized.
			$grv_footer_sort = static function ( $a, $b ) use ( $grv_footer_catalog ) {
				return ( ( $grv_footer_catalog['counts'][ $b->term_id ] ?? 0 ) <=> ( $grv_footer_catalog['counts'][ $a->term_id ] ?? 0 ) ) ?: strcasecmp( $a->name, $b->name );
			};
			usort( $grv_footer_technology, $grv_footer_sort );
			usort( $grv_footer_lifestyle, $grv_footer_sort );
			$grv_footer_limit = max( 8, count( $grv_footer_roots ) );
			$grv_footer_groups = array(
				array( 'title' => 'Categorias', 'terms' => $grv_footer_roots ),
				array( 'title' => 'Tecnologia e casa', 'terms' => array_slice( $grv_footer_technology, 0, $grv_footer_limit ) ),
				array( 'title' => 'Saúde e estilo de vida', 'terms' => array_slice( $grv_footer_lifestyle, 0, $grv_footer_limit ) ),
			);
			foreach ( $grv_footer_groups as $grv_footer_index => $grv_footer_group ) :
				if ( ! $grv_footer_group['terms'] ) { continue; }
				?>
				<nav class="grv-footer-category-links" aria-labelledby="grvFooterHeading<?php echo (int) $grv_footer_index; ?>">
					<h2 id="grvFooterHeading<?php echo (int) $grv_footer_index; ?>"><?php echo esc_html( $grv_footer_group['title'] ); ?></h2>
					<ul>
						<?php foreach ( $grv_footer_group['terms'] as $grv_footer_term ) :
							$grv_footer_label = $grv_footer_term->name;
							if ( $grv_footer_term->parent && $grv_footer_names[ $grv_footer_label ] > 1 ) {
								$grv_footer_parent = get_term( $grv_footer_term->parent, 'category' );
								if ( $grv_footer_parent instanceof WP_Term ) { $grv_footer_label .= ' (' . $grv_footer_parent->name . ')'; }
							}
							?>
							<li><a href="<?php echo esc_url( get_category_link( $grv_footer_term ) ); ?>"><?php echo esc_html( $grv_footer_label ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endforeach; ?>
		</div>
		<p class="grv-footer-copyright">&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> Guia Review. Todos os direitos reservados.</p>
	</footer>

</div><!-- /.grv -->

<?php require __DIR__ . '/footer-widgets.php'; ?>
<?php wp_footer(); ?>
</body>
</html>
