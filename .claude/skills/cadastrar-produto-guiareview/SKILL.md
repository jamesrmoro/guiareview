---
name: cadastrar-produto-guiareview
description: Cadastra produtos no site WordPress real "Guia Review" (projetos/guiareview) a partir da pasta produtos-inserir do tema, criando as categorias que faltarem e deixando a página pronta para indexação no Google (título, meta description, canonical, Open Graph e JSON-LD Product automáticos via single.php — não precisa gerar nada disso na mão). Use esta skill sempre que o usuário pedir para "cadastrar produto(s)", "adicionar produto(s)", "processar a pasta produtos-inserir", "subir o que coloquei em produtos-inserir" ou qualquer variação disso referente ao tema guiareview — mesmo que ele não cite o nome da skill. Cobre também criação/reaproveitamento de categorias do WordPress e validação de que a página final não tem erro PHP.
---

# Cadastrar produto — Guia Review

Fluxo para transformar o conteúdo que o usuário solto em `produtos-inserir/` em um
post real, publicado, categorizado e pronto para o Google indexar, no site
WordPress real (não um protótipo) em `C:\wamp64\www\projetos\guiareview`.

## Por que este fluxo existe

O tema guiareview já faz todo o trabalho pesado de exibição e SEO sozinho
(`single.php` já renderiza galeria, breadcrumb, bullets, especificações,
produtos relacionados e o JSON-LD `Product` automaticamente a partir dos
campos ACF do post). Seu trabalho aqui é só **alimentar esses campos
corretamente** — não precisa (e não deve) escrever HTML, CSS ou lógica de SEO
nova para cada produto.

## Convenção da pasta `produtos-inserir/`

Caminho: `wp-content/themes/guiareview/produtos-inserir/`.

O usuário cria uma **subpasta por produto** (o nome da subpasta não importa).
Dentro dela:
- **Imagens** (`.jpg`/`.jpeg`/`.png`/`.webp`) — quantas houver. A primeira em
  ordem alfabética vira a imagem destacada; todas entram na galeria.
- **Um arquivo de dados**: `dados.txt`, `dados.md` ou `dados.json` (aceite o
  que encontrar, tentando nessa ordem). Não existe um formato rígido de texto
  — leia o arquivo e extraia o que der; o usuário pode escrever de forma
  livre ("Título: ...", listas com "-", etc.). Campos que você tenta extrair:
  título, categoria (pode vir como caminho completo "Casa > Móveis > ..." ou
  só o nome final — se só vier o nome final, tente achar essa categoria em
  qualquer nível da árvore existente antes de assumir que é top-level), marca,
  cor, url (link do produto/afiliado), nota e quantidade de avaliações,
  destaques/bullets, especificações técnicas. **Nunca peça nem cadastre
  preço** — o dono do site pediu explicitamente para não fixar preço no
  conteúdo (muda com frequência); o CTA da página é só "Ver oferta"/"Comprar
  agora" apontando pro `url`.
- Se um campo não vier, siga em frente mesmo assim — o template já esconde
  graciosamente o que está vazio. Só anote pra avisar no relatório final.

Depois de um produto ser cadastrado com sucesso, **mova a subpasta dele** para
`produtos-inserir/processados/` (crie essa pasta se não existir). Isso evita
reprocessar o mesmo produto numa chamada futura e dá ao usuário um sinal
visual do que já foi feito. Nunca apague a pasta original — só mova.

## Passo a passo

1. **Preparar o ambiente** (sempre no início, cada sessão de terminal nova):
   ```bash
   export PATH="/c/wamp64/bin/php/php8.0.30:$PATH"
   ```
   Todo comando `wp.bat` precisa de `--path="C:\wamp64\www\projetos\guiareview" --allow-root`.
   A primeira chamada `wp.bat` de uma sessão costuma demorar ~20-30s a mais
   que o normal (não é travamento) — dê um timeout generoso (90s) só nela.

