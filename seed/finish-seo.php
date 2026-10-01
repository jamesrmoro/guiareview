<?php
if (PHP_SAPI !== 'cli') { exit; }
require dirname(__DIR__,4).'/wp-load.php';
wp_set_current_user(1);
$results=json_decode(file_get_contents(__DIR__.'/seo-analysis-results.json'),true,512,JSON_THROW_ON_ERROR);
$input=json_decode(file_get_contents(__DIR__.'/seo-analysis-input.json'),true,512,JSON_THROW_ON_ERROR);
$builder=YoastSEO()->classes->get(\Yoast\WP\SEO\Builders\Indexable_Builder::class);
$repository=YoastSEO()->classes->get(\Yoast\WP\SEO\Repositories\Indexable_Repository::class);
foreach($results as $result) {
    $matches=array_values(array_filter($input,fn($entry)=>$entry['id']===$result['id']));
    if(!$matches || $matches[0]['text']!==get_post_field('post_content',$result['id']).grv_yoast_native_content($result['id'])) {throw new RuntimeException('Analysis content is stale.');}
    WPSEO_Meta::set_value('linkdex',(int)$result['seo'],$result['id']);
    WPSEO_Meta::set_value('content_score',(string)(int)$result['readability'],$result['id']);
    $builder->build_for_id_and_type($result['id'],'post',$repository->find_by_id_and_type($result['id'],'post',false));
}
$indexed=[];
if(!YoastSEO()->helpers->indexable->should_index_indexables()) {
    echo wp_json_encode(['scores'=>array_map(fn($r)=>['id'=>$r['id'],'seo'=>$r['seo'],'readability'=>$r['readability']],$results),'indexing'=>'Yoast does not persist indexables in this local development environment. Run SEO data optimization after deployment.'],JSON_PRETTY_PRINT);
    exit;
}
foreach(['Indexable_Post_Indexation_Action','Indexable_Term_Indexation_Action','Indexable_Post_Type_Archive_Indexation_Action','Indexable_General_Indexation_Action','Post_Link_Indexing_Action','Term_Link_Indexing_Action'] as $name) {
    $action=YoastSEO()->classes->get('Yoast\\WP\\SEO\\Actions\\Indexing\\'.$name);
    if(defined(get_class($action).'::UNINDEXED_COUNT_TRANSIENT')) {delete_transient($action::UNINDEXED_COUNT_TRANSIENT);}
    $indexed[$name]=0;
    for($batch=0;$batch<200;$batch++) {
        $items=$action->index();
        $count=is_array($items)?count($items):0;
        $indexed[$name]+=$count;
        if(!$count) {break;}
    }
    if(defined(get_class($action).'::UNINDEXED_COUNT_TRANSIENT')) {delete_transient($action::UNINDEXED_COUNT_TRANSIENT);}
    if($action->get_total_unindexed()>0) {throw new RuntimeException('Incomplete indexing: '.$name.' remaining='.$action->get_total_unindexed().' processed='.$indexed[$name]);}
}
YoastSEO()->classes->get(\Yoast\WP\SEO\Actions\Indexing\Indexable_Indexing_Complete_Action::class)->complete();
echo wp_json_encode(['scores'=>array_map(fn($r)=>['id'=>$r['id'],'seo'=>$r['seo'],'readability'=>$r['readability']],$results),'indexed'=>$indexed],JSON_PRETTY_PRINT);
