# Tema Guia Review

Tema WordPress com catálogo de produtos, categorias e subcategorias, busca,
links de afiliado, anúncios, relatórios administrativos e campos nativos.

Os templates atuais ficam na raiz; as funções em `functions/`, os estilos em
`css/`, os scripts em `js/` e os ícones usados em `favicon/` e `src/images/`.
O frontend utiliza `css/guiareview.css`; a tela de acesso utiliza `css/login.css`.
Os scripts administrativos declaram as dependências nativas do WordPress.
Não é necessário executar Gulp, instalar um pacote NPM ou usar ACF para servir o tema.

O ambiente atual usa Yoast SEO e Contact Form 7. A integração do Yoast e o
formulário de contato permanecem no tema. As imagens cadastradas ficam em
`wp-content/uploads/`, fora desta pasta.

Consulte `DEPLOY.md` para exportar ou aplicar conteúdo com `deploy.php`.
Essas ferramentas de publicação foram mantidas.

Os arquivos antigos foram preservados em `_arquivados/2026-10-20-limpeza/`.
Essa pasta é local, ignorada pelo Git e bloqueada para acesso HTTP. Consulte
`ARQUIVOS-ARQUIVADOS.md` para localizar os arquivos e restaurá-los.