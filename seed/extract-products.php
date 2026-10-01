<?php
if(PHP_SAPI!=='cli'){exit;}
foreach(isset($argv[1])?[(int)$argv[1]]:[7,8] as $number){
$path=__DIR__.'/../produtos-inserir/produto-'.$number.'.html';
$raw=file_get_contents($path);$dom=new DOMDocument();libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="UTF-8">'.$raw);$xp=new DOMXPath($dom);
$text=fn($query)=>trim(preg_replace('/\s+/u',' ',$xp->query($query)->item(0)?->textContent??''));
$data=['number'=>$number,'title'=>$text('//*[@id="productTitle"]'),'url'=>'','brand'=>$text('//*[@id="bylineInfo"]'),'rating'=>$text('//*[@id="acrPopover"]'),'review_count'=>$text('//*[@id="acrCustomerReviewText"]'),'categories'=>[],'bullets'=>[],'specs'=>[],'images'=>[]];
foreach($xp->query('//*[@id="wayfinding-breadcrumbs_feature_div"]//a') as $node){$data['categories'][]=trim($node->textContent);}
foreach($xp->query('//*[@id="feature-bullets"]//li//span[contains(@class,"a-list-item")]') as $node){$value=trim(preg_replace('/\s+/u',' ',$node->textContent));if($value){$data['bullets'][]=$value;}}
foreach($xp->query('//tr[th[contains(@class,"prodDetSectionEntry")]] | //tr[td/span[contains(@class,"a-text-bold")]]') as $row){$cells=$xp->query('./th|./td',$row);if($cells->length===2){$label=trim($cells->item(0)->textContent);$value=trim(preg_replace('/\s+/u',' ',$cells->item(1)->textContent));if($label&&!str_contains($value,'function(')&&!str_contains($value,'var ')&&!str_contains($label,'avalia')&&!str_contains($label,'Avalia')&&!str_contains($label,'Ranking')){$data['specs'][]=$label.': '.$value;}}}
foreach($xp->query('//link[@rel="canonical"]') as $node){$data['url']=$node->getAttribute('href');}
if(preg_match("/'colorImages'\\s*:\\s*\\{\\s*'initial'\\s*:\\s*A[^()]+parseJSON\\('(.+?)'\\)/s",$raw,$match)){$images=json_decode($match[1],true)?:json_decode(stripcslashes($match[1]),true);foreach($images?:[] as $image){$url=$image['hiRes']?:$image['large'];if($url){$data['images'][]=$url;}}}
file_put_contents(__DIR__.'/../produtos-inserir/produto-'.$number.'-extracted.json',json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
}
