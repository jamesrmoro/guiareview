<?php
if(PHP_SAPI!=='cli'){exit;}
$data=require __DIR__.'/product-9.php';
$dom=new DOMDocument();libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="UTF-8">'.file_get_contents(__DIR__.'/../produtos-inserir/produto-9.html'));$xp=new DOMXPath($dom);
$images=[];
foreach($xp->query('//*[contains(@class,"ui-pdp-gallery")]//img') as $image){
    $src=$image->getAttribute('src');
    if(!preg_match('#/D_NQ_NP_\d+-MLB\d+_\d+-O-.*\.webp$#',$src)){continue;}
    $local=realpath(__DIR__.'/../produtos-inserir/'.preg_replace('#^\./#','',$src));
    if(!$local || !getimagesize($local)){throw new RuntimeException('Invalid product image.');}
    $images[$local]=$local;
}
$data['images']=array_values($images);
if(count($data['images'])!==8){throw new RuntimeException('Expected eight gallery images.');}
file_put_contents(__DIR__.'/../produtos-inserir/produto-9-dados.json',json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
echo count($data['images'])." images prepared\n";
