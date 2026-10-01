<?php
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__,4).'/wp-load.php';
wp_set_current_user(1);
$number=(int)($argv[1]??0);
if($number<1){throw new RuntimeException('Invalid product number.');}
$args=[__DIR__.'/../produtos-inserir/produto-'.$number.'-dados.json'];
if(!is_file($args[0])){throw new RuntimeException('Product manifest not found.');}
$link_file=__DIR__.'/../produtos-inserir/link-produtos.txt';
if(is_file($link_file)&&preg_match('/Produto\s+'.preg_quote((string)$number,'/').'\s*-\s*(https?:\/\/\S+)/iu',file_get_contents($link_file),$match)){
    $manifest=json_decode(file_get_contents($args[0]),true,512,JSON_THROW_ON_ERROR);
    $manifest['affiliate_url']=esc_url_raw(trim($match[1]),['http','https']);
    file_put_contents($args[0],wp_json_encode($manifest,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}
require __DIR__.'/../.claude/skills/cadastrar-produto-guiareview/scripts/importar-produto.php';
