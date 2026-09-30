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

<div class="modal-ads" id="modalAds">
  <div class="wrapper-modal-ads">
    <span id="ads-3" class="closeText"></span>
    <a id="ads-2" href="https://amzn.to/3ILMrU3" class="banner-inner" target="_blank" rel="noopener noreferrer">
      <img src="<?php bloginfo('template_url'); ?>/src/images/ads-2.jpg" alt="Compre Novo Kindle Colorido | Frete Grátis com Prime">
    </a>
  </div>
</div>

<div id="footer-banner" class="expanded">
  <button id="toggle-banner" title="Mostrar/Ocultar banner"></button>
  <a id="ads-1" href="https://amzn.to/3ILMrU3" class="banner-inner" target="_blank" rel="noopener noreferrer">
    <div class="banner-text">
      <div class="image-banner">
        <img src="<?php bloginfo('template_url'); ?>/src/images/ads-1.png" alt="Compre Novo Kindle Colorido | Frete Grátis com Prime">
      </div>
      <div class="group-banner">
        <h2 class="banner-title">Compre Novo Kindle Colorido | Frete Grátis com Prime</h2>
        <div class="group-link">
          <img src="<?php bloginfo('template_url'); ?>/src/images/stars.png" alt="Avaliação">
          <span class="link">Entrega Rápida e Segura</span>
        </div>
      </div>
    </div>
  </a>
</div>

<div class="cookie-notice" style="display: none">
  <div class="container-cookie">
    <div class="notice-text">
      <span>
        <p>Utilizamos cookies para oferecer melhor experiência, melhorar o desempenho, analisar como você interage em nosso aplicativo e personalizar conteúdo. Para mais informações acesse nossa <a href="<?php echo site_url(); ?>/privacy" title="Política de Privacidade">Política de Privacidade</a>.</p>
      </span>
    </div>
    <div class="notice-buttons">
      <span class="btn-cookie accept" title="Aceitar todos os cookies">Ok, entendi</span>
    </div>
  </div>
</div>

<?php wp_footer(); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mobile-detect/1.4.3/mobile-detect.min.js"></script>

<script>
  function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value}; expires=${d.toUTCString()}; path=/`;
  }

  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
  }

  document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalAds');
    const closeText = document.querySelector('.closeText');

    if (getCookie('ocultou_ads') === 'sim') {
      modal.classList.remove('active');
      return;
    }

    modal.classList.add('active');

    let counter = 5;
    const interval = setInterval(() => {
      if (counter > 0) {
        closeText.textContent = `Fechar anúncio em ${counter}...`;
        counter--;
      } else {
        clearInterval(interval);
        closeText.textContent = 'Fechar anúncio';
        closeText.style.cursor = 'pointer';

        closeText.addEventListener('click', () => {
          modal.classList.remove('active');
          setCookie('ocultou_ads', 'sim', 3);

          // Enviar clique do tipo "Fechar anúncio"
          fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
              action: 'enviar_clique_anuncio',
              anuncio: 'ads-3',
              pagina: window.location.href,
              tipo: 'Fechar anúncio'
            })
          }).then(res => res.json()).then(data => {
            console.log('ads-3 enviado:', data);
          });
        });
      }
    }, 1000);

    // Clique em banners ads-1 e ads-2
    ['ads-1', 'ads-2'].forEach(id => {
      const el = document.getElementById(id);
      if (el) {
        el.addEventListener('click', function () {
          fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
              action: 'enviar_clique_anuncio',
              anuncio: id,
              pagina: window.location.href,
              tipo: 'Clique Banner'
            })
          }).then(res => res.json()).then(data => {
            console.log(data);
          });
        });
      }
    });
  });

  const banner = document.getElementById('footer-banner');
  const toggleBtn = document.getElementById('toggle-banner');

  toggleBtn?.addEventListener('click', () => {
    banner.classList.toggle('collapsed');
    localStorage.setItem('bannerCollapsed', banner.classList.contains('collapsed') ? '1' : '0');
  });

  window.addEventListener('DOMContentLoaded', () => {
    const collapsed = localStorage.getItem('bannerCollapsed') === '1';
    if (collapsed) {
      banner.classList.add('collapsed');
    }
  });
</script>

<script>
  window.onload = function () {
    const body = document.getElementById("body");
    const app = document.getElementById("installApp-bottom");
    const closeApp = document.getElementById("close-app");

    if (!getCookie("appDismissed")) {
      if (app) app.style.display = "flex";
      if (body) body.classList.add("resize-app");
    }

    if (closeApp) {
      closeApp.addEventListener("click", () => {
        if (app) app.style.display = "none";
        setCookie("appDismissed", "true", 7);
        if (body) body.classList.remove("resize-app");
      });
    }
  }
</script>
</body>
</html>
