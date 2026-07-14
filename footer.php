<?php
/**
 * The template for displaying the footer.
 *
 * @package Guia Review
 * @since 0.0.1
 */
?>

        <div class="search-modal-bg" id="searchModalBg" tabindex="-1" aria-hidden="true">
          <div class="search-modal-box" role="dialog" aria-modal="true" aria-labelledby="searchModalTitle">
            <button class="search-modal-close" id="closeSearchBtn" aria-label="Fechar busca">
              <!-- Ícone X SVG -->
              <svg viewBox="0 0 40 40"><line x1="10" y1="10" x2="30" y2="30" stroke="#fff" stroke-width="3" stroke-linecap="round"/><line x1="30" y1="10" x2="10" y2="30" stroke="#fff" stroke-width="3" stroke-linecap="round"/></svg>
            </button>
            <h2 id="searchModalTitle">Buscar produtos</h2>
            <form class="form-search" action="<?php echo esc_url(home_url('/')); ?>" method="get" role="search">
              <input type="search" name="s" id="searchInput" placeholder="Digite para buscar..." aria-label="Buscar desenhos" required />
              <input type="hidden" name="post_type" value="post">
              <button type="submit" aria-label="Buscar agora">Buscar produto</button>
            </form>
          </div>
        </div>
                                <script>
(function () {
  const openSearchBtn = document.getElementById('openSearchBtn');
  const searchModalBg = document.getElementById('searchModalBg');
  const closeSearchBtn = document.getElementById('closeSearchBtn');
  const searchInput = document.getElementById('searchInput');

  function openSearch() {
    searchModalBg.classList.add('active');
    searchModalBg.setAttribute('aria-hidden', 'false');
    setTimeout(() => searchInput.focus(), 100);
  }

  function closeSearch() {
    searchModalBg.classList.remove('active');
    searchModalBg.setAttribute('aria-hidden', 'true');
  }

  openSearchBtn.addEventListener('click', openSearch);
  closeSearchBtn.addEventListener('click', closeSearch);

  searchModalBg.addEventListener('click', (e) => {
    if (e.target === searchModalBg) closeSearch();
  });

  document.addEventListener('keydown', (e) => {
    if (searchModalBg.classList.contains('active') && e.key === 'Escape') closeSearch();
  });
})();
</script>

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
