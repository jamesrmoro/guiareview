# Arquivos preservados — limpeza de 20/10/2026

Pasta local: **`_arquivados/2026-10-20-limpeza/`**.

Foram retirados do tema ativo **6.536 arquivos, totalizando 667,87 MB**. O conteúdo
foi movido e preservado; isso reduz o conjunto de arquivos do tema, sem liberar
esse espaço do disco. A pasta `/_arquivados/` foi adicionada ao `.gitignore`.

## O que foi arquivado

- `amazon/`, `amazon-api/`, `produtos-inserir/` e `seed/`: importadores, fontes de produtos, auditorias, relatórios e backups de trabalhos anteriores.
- `.claude/`: o fluxo antigo de cadastro foi guardado junto com os importadores dos quais dependia.
- `app/` e `extension/`: telas de instalação e extensão de captura que não fazem parte do site atual.
- `functions/acf/`, `functions/admin/` e o registro antigo de widgets do rodapé.
- Templates antigos de blog, privacidade e relatório, sem páginas cadastradas que os utilizem; cabeçalho e rodapé próprios desse relatório.
- CSS do layout antigo, Swiper, máscara de campos, cópia adicional do jQuery e script de instalação do app.
- Favicons antigos de Android/iOS/Windows e imagens de origem ou variantes sem referência. Os ícones usados pelo site, painel e manifesto foram mantidos.
- Imagens antigas de anúncios, logo anterior, capturas de erro e configuração Gulp sem fluxo de build ativo.

Os carregamentos dessas dependências foram removidos. As regras de base ainda
necessárias foram incorporadas a `css/guiareview.css`, e `index.php` passou a
reutilizar o layout atual de arquivos do WordPress.

Foram mantidos os templates atuais, campos de produtos e categorias, links de
afiliado e suas estatísticas, SEO, anúncios, busca, formulário, login, arquivos
de favicon usados, `deploy.php`, `DEPLOY.md`, `style.css` e `screenshot.png`.
Conteúdos e imagens cadastrados no banco e em uploads não foram removidos.

## Inventário e restauração

`inventario.json`, dentro da pasta de arquivo, registra o caminho original,
destino, tamanho e SHA-256 de cada arquivo. Os **6.543 hashes** foram conferidos
após a movimentação, incluindo sete cópias dos arquivos editados.

Para restaurar um item, copie-o para o mesmo caminho relativo na raiz do tema.
Os arquivos originais que foram editados estão em `originais-editados/`, com a
estrutura de pastas preservada. Para retomar o fluxo completo anterior, restaure
também essas cópias e as dependências correspondentes. Scripts antigos precisam
voltar ao caminho original antes da execução, pois usam caminhos relativos.

A pasta contém `.htaccess` para bloquear o acesso pelo navegador. Ela não deve
ser enviada na publicação; a exclusão pelo Git já está configurada.

## Verificação

Foram conferidas 345 URLs, incluindo produtos, categorias, tags, páginas,
busca, login e resposta 404, além de 229 URLs de arquivos utilizados. Os 26
arquivos PHP ativos passaram na verificação de sintaxe. As contagens de
conteúdo no WordPress foram preservadas, e nenhum arquivo necessário ficou
ausente. A pasta de arquivo foi confirmada como ignorada pelo Git e bloqueada
para acesso HTTP.