2. **Desativar temporariamente o plugin `fast-indexing-api`** antes de criar
   qualquer post via `wp_insert_post`:
   ```bash
   wp.bat plugin deactivate fast-indexing-api --path="..." --allow-root
   ```
   Esse plugin tenta chamar a API do Google no hook `save_post_post` e causa
   um Fatal Error neste ambiente local (sem credenciais válidas). Reative
   (`wp.bat plugin activate fast-indexing-api ...`) assim que terminar de
   cadastrar todos os produtos da leva atual — não deixe desativado.

3. **Para cada subpasta de produto** (ignore `processados/`):
   a. Leia o arquivo de dados e as imagens.
   b. Monte um **manifesto JSON** normalizado (veja o formato completo no
      topo de `scripts/importar-produto.php`) num arquivo temporário — use
      `category_path` como array (da categoria raiz até a mais específica).
   c. Rode o script (repare: **sem** `--` antes do caminho do manifesto, um
      `--` literal aí quebra o script):
      ```bash
      wp.bat eval-file "wp-content/themes/guiareview/.claude/skills/cadastrar-produto-guiareview/scripts/importar-produto.php" "<caminho-do-manifesto.json>" --path="C:\wamp64\www\projetos\guiareview" --allow-root
      ```
      O script já resolve/cria a hierarquia de categorias (reaproveitando por
      nome, case-insensitive, qualquer nível já existente — nunca duplica),
      cria o post, sobe as imagens, preenche os campos ACF, e **nunca** seta
      preço. Ele imprime um JSON com `post_id`, `permalink`,
      `created_categories` e `missing_fields`.
   d. **Valide com curl** antes de considerar concluído — não confie só no
      "sucesso" do script:
      ```bash
      curl -s -o /tmp/check.html -w "%{http_code}\n" "<permalink>"
      grep -i "fatal error\|warning:\|notice:" /tmp/check.html
      ```
      Status deve ser 200 e o grep não deve achar nada.
   e. Mova a subpasta do produto para `produtos-inserir/processados/`.

4. **Reative o plugin** `fast-indexing-api`.

5. **Relate ao usuário**, por produto: link do post, categorias criadas do
   zero vs. reaproveitadas, e quais campos ficaram vazios/faltando (pra ele
   saber o que pode complementar depois).

## Campos ACF de referência (grupo "Informações do produto", post 1236)

Novos (genéricos, criados para produtos em geral): `url`, `brand`, `color`,
`rating` (0–5), `review_count`, `bullets` (textarea, uma linha por item,
formato livre "Rótulo: texto"), `specs` (textarea, mesma lógica, "Rótulo:
valor"), `gallery` (campo ACF Gallery — array de anexos), `image` (campo
legado de imagem única, ainda usado como fallback pelo single.php).

Legados (só preencher se o produto for mesmo um livro): `author`, `company`,
`pages`, `language`, `isbn`, `isbn_13`, `measurements`, `date_published`.

**Não existe campo de preço e não deve ser criado um.**

## Armadilha já conhecida: o campo "gallery"

Alguns posts antigos do site (antes deste fluxo existir) já tinham um
`meta_key` próprio chamado `gallery`, mas em formato diferente (array simples
de IDs de anexo, tipo `["1212","1211"]`), não no formato que o campo ACF
Gallery espera (array de arrays com sub-chave `url`). O `single.php` já trata
os dois formatos ao ler esse campo — só um lembrete de que esse tipo de
inconsistência existe neste projeto, caso você mexa em algo relacionado a
`gallery` fora deste script.

## Branch de trabalho

O tema está numa branch git chamada `redesign/guiareview-theme` (não `main`).
Opere nela, a menos que o usuário diga explicitamente o contrário. Depois de
cadastrar os produtos, pode fazer um commit dessa leva se o usuário pedir
(o histórico do DB — posts/categorias — não é versionado pelo git, só os
arquivos do tema; o script fica commitado, o conteúdo cadastrado não).

## Se algo além de "cadastrar produto" for pedido

Esta skill cobre só o cadastro em si. Se o pedido envolver mudar o design,
CSS, ou o comportamento do `single.php`/`category.php`, trate como uma tarefa
normal de edição de tema (leia os arquivos, entenda o contexto, teste com
`php -l` e curl como sempre) — não force esse tipo de mudança a passar por
este fluxo de importação.
