<?php
/** Run from the theme root: php deploy.php export | apply [--dry-run]. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(realpath(getcwd())!==realpath(__DIR__)){fwrite(STDERR,"Execute este arquivo na raiz do tema Guia Review.\n");exit(1);}
try{
    if(!class_exists('ZipArchive')){throw new RuntimeException('A extensão PHP zip é necessária.');}
    $command=$argv[1]??'';$dry=in_array('--dry-run',$argv,true);$package=__DIR__.'/deploy-content.zip';
    foreach($argv as $argument){if(str_starts_with($argument,'--file=')){$package=substr($argument,7);}}
    if(!in_array($command,['export','apply'],true)){echo "Uso: php deploy.php export\n     php deploy.php apply --dry-run\n     php deploy.php apply\nOpcional: --file=/caminho/pacote.zip\n";exit;}
    require dirname(__DIR__,3).'/wp-load.php';
    if(!function_exists('grv_product_fields')||!post_type_exists('grv_ad')||!defined('WPSEO_VERSION')){throw new RuntimeException('Ative o tema Guia Review atualizado e o Yoast SEO antes de executar.');}
    $admins=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID']);if(!$admins){throw new RuntimeException('Administrador não encontrado.');}wp_set_current_user((int)$admins[0]);
    require_once ABSPATH.'wp-admin/includes/image.php';require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';
    global $wpdb;
    $allowed_meta=function($key){return isset(grv_product_fields()[$key])||in_array($key,['_thumbnail_id','grv_product_number'],true)||str_starts_with($key,'_yoast_wpseo_')||str_starts_with($key,'_grv_ad_');};
    if($command==='export'){
        $manifest=['version'=>1,'source_home'=>untrailingslashit(home_url()),'created'=>gmdate('c'),'posts'=>[],'terms'=>[],'media'=>[]];$media=[];
        $include_media=function($value)use(&$media){if(is_numeric($value)&&(int)$value>0){$media[(int)$value]=true;}};
        foreach(get_posts(['post_type'=>['post','grv_ad'],'post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC']) as $post){
            $meta=[];foreach(get_post_meta($post->ID) as $key=>$values){if($allowed_meta($key)){$meta[$key]=get_post_meta($post->ID,$key,true);}}
            foreach(['_thumbnail_id','image','_yoast_wpseo_opengraph-image-id','_yoast_wpseo_twitter-image-id'] as $key){$include_media($meta[$key]??0);}
            foreach(is_array($meta['gallery']??null)?$meta['gallery']:[] as $image){$include_media($image);}
            $tax=[];foreach(['category','post_tag'] as $taxonomy){$tax[$taxonomy]=wp_get_object_terms($post->ID,$taxonomy,['fields'=>'ids']);}
            $manifest['posts'][]=['source_id'=>$post->ID,'type'=>$post->post_type,'slug'=>$post->post_name,'title'=>$post->post_title,'content'=>$post->post_content,'excerpt'=>$post->post_excerpt,'date'=>$post->post_date,'meta'=>$meta,'terms'=>$tax];
        }
        foreach(['category','post_tag'] as $taxonomy){foreach(get_terms(['taxonomy'=>$taxonomy,'hide_empty'=>false]) as $term){
            $image=get_term_meta($term->term_id,'image',true);$include_media($image);
            $seo=WPSEO_Taxonomy_Meta::get_term_meta($term->term_id,$taxonomy);foreach(['wpseo_opengraph-image-id','wpseo_twitter-image-id'] as $key){$include_media($seo[$key]??0);}
            $manifest['terms'][]=['source_id'=>$term->term_id,'taxonomy'=>$taxonomy,'slug'=>$term->slug,'name'=>$term->name,'parent'=>$term->parent,'description'=>$term->description,'image'=>$image,'seo'=>$seo];
        }}
        $zip=new ZipArchive();$temp=$package.'.tmp';if($zip->open($temp,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true){throw new RuntimeException('Não foi possível criar o pacote.');}
        foreach(array_keys($media) as $id){
            $file=get_attached_file($id);if(!$file||!is_file($file)||!wp_getimagesize($file)){throw new RuntimeException('Imagem usada no conteúdo ausente: '.$id);}
            $hash=hash_file('sha256',$file);$entry='media/'.$hash.'.'.strtolower(pathinfo($file,PATHINFO_EXTENSION));
            $zip->addFile($file,$entry);
            $urls=[];foreach(array_keys(wp_get_attachment_metadata($id)['sizes']??[]) as $size){$urls[$size]=wp_get_attachment_image_url($id,$size);}
            $manifest['media'][]=['source_id'=>$id,'file'=>$entry,'name'=>basename($file),'sha256'=>$hash,'mime'=>get_post_mime_type($id),'title'=>get_the_title($id),'alt'=>get_post_meta($id,'_wp_attachment_image_alt',true),'url'=>wp_get_attachment_url($id),'sizes'=>$urls];
        }
        $zip->addFromString('manifest.json',wp_json_encode($manifest,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        if(!$zip->close()){throw new RuntimeException('Falha ao finalizar o pacote.');}
        if(!rename($temp,$package)){throw new RuntimeException('Falha ao salvar o pacote.');}
        echo wp_json_encode(['package'=>$package,'posts'=>count($manifest['posts']),'terms'=>count($manifest['terms']),'images'=>count($manifest['media']),'bytes'=>filesize($package),'sha256'=>hash_file('sha256',$package)],JSON_PRETTY_PRINT)."\n";exit;
    }
    $zip=new ZipArchive();if($zip->open($package)!==true){throw new RuntimeException('Pacote não encontrado ou inválido.');}
    $manifest=json_decode($zip->getFromName('manifest.json')?:'',true,512,JSON_THROW_ON_ERROR);
    if(($manifest['version']??0)!==1||!isset($manifest['posts'],$manifest['terms'],$manifest['media'],$manifest['source_home'])||!preg_match('#^https?://#',$manifest['source_home'])){throw new RuntimeException('Manifesto inválido.');}
    $source_home=rtrim($manifest['source_home'],'/');$target_home=untrailingslashit(home_url());$numbers=[];$media_ids=[];
    foreach($manifest['media'] as $image){
        if(!$image['source_id']||isset($media_ids[$image['source_id']])){throw new RuntimeException('ID de imagem inválido/duplicado.');}$media_ids[$image['source_id']]=true;
        if(!preg_match('#^media/[a-f0-9]{64}\.(jpg|jpeg|png|webp|gif)$#',$image['file'])){throw new RuntimeException('Caminho de imagem inválido.');}
        $data=$zip->getFromName($image['file']);
        if($data===false||!hash_equals($image['sha256'],hash('sha256',$data))||!getimagesizefromstring($data)){throw new RuntimeException('Imagem corrompida no pacote.');}
    }
    $find_post=function($item)use($wpdb){
        $number=(int)($item['meta']['grv_product_number']??0);$match=0;
        if($item['type']==='post'&&$number){
            $ids=$wpdb->get_col($wpdb->prepare("SELECT DISTINCT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id=p.ID WHERE p.post_type='post' AND p.post_status<>'trash' AND m.meta_key='grv_product_number' AND m.meta_value=%s",(string)$number));
            if(count($ids)>1){throw new RuntimeException('Número duplicado em produção: '.$number);}$match=(int)($ids[0]??0);
        }
        $by_slug=get_page_by_path($item['slug'],OBJECT,$item['type']);
        if($match&&$by_slug&&$by_slug->ID!==$match){throw new RuntimeException('Conflito entre número e slug: '.$item['slug']);}
        return $match?:($by_slug?->ID??0);
    };
    $plan=['create'=>0,'update'=>0,'images'=>count($manifest['media']),'terms'=>count($manifest['terms']),'source'=>$source_home,'target'=>$target_home];
    foreach($manifest['posts'] as $item){
        if(!in_array($item['type'],['post','grv_ad'],true)||empty($item['slug'])||empty($item['title'])){throw new RuntimeException('Post inválido no pacote.');}
        $number=(int)($item['meta']['grv_product_number']??0);if($item['type']==='post'&&$number){if(isset($numbers[$number])){throw new RuntimeException('Número duplicado no pacote.');}$numbers[$number]=true;}
        $plan[$find_post($item)?'update':'create']++;
    }
    $term_ids=[];$term_parents=[];foreach($manifest['terms'] as $term){if(!in_array($term['taxonomy'],['category','post_tag'],true)||!$term['source_id']||isset($term_ids[$term['source_id']])){throw new RuntimeException('Categoria/tag inválida no pacote.');}$term_ids[$term['source_id']]=$term['taxonomy'];$term_parents[$term['source_id']]=$term['parent'];}
    foreach($manifest['terms'] as $term){
        if($term['parent']&&(!isset($term_ids[$term['parent']])||$term_ids[$term['parent']]!==$term['taxonomy'])){throw new RuntimeException('Categoria pai inválida no pacote.');}
        $seen=[];$ancestor=$term['source_id'];while($ancestor){if(isset($seen[$ancestor])){throw new RuntimeException('Ciclo na hierarquia de categorias.');}$seen[$ancestor]=true;$ancestor=$term_parents[$ancestor]??0;}
        if(is_numeric($term['image'])&&$term['image']&&!isset($media_ids[$term['image']])){throw new RuntimeException('Imagem de categoria ausente.');}
    }
    foreach($manifest['posts'] as $item){
        foreach(['_thumbnail_id','image','_yoast_wpseo_opengraph-image-id','_yoast_wpseo_twitter-image-id'] as $key){$value=$item['meta'][$key]??0;if(is_numeric($value)&&$value&&!isset($media_ids[$value])){throw new RuntimeException('Imagem do post ausente: '.$item['slug']);}}
        foreach($item['meta']['gallery']??[] as $image){if(!isset($media_ids[$image])){throw new RuntimeException('Imagem da galeria ausente.');}}
        foreach($item['terms'] as $taxonomy=>$ids){foreach($ids as $id){if(($term_ids[$id]??'')!==$taxonomy){throw new RuntimeException('Taxonomia do post inválida.');}}}
    }
    echo wp_json_encode($plan,JSON_PRETTY_PRINT)."\n";
    if($dry){$zip->close();echo "Simulação concluída. Nenhum conteúdo alterado.\n";exit;}

    // Complete private backup, outside the public web root, before any mutation.
    $backup=sys_get_temp_dir().'/guiareview-before-deploy-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sql.gz';$stream=gzopen($backup,'wb6');
    if(!$stream){throw new RuntimeException('Não foi possível criar o backup.');}
    @chmod($backup,0600);
    $backup_bytes=0;$write=function($sql)use($stream,&$backup_bytes){if(gzwrite($stream,$sql)!==strlen($sql)){throw new RuntimeException('Backup incompleto.');}$backup_bytes+=strlen($sql);};
    $write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
    foreach($wpdb->get_col('SHOW TABLES') as $table){
        if(!str_starts_with($table,$wpdb->prefix)){continue;}if(!preg_match('/^[A-Za-z0-9_]+$/',$table)){throw new RuntimeException('Nome de tabela inválido.');}
        $schema=$wpdb->get_row('SHOW CREATE TABLE `'.$table.'`',ARRAY_N);if(!$schema||!isset($schema[1])){throw new RuntimeException('Falha ao copiar estrutura da tabela.');}$write('DROP TABLE IF EXISTS `'.$table.'`;'."\n".$schema[1].";\n");
        for($offset=0;;$offset+=100){$rows=$wpdb->get_results('SELECT * FROM `'.$table.'` LIMIT '.$offset.',100',ARRAY_A);if($wpdb->last_error){throw new RuntimeException('Falha no backup: '.$wpdb->last_error);}if(!$rows){break;}
            foreach($rows as $row){$write('INSERT INTO `'.$table.'` ('.implode(',',array_map(fn($key)=>'`'.$key.'`',array_keys($row))).') VALUES ('.implode(',',array_map(fn($value)=>$value===null?'NULL':"X'".bin2hex((string)$value)."'",array_values($row))).");\n");}
        }
    }
    $write("SET FOREIGN_KEY_CHECKS=1;\n");if(!gzclose($stream)){throw new RuntimeException('Falha ao finalizar backup.');}
    $reader=gzopen($backup,'rb');$verified=0;while(!gzeof($reader)){$chunk=gzread($reader,1048576);if($chunk===false){throw new RuntimeException('Backup não verificável.');}$verified+=strlen($chunk);}gzclose($reader);if($verified!==$backup_bytes){throw new RuntimeException('Tamanho do backup inválido.');}echo 'Backup verificado: '.$backup."\n";
    $media_map=[];$replacements=[$source_home=>$target_home];
    foreach($manifest['media'] as $image){
        $found=get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>1,'meta_query'=>['relation'=>'OR',['key'=>'_grv_deploy_hash','value'=>$image['sha256']],['key'=>'_grv_category_source_hash','value'=>$image['sha256']],['key'=>'_grv_ad_asset_hash','value'=>$image['sha256']]]]);
        $id=$found[0]->ID??0;
        if(!$id){foreach(get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>-1,'meta_key'=>'_wp_attached_file','meta_value'=>basename($image['name']),'meta_compare'=>'LIKE']) as $candidate){$file=get_attached_file($candidate->ID);if(is_file($file)&&hash_equals($image['sha256'],hash_file('sha256',$file))){$id=$candidate->ID;break;}}}
        if($id&&(!is_file(get_attached_file($id))||!hash_equals($image['sha256'],hash_file('sha256',get_attached_file($id))))){$id=0;}
        if(!$id){$temp=wp_tempnam($image['name']);if(file_put_contents($temp,$zip->getFromName($image['file']))===false){throw new RuntimeException('Falha ao preparar imagem.');}
            $id=media_handle_sideload(['name'=>sanitize_file_name($image['name']),'tmp_name'=>$temp],0,$image['title']);if(is_wp_error($id)){@unlink($temp);throw new RuntimeException($id->get_error_message());}}
        update_post_meta($id,'_grv_deploy_hash',$image['sha256']);update_post_meta($id,'_wp_attachment_image_alt',$image['alt']);$media_map[$image['source_id']]=$id;
        $replacements[$image['url']]=wp_get_attachment_url($id);
        foreach($image['sizes']??[] as $size=>$old_url){$replacements[$old_url]=wp_get_attachment_image_url($id,$size)?:wp_get_attachment_url($id);}
    }
    $zip->close();
    $replace=function($value)use(&$replace,&$replacements){if(is_array($value)){return array_map($replace,$value);}return is_string($value)?strtr($value,$replacements):$value;};
    $term_map=[];$pending=$manifest['terms'];
    while($pending){$progress=false;foreach($pending as $index=>$term){
        if($term['parent']&&!isset($term_map[$term['parent']])){continue;}
        $parent=$term['parent']?($term_map[$term['parent']]??0):0;$existing=get_term_by('slug',$term['slug'],$term['taxonomy']);
        $args=['slug'=>$term['slug'],'parent'=>$parent,'description'=>$replace($term['description'])];
        $result=$existing?wp_update_term($existing->term_id,$term['taxonomy'],$args+['name'=>$term['name']]):wp_insert_term($term['name'],$term['taxonomy'],$args);
        if(is_wp_error($result)){throw new RuntimeException($result->get_error_message());}$id=$result['term_id'];$term_map[$term['source_id']]=$id;
        if($term['image']){update_term_meta($id,'image',$media_map[$term['image']]??$replace($term['image']));}
        $seo=$replace(is_array($term['seo'])?$term['seo']:[]);foreach(['wpseo_opengraph-image-id','wpseo_twitter-image-id'] as $key){if(!empty($seo[$key])&&is_numeric($seo[$key])){$seo[$key]=$media_map[$seo[$key]]??0;}}
        WPSEO_Taxonomy_Meta::set_values($id,$term['taxonomy'],$seo);unset($pending[$index]);$progress=true;
    }if(!$progress){throw new RuntimeException('Hierarquia de categorias inválida.');}}
    foreach($manifest['posts'] as $item){
        $existing=$find_post($item);$fields=['post_type'=>$item['type'],'post_status'=>'publish','post_title'=>$item['title'],'post_name'=>$item['slug'],'post_content'=>$replace($item['content']),'post_excerpt'=>$replace($item['excerpt'])];
        if($existing){$fields['post_content']=preg_replace_callback('/data-grv-product="\d+"\s+data-grv-click-token="[^"]*"/',fn()=>grv_offer_attributes($existing),$fields['post_content']);}
        if($existing){$fields['ID']=$existing;$current=get_post($existing);$changed=false;foreach($fields as $key=>$value){if($key!=='ID'&&(string)$current->$key!==(string)$value){$changed=true;break;}}$id=$changed?wp_update_post(wp_slash($fields),true):$existing;}
        else{$fields['post_status']='draft';$fields['post_author']=get_current_user_id();$fields['post_date']=$item['date'];$id=wp_insert_post(wp_slash($fields),true);}
        if(is_wp_error($id)||!$id){throw new RuntimeException('Falha ao salvar post: '.$item['slug']);}
        foreach($item['meta'] as $key=>$value){if(!$allowed_meta($key)){continue;}
            if(in_array($key,['_thumbnail_id','image','_yoast_wpseo_opengraph-image-id','_yoast_wpseo_twitter-image-id'],true)&&is_numeric($value)){$value=$value?($media_map[$value]??0):0;}
            elseif($key==='gallery'&&is_array($value)){$value=array_values(array_filter(array_map(fn($old)=>$media_map[$old]??0,$value)));}
            elseif($key==='_yoast_wpseo_primary_category'){$value=$term_map[$value]??'';}
            else{$value=$replace($value);}
            // Tokens are generated by the destination theme, never copied from local salts.
            if($key==='post_content'){continue;}update_post_meta($id,$key,wp_slash($value));
        }
        foreach($item['terms'] as $taxonomy=>$ids){if($item['type']==='post'){wp_set_object_terms($id,array_map(fn($old)=>(int)$term_map[$old],$ids),$taxonomy,false);}}
        // Re-sign any affiliate anchors embedded in editor content for the new IDs/salts.
        $content=get_post_field('post_content',$id);$content=preg_replace_callback('/data-grv-product="\d+"\s+data-grv-click-token="[^"]*"/',fn()=>grv_offer_attributes($id),$content);
        if(!$existing||$content!==get_post_field('post_content',$id)){$updated=wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$content,'post_status'=>'publish']),true);if(is_wp_error($updated)){throw new RuntimeException($updated->get_error_message());}}
        if($item['type']==='post'){$builder=YoastSEO()->classes->get(\Yoast\WP\SEO\Builders\Indexable_Builder::class);$repo=YoastSEO()->classes->get(\Yoast\WP\SEO\Repositories\Indexable_Repository::class);$builder->build_for_id_and_type($id,'post',$repo->find_by_id_and_type($id,'post',false));}
        echo 'OK: '.$item['slug']."\n";
    }
    update_option('grv_product_sequence',(string)(int)$wpdb->get_var("SELECT MAX(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key='grv_product_number'"),false);
    if(YoastSEO()->helpers->indexable->should_index_indexables()){
        foreach(['Indexable_Post_Indexation_Action','Indexable_Term_Indexation_Action','Indexable_Post_Type_Archive_Indexation_Action','Indexable_General_Indexation_Action','Post_Link_Indexing_Action','Term_Link_Indexing_Action'] as $name){
            $action=YoastSEO()->classes->get('Yoast\\WP\\SEO\\Actions\\Indexing\\'.$name);if(defined(get_class($action).'::UNINDEXED_COUNT_TRANSIENT')){delete_transient($action::UNINDEXED_COUNT_TRANSIENT);}
            for($batch=0;$batch<2000;$batch++){if(!$action->index()){break;}}
            if(defined(get_class($action).'::UNINDEXED_COUNT_TRANSIENT')){delete_transient($action::UNINDEXED_COUNT_TRANSIENT);}if($action->get_total_unindexed()>0){throw new RuntimeException('Índice SEO incompleto.');}
        }
        YoastSEO()->classes->get(\Yoast\WP\SEO\Actions\Indexing\Indexable_Indexing_Complete_Action::class)->complete();
    }
    wp_cache_flush();echo "Deploy concluído. Usuários, comentários e contagens de produção preservados.\n";
}catch(Throwable $error){fwrite(STDERR,'Erro: '.$error->getMessage()."\n");exit(1);}
