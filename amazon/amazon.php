<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'simple_html_dom.php';

$baseURL = 'http://jamesrmoro.me/livros/categoria/livros/page-';

$numberOfPages = 10;

$pages = [];

?>
<!DOCTYPE html>
<html>
<head>
	<title>Página Amazon</title>
	<link rel="stylesheet" type="text/css" href="css/style.css">
	<link rel="stylesheet" href="https://getbootstrap.com/docs/5.0/dist/css/bootstrap.min.css">
	<script
  	src="https://code.jquery.com/jquery-3.7.1.js"
  	integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
  	crossorigin="anonymous"></script>
</head>
<body>

	<div class="container">
		<div class="bd-example">
			<h1>Página Amazon</h1>
			<form>
				<div class="mb-3">
					<label for="category>" class="form-label fw-bold">Categoria</label>
					<input id="category" type="text" class="category-js form-control" name="" value="1">
				</div>
				<div class="mb-3">
					<label for="tags" class="form-label fw-bold">Tags</label>
					<input id="tags" type="text" class="tags-js form-control" name="" value="casa1, casa 2, teste, olá, vírgula">
				</div>
				<div class="group">
					<ul class="tabs">
						<li data-tab="post-1" class="active">Post 1</li>
						<li data-tab="post-2">Post 2</li>
						<li data-tab="post-3">Post 3</li>
						<li data-tab="post-4">Post 4</li>
						<li data-tab="post-5">Post 5</li>
						<li data-tab="post-6">Post 6</li>
						<li data-tab="post-7">Post 7</li>
						<li data-tab="post-8">Post 8</li>
						<li data-tab="post-9">Post 9</li>
						<li data-tab="post-10">Post 10</li>
					</ul>
					
					<?php
	                    for ($i = 1; $i <= $numberOfPages; $i++) {
	                        $url = $baseURL . ($i);
	                        $html = file_get_html($url);

	                        if ($html !== false) {
	                            $author = $html->find('.author .a-link-normal', 0)->innertext;
	                            $title = $html->find('#productTitle', 0)->innertext;
	                            $image_url = $html->find('#landingImage img', 0)->getAttribute('data-old-hires');
	                            $resumo = $html->getElementById('drengr_desktopTabbedDescriptionOverviewContent_feature_div');
	                            if(!isset($resumo)){
	                            	$resumo = $html->getElementById('drengr_DesktopTabbedDescriptionOverviewContent_feature_div');
	                            }
	                        } else {
	                            echo "Error: Failed to fetch HTML from $url.";
	                        }

	                        ?>
	                        <div class="tab <?php echo $i == 1 ? "active" : ""; ?>" id="post-<?php echo $i; ?>">
	                        	<div class="mb-3">
									<label for="url_<?php echo $i; ?>" class="form-label fw-bold">Url da página</label>
									<input id="url_<?php echo $i; ?>" type="text" class="url-js form-control" name="" value="<?php echo $url; ?>">
								</div>
								<div class="mb-3">
									<label for="url_afiliado_<?php echo $i; ?>" class="form-label fw-bold">Url afiliado</label>
									<input id="url_afiliado_<?php echo $i; ?>" type="text" class="url-afiliado-js form-control" name="" value="">
								</div>
								<div class="mb-3">
									<label for="title_<?php echo $i; ?>" class="form-label fw-bold">Título</label>
									<input id="title_<?php echo $i; ?>" type="text" class="title-js form-control" name="" value="<?php echo $title; ?>">
								</div>
								<div class="mb-3">
									<label for="image_<?php echo $i; ?>" class="form-label fw-bold">Imagem</label>
									<input id="image_<?php echo $i; ?>" type="text" class="image-js form-control" name="" value="<?php echo $image_url; ?>">
								</div>
								<div class="mb-3">
									<label for="author_<?php echo $i; ?>" class="form-label fw-bold">Autor</label>
									<input id="author_<?php echo $i; ?>" type="text" class="author-js form-control" name="" value="<?php echo $author; ?>">
								</div>
								<div class="mb-3">
									<label for="text_<?php echo $i; ?>" class="form-label fw-bold">Texto</label>
									<textarea style="height:300px" id="text_<?php echo $i; ?>" class="text-js form-control">
										<?php
											foreach ($resumo->children  as $key) {
												echo $key;
											}
										?>
									</textarea>
								</div>
								<div class="books-attributes">
									<?php 
									foreach($html->find('ol.a-carousel')[0]->children as $e) { ?>
											<label for="text" class="form-label fw-bold"><?php echo $e->children[0]->firstChild()->plaintext; ?></label>
											<input type="" name="" value="<?php echo $e->children[0]->lastChild()->plaintext; ?>" class="form-control mb-3">
										<?php }
									?>
								</div>
	                        </div>
	                        <?php
	                    }
                    ?>

					<button type="submit" class="btn btn-primary">Enviar todos</button>
				</div>

			</form>
		</div>
	</div>
	<script src="js/script.js"></script>
</body>
</html>



