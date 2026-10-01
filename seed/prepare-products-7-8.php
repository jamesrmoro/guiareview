<?php
if(PHP_SAPI!=='cli'){exit;}
$items=require __DIR__.'/products-7-8.php';
foreach($items as $number=>$item){
    $source=json_decode(file_get_contents(__DIR__.'/../produtos-inserir/produto-'.$number.'-extracted.json'),true,512,JSON_THROW_ON_ERROR);
    $manifest=$item;
    $manifest['category_path']=$source['categories'];
    $manifest['url']='https://www.amazon.com.br/dp/'.($number===7?'B0FGGBPTM8':'B0GC55VT5S');
    $manifest['images']=array_map(function($url)use($number){$local=__DIR__.'/../produtos-inserir/produto-'.$number.'_files/'.basename(parse_url($url,PHP_URL_PATH));return file_exists($local)?realpath($local):$url;},$source['images']);
    file_put_contents(__DIR__.'/../produtos-inserir/produto-'.$number.'-dados.json',json_encode($manifest,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    echo $number.': '.count($manifest['images'])." images\n";
}
