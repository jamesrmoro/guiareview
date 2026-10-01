<?php
if(PHP_SAPI!=='cli'){exit;}
$dom=new DOMDocument();libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="UTF-8">'.file_get_contents(__DIR__.'/../produtos-inserir/produto-9.html'));$xp=new DOMXPath($dom);
$output=[];
foreach(['//h1','//*[contains(@class,"andes-breadcrumb")]','//*[contains(@class,"ui-pdp-description__content")]','//table','//*[contains(@class,"ui-pdp-review__rating")]','//*[contains(@class,"ui-pdp-review__amount")]','//*[contains(@class,"ui-pdp-variations")]'] as $query){foreach($xp->query($query) as $node){$output['text'][]=trim(preg_replace('/\s+/u',' ',$node->textContent));}}
foreach($xp->query('//*[contains(@class,"ui-pdp-gallery")]//img') as $node){$output['images'][]=['src'=>$node->getAttribute('src'),'data-src'=>$node->getAttribute('data-src'),'alt'=>$node->getAttribute('alt')];}
echo json_encode($output,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
