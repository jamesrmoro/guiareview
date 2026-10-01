<?php
/**
 * The template for displaying the footer.
 *
 * @package Guia Review
 * @since 0.0.1
 */
?>

	<footer class="grv-footer" style="background:#111226;color:#fff;margin-top:40px">
		<div class="container grv-footer-cols">
			<div>
				<h3>Guia Review</h3>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/sobre' ) ); ?>">Sobre nós</a></li>
					<li><a href="<?php echo esc_url( home_url( '/contato' ) ); ?>">Contato</a></li>
					<li><a href="<?php echo esc_url( home_url( '/politica-de-privacidade' ) ); ?>">Política de privacidade</a></li>
				</ul>
			</div>
			<?php
			$grv_footer_cats = get_categories( array( 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name', 'number' => 3 ) );
			foreach ( $grv_footer_cats as $grv_fcat ) :
				$grv_sub = get_categories( array( 'parent' => $grv_fcat->term_id, 'hide_empty' => false, 'number' => 5 ) );
				?>
				<div>
					<h3><a href="<?php echo esc_url( get_category_link( $grv_fcat->term_id ) ); ?>" style="color:#fff"><?php echo esc_html( $grv_fcat->name ); ?></a></h3>
					<ul>
						<?php if ( $grv_sub ) : foreach ( $grv_sub as $grv_s ) : ?>
							<li><a href="<?php echo esc_url( get_category_link( $grv_s->term_id ) ); ?>"><?php echo esc_html( $grv_s->name ); ?></a></li>
						<?php endforeach; else : ?>
							<li><a href="<?php echo esc_url( get_category_link( $grv_fcat->term_id ) ); ?>">Ver produtos</a></li>
						<?php endif; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
		<p style="text-align:center;padding:16px;font-size:12px;color:#8b8da8;margin:0;border-top:1px solid rgba(255,255,255,.08)">&copy; <?php echo esc_html( date( 'Y' ) ); ?> Guia Review. Todos os direitos reservados.</p>
	</footer>

</div><!-- /.grv -->

<?php require __DIR__ . '/footer-widgets.php'; ?>
<?php wp_footer(); ?>
</body>
</html>
