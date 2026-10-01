<?php
if(PHP_SAPI!=='cli'){exit;}
$data=require __DIR__.'/product-10.php';
$source=json_decode(file_get_contents(__DIR__.'/../produtos-inserir/produto-10-extracted.json'),true,512,JSON_THROW_ON_ERROR);
$data['images']=[];
foreach($source['images'] as $url){
    $file=__DIR__.'/../produtos-inserir/produto-10_files/'.basename(parse_url($url,PHP_URL_PATH));
    $data['images'][]=is_file($file)?realpath($file):$url;
}
file_put_contents(__DIR__.'/../produtos-inserir/produto-10-dados.json',json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
echo count($data['images'])." images prepared\n";
