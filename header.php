<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="initial-scale=1.0, maximum-scale=2.0, width=device-width" />
	<?php $color = '#111226'; ?>
	<meta name="theme-color" content="<?php echo esc_attr( $color ); ?>">
	<meta name="msapplication-navbutton-color" content="<?php echo esc_attr( $color ); ?>">
	<meta name="apple-mobile-web-app-status-bar-style" content="<?php echo esc_attr( $color ); ?>">
	<?php wp_head(); ?>
</head>
<style>.adsbygoogle {text-align: center !important}</style>
<body id="body" <?php body_class(); ?>>
<div class="grv">

	<header class="grv-header">
		<div class="container grv-hd-row">
			<button class="grv-menu-toggle" id="grvMenuToggle" aria-controls="grvDrawer" aria-expanded="false" aria-label="Abrir menu de categorias">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
			</button>
			<a class="grv-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php bloginfo('name'); ?>">
				<img src="<?php bloginfo('template_url') ?>/src/images/logo-guia-review.png" width="160" height="28" alt="Guia Review">
			</a>
			<button class="grv-search-btn" id="grvOpenSearch" aria-label="Abrir busca">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
				<span>Buscar produtos…</span>
			</button>
		</div>
		<nav class="grv-cat-nav" aria-label="Categorias">
			<div class="container">
				<?php
				wp_nav_menu( array(
					'theme_location' => 'categorias',
					'container'      => false,
					'menu_class'     => 'grv-cat-list',
					'walker'         => new GRV_Menu_Walker(),
					'fallback_cb'    => false,
					'depth'          => 1,
				) );
				?>
			</div>
		</nav>
	</header>

	<dialog class="grv-drawer" id="grvDrawer" aria-label="Menu de categorias">
		<div class="grv-drawer-head">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php bloginfo('template_url') ?>/src/images/logo-guia-review.png" width="130" height="23" alt="Guia Review"></a>
			<button class="grv-drawer-close" id="grvDrawerClose" aria-label="Fechar menu">&times;</button>
		</div>
		<div class="grv-drawer-body">
			<?php grv_category_drawer_tree(); ?>
		</div>
	</dialog>

	<div class="grv-search-modal-bg" id="grvSearchModalBg" tabindex="-1" aria-hidden="true">
		<div class="grv-search-modal-box" role="dialog" aria-modal="true" aria-labelledby="grvSearchModalTitle">
			<button class="grv-search-modal-close" id="grvCloseSearch" aria-label="Fechar busca">
				<svg viewBox="0 0 40 40" width="16" height="16"><line x1="10" y1="10" x2="30" y2="30" stroke="#fff" stroke-width="3" stroke-linecap="round"/><line x1="30" y1="10" x2="10" y2="30" stroke="#fff" stroke-width="3" stroke-linecap="round"/></svg>
			</button>
			<h2 id="grvSearchModalTitle">Buscar produtos</h2>
			<form class="grv-form-search" action="<?php echo esc_url(home_url('/')); ?>" method="get" role="search">
				<input type="search" name="s" id="grvSearchInput" placeholder="O que você está procurando?" aria-label="Buscar produtos" required />
				<input type="hidden" name="post_type" value="post">
				<button type="submit">Buscar</button>
			</form>
		</div>
	</div>

	<script>
	(function () {
		var toggle = document.getElementById('grvMenuToggle');
		var drawer = document.getElementById('grvDrawer');
		var drawerClose = document.getElementById('grvDrawerClose');
		if (toggle && drawer) {
			toggle.addEventListener('click', function () {
				drawer.showModal();
				toggle.setAttribute('aria-expanded', 'true');
			});
		}
		if (drawerClose && drawer) {
			drawerClose.addEventListener('click', function () {
				drawer.close();
			});
		}
		if (drawer) {
			drawer.addEventListener('click', function (e) {
				if (e.target === drawer) drawer.close();
			});
			drawer.addEventListener('close', function () {
				if (toggle) toggle.setAttribute('aria-expanded', 'false');
			});
		}

		var openSearch = document.getElementById('grvOpenSearch');
		var searchBg = document.getElementById('grvSearchModalBg');
		var closeSearch = document.getElementById('grvCloseSearch');
		var searchInput = document.getElementById('grvSearchInput');
		if (openSearch && searchBg) {
			openSearch.addEventListener('click', function () {
				searchBg.classList.add('active');
				searchBg.setAttribute('aria-hidden', 'false');
				setTimeout(function () { searchInput && searchInput.focus(); }, 100);
			});
		}
		if (closeSearch && searchBg) {
			closeSearch.addEventListener('click', function () {
				searchBg.classList.remove('active');
				searchBg.setAttribute('aria-hidden', 'true');
			});
		}
		if (searchBg) {
			searchBg.addEventListener('click', function (e) {
				if (e.target === searchBg) {
					searchBg.classList.remove('active');
					searchBg.setAttribute('aria-hidden', 'true');
				}
			});
		}
	})();
	</script>
