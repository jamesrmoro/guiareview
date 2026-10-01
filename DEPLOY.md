# Atualizar os conteúdos em produção

Execute os comandos **sempre na raiz deste tema**, onde está `deploy.php`.
O PHP usado precisa ter as extensões `zip` e `gd`. O tema atualizado e o
Yoast SEO devem estar ativos no WordPress de destino.

No site local:

```powershell
cd C:\wamp64\www\projetos\guiareview\wp-content\themes\guiareview
C:\wamp64\bin\php\php8.2.29\php.exe deploy.php export
```

Isso gera `deploy-content.zip`, contendo produtos publicados, anúncios,
categorias, tags, descrições, campos nativos, SEO, links de afiliado e
imagens utilizadas. O pacote não contém senhas, usuários ou estatísticas.

Envie os arquivos atualizados do tema e `deploy-content.zip` para a pasta
do tema em produção. No terminal do servidor, entre nessa pasta e execute:

```sh
php deploy.php apply --dry-run
php deploy.php apply
```

O primeiro comando verifica o pacote e informa quantos posts serão criados
ou atualizados, sem alterar conteúdo. O segundo cria um backup completo do
banco na pasta temporária do servidor, informa seu caminho e aplica o pacote.
Guarde esse backup fora da pasta pública do site.

Produtos são identificados pelo número de cadastro, com conferência do slug.
Anúncios são identificados pelo slug. Imagens são reaproveitadas por hash.
Os IDs, URLs internas, imagens, categorias primárias do Yoast e assinaturas
de contagem são adaptados ao destino. As contagens de cliques e exibições
de produção, usuários e comentários permanecem intactos. O script não
exclui conteúdos de produção ausentes no pacote e só cria revisões quando
o conteúdo mudou. Categorias, tags e configurações dos anúncios presentes
no pacote são sincronizadas a partir do site local.

Para outro nome/caminho de pacote, acrescente `--file=/caminho/pacote.zip`.
O script exige acesso ao banco e permissão para gravar em `uploads` e na
pasta temporária. Para reverter, restaure o backup SQL indicado pelo comando.

Depois do deploy, confira a home, um produto, a categoria Casa, o formulário
e o menu Anúncios. Se houver um plugin de cache/CDN, limpe seu cache.
