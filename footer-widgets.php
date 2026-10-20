<?php defined('ABSPATH') || exit; ?>
<dialog id="grvAdModal" class="grv-ad-modal" aria-labelledby="grvAdTitle">
    <div class="grv-ad-heading"><span>Publicidade</span><button type="button" data-ad-close aria-label="Fechar anúncio">Fechar <span aria-hidden="true">×</span></button></div>
    <a data-ad-link target="_blank" rel="nofollow sponsored noopener noreferrer">
        <img data-ad-image alt="" width="600" height="600">
        <div class="grv-ad-caption"><h2 id="grvAdTitle" data-ad-title></h2><span data-ad-cta class="grv-ad-cta"></span></div>
    </a>
</dialog>
<aside id="footer-banner" hidden aria-label="Publicidade">
    <button id="toggle-banner" type="button" aria-label="Mostrar ou ocultar anúncio" aria-expanded="true"></button>
    <a class="banner-inner" data-ad-link target="_blank" rel="nofollow sponsored noopener noreferrer">
        <img data-ad-image alt="" width="52" height="52">
        <div><span class="grv-ad-label">Publicidade</span><h3 data-ad-title></h3></div><span data-ad-cta class="grv-ad-cta"></span>
    </a>
</aside>
<aside class="cookie-notice" hidden aria-label="Aviso de privacidade">
    <div class="container-cookie">
        <div class="grv-cookie-heading"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 3 4 6v6c0 5 8 9 8 9s8-4 8-9V6l-8-3Z"/><path d="m8 12 3 3 5-6"/></svg><strong>Sua privacidade importa</strong></div>
        <div class="notice-text"><p>Usamos cookies e armazenamento local para lembrar suas preferências e melhorar sua navegação.</p></div>
        <div class="notice-buttons"><a href="<?php echo esc_url(get_privacy_policy_url()?:home_url('/politica-de-privacidade/')); ?>">Política de privacidade</a><button type="button" class="btn-cookie accept">Entendi</button></div>
    </div>
</aside>
