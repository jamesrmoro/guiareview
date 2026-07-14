<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv=content-type content=“text/html; charset=UTF-8”>
    <title>Associados Amazon</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=2.0, width=device-width" />
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <script
    src="https://code.jquery.com/jquery-3.7.1.js"
    integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
    crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="css/style.css?version=1">
</head>
<body>
<?php
require_once('config/config.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$currentURL = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Check if not localhost
if ($_SERVER['HTTP_HOST'] !== 'localhost') {
    // Replace "http://" with "https://"
    $currentURL = str_replace('http://', 'https://', $currentURL);
}

echo '<input id="current_url" type="hidden" value="' .$currentURL. '">';

// WordPress
function makeApiRequest($apiUrl) {
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Erro na solicitação cURL: ' . curl_error($ch);
        return false;
    }

    list($headers, $body) = explode("\r\n\r\n", $response, 2);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpStatus == 200) {
        return json_decode($body, true);
    } else {
        echo 'Erro na solicitação. Código de status HTTP: ' . $httpStatus;
        return false;
    }

    curl_close($ch);
}

function displaySelect($items, $title, $fieldName) {
    if ($items && count($items) > 0) {
        echo '<select id="' . $fieldName . '" name="' . $fieldName . '">';
        foreach ($items as $item) {
            echo '<option value="' . $item['id'] . '">' . $item['name'] . '</option>';
        }
        echo '</select>';
    } else {
        echo 'Nenhuma ' . strtolower($title) . ' encontrada.';
    }
}

$siteUrl = URLSITE;

// Lista de Usuários
$usersUrl = $siteUrl . '/wp-json/wp/v2/users';
$users = makeApiRequest($usersUrl);

// Lista de Categorias
$categoriesUrl = $siteUrl . '/wp-json/wp/v2/categories?per_page=100';
$categories = makeApiRequest($categoriesUrl);


// Artigos
// https://www.sitepoint.com/community/t/problem-with-encoding-simple-html-dom/38702/22
// https://webscraping.ai/faq/simple-html-dom/what-is-the-best-way-to-handle-character-encoding-with-simple-html-dom

// Exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>

    <div class="container">

        <div class="center">
            <div class="logo">
                <svg width="30" height="30" viewBox="0 0 152 152" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M147.923 32.3483C147.922 31.4054 147.642 30.4838 147.117 29.7006C146.592 28.9174 145.846 28.3079 144.974 27.9495L77.8071 0.35665C76.6493 -0.118883 75.3507 -0.118883 74.193 0.35665L7.02589 27.9495C6.15372 28.3078 5.40775 28.9173 4.88277 29.7005C4.35779 30.4838 4.0775 31.4054 4.07751 32.3483V119.652C4.0775 120.595 4.35779 121.516 4.88277 122.299C5.40775 123.083 6.15372 123.692 7.02589 124.05L74.193 151.643C75.3487 152.119 76.6455 152.119 77.8011 151.643C77.8585 151.624 75.3155 152.667 144.974 124.05C145.846 123.692 146.592 123.083 147.117 122.299C147.642 121.516 147.923 120.595 147.923 119.652V32.3483ZM76 55.2L51.2515 45.0331L104.736 21.7015L131.139 32.5483L76 55.2ZM13.5884 39.8429L34.9406 48.6146V73.3619C34.9406 75.9881 37.0699 78.1174 39.6961 78.1174C42.3223 78.1174 44.4515 75.9881 44.4515 73.3619V52.5218L71.2446 63.5286V140.15L13.5884 116.464V39.8429ZM76 9.89637L92.4854 16.6687L39.0012 40.0004L20.8607 32.548L76 9.89637ZM80.7555 63.5286L138.412 39.8429V116.464L80.7555 140.15V63.5286Z" fill="white"/>
                <circle cx="118.5" cy="35.5" r="29.5" fill="#FFA724"/>
                </svg>

                <h1>Produtos <span>Amazon</span></h1>
            </div>
            <form>
                <div class="flex wordpress">
                    <span class="title-box">Informações do WordPress</span>
                    <div class="field-input">
                        <label for="siteUrl">Url do site WP</label>
                        <input id="siteUrl" type="text" name="siteUrl" value="<?php echo $siteUrl; ?>">
                    </div>
                    <div class="field-input">
                        <label for="category_id_wp">Categoria</label>
                        <?php displaySelect($categories, '', 'category_id_wp'); ?>
                        <input id="category-current" type="hidden" name="" value="">
                    </div>
                    <div class="field-input">
                        <label for="tags">Tags</label>
                        <input class="tags-js" id="tags" type="text" name="tags" value="">
                    </div>
                    <div class="field-input">
                        <label for="user_wordpress">ID usuário</label>
                        <?php displaySelect($users, 'Usuários', 'user_id'); ?>
                    </div>
                    <div class="field-input opacity">
                        <label for="user_wordpress">Carregar dados</label>
                        <button class="button-load button-load-js">
                            <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" x="0" y="0" viewBox="0 0 32 32" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="M9.16 14.86a1 1 0 0 1 1.51-1.31L15 18.5V5a1 1 0 0 1 2 0v13.5l4.33-4.95a1 1 0 0 1 1.51 1.31l-6.09 7a1 1 0 0 1-1.5 0zM27 22.05a1 1 0 0 0-1 1v1.19c0 .38-.5.81-1.22.81H7.22c-.72 0-1.22-.43-1.22-.81v-1.19a1 1 0 0 0-2 0v1.19a3 3 0 0 0 3.22 2.81h17.56A3 3 0 0 0 28 24.24v-1.19a1 1 0 0 0-1-1z" data-name="Layer 41" fill="#fff" opacity="1" data-original="#000000" class=""></path></g></svg>
                            <span>Buscar na Amazon</span>
                        </button>
                    </div>
                    <div id="group-wp" class="field-input opacity">
                        <label>Registrar no WP</label>
                        <button class="button-send start show">
                            <div class="icon"></div>
                            <span>Em espera...</span>
                        </button>
                        <button class="button-send sending hide">
                            <div class="icon"></div>
                            <span>Buscando conteúdos</span>
                        </button>
                        <button class="button-send ok hide">
                            <div class="icon"></div>
                            <span>Enviar para o WP</span>
                        </button>
                    </div>
                </div>
                <ul class="tabs">
                    <li data-tab="post-1" class="active"><span class="name">Post <em>1</em></span><div class="icon"></li>
                    <li data-tab="post-2"><span class="name">Post <em>2</em></span><i class="icon"></i></li>
                    <li data-tab="post-3"><span class="name">Post <em>3</em></span><i class="icon"></i></li>
                    <li data-tab="post-4"><span class="name">Post <em>4</em></span><i class="icon"></i></li>
                    <li data-tab="post-5"><span class="name">Post <em>5</em></span><i class="icon"></i></li>
                    <li data-tab="post-6"><span class="name">Post <em>6</em></span><i class="icon"></i></li>
                    <li data-tab="post-7"><span class="name">Post <em>7</em></span><i class="icon"></i></li>
                    <li data-tab="post-8"><span class="name">Post <em>8</em></span><i class="icon"></i></li>
                    <li data-tab="post-9"><span class="name">Post <em>9</em></span><i class="icon"></i></li>
                    <li data-tab="post-10"><span class="name">Post <em>10</em></span><i class="icon"></i></li>
                </ul>
                <div class="group-tabs">
                    <?php for ($i=1; $i <= 10; $i++) { ?>
                        <div class="tab <?php echo $i == 1 ? "active" : "" ?>" id="post-<?php echo $i; ?>">
                            <div class="flex">
                                <div class="field-input">
                                    <label for="url_page_<?php echo $i; ?>">Url da página da Amazon</label>
                                    <div class="loading">
                                        <input class="input-amazon" oninput="verificarPreenchimento(this)" id="url_page_<?php echo $i; ?>" type="text" name="url_page_<?php echo $i; ?>" value="">
                                    </div>
                                </div>
                                <div class="field-input">
                                    <label for="infoLinkGoogle_<?php echo $i; ?>">Url da página do Google</label>
                                    <div class="loading">
                                        <input class="input-amazon" oninput="verificarPreenchimento(this)" id="infoLinkGoogle_<?php echo $i; ?>" type="text" name="infoLinkGoogle_<?php echo $i; ?>" value="">
                                    </div>
                                </div>
                                <div class="field-input">
                                    <label for="url_page_amazon_<?php echo $i; ?>">ASIN</label>
                                    <div class="loading">
                                        <input oninput="verificarPreenchimento(this)" class="input-amazon asin-js" id="url_page_amazon_<?php echo $i; ?>" type="text" name="url_page_amazon_<?php echo $i; ?>" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="flex">
                            <div class="field-input">
                                <label for="title_<?php echo $i; ?>">Título</label>
                                <div class="loading">
                                    <input oninput="verificarPreenchimento(this)" class="input-amazon title-js" id="title_<?php echo $i; ?>" type="text" name="title_<?php echo $i; ?>" value="">
                                </div>
                            </div>
                            <div class="field-input">
                                <label for="url_affilliate_<?php echo $i; ?>">Url de afiliado</label>
                                <div class="loading">
                                    <input oninput="verificarPreenchimento(this)" class="input-amazon url-afiliado-js" id="url_affilliate_<?php echo $i; ?>" type="text" name="url_affilliate_<?php echo $i; ?>" value="">
                                </div>
                            </div>
                            </div>
                            <div class="flex">
                                <div class="field-input">
                                    <label for="isbn_10_<?php echo $i; ?>">ISBN-10</label>
                                    <div class="loading">
                                        <input oninput="verificarPreenchimento(this)" class="input-amazon isbn_10-js" id="isbn_10_<?php echo $i; ?>" type="text" name="isbn_10_<?php echo $i; ?>" value="">
                                    </div>
                                </div>
                                <div class="field-input">
                                    <label for="isbn_13_<?php echo $i; ?>">ISBN-13</label>
                                    <div class="loading">
                                        <input oninput="verificarPreenchimento(this)" class="input-amazon isbn_13-js" id="isbn_13_<?php echo $i; ?>" type="text" name="isbn_13_<?php echo $i; ?>" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="flex">
                                <div class="field-input">
                                    <label for="author_<?php echo $i; ?>">Autor</label>
                                    <div class="loading">
                                        <input oninput="verificarPreenchimento(this)" class="input-amazon author-js" id="author_<?php echo $i; ?>" type="text" name="author_<?php echo $i; ?>" value="">
                                    </div>
                                </div>
                                <div class="field-input">
                                    <label for="company_<?php echo $i; ?>">Editora</label>
                                    <div class="loading">
                                        <input oninput="verificarPreenchimento(this)" class="input-amazon company-js" id="company_<?php echo $i; ?>" type="text" name="company_<?php echo $i; ?>" value="">
                                    </div>
                                </div>
                                <div class="field-input">
                                    <label for="dimensions_<?php echo $i; ?>">Medidas</label>
                                    <div class="loading">
                                        <input oninput="verificarPreenchimento(this)" class="input-amazon dimensions-js" id="dimensions_<?php echo $i; ?>" type="text" name="dimensions_<?php echo $i; ?>" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="flex">
                                <div class="field-input">
                                    <label for="language_<?php echo $i; ?>">Idioma</label>
                                    <div class="loading">
                                        <input oninput="verificarPreenchimento(this)" class="input-amazon language-js" id="language_<?php echo $i; ?>" type="text" name="language_<?php echo $i; ?>" value="">
                                    </div>
                                </div>
                                <div class="field-input">
                                    <label for="pages_count_<?php echo $i; ?>">Qtd de páginas</label>
                                    <div class="loading">
                                        <input oninput="verificarPreenchimento(this)" class="input-amazon pages_count-js" id="pages_count_<?php echo $i; ?>" type="text" name="pages_count_<?php echo $i; ?>" value="">
                                    </div>
                                </div>
                                <div class="field-input">
                                    <label for="formattedDate_<?php echo $i; ?>">Data da publicação</label>
                                    <div class="loading">
                                        <input oninput="verificarPreenchimento(this)" class="input-amazon formattedDate-js" id="formattedDate_<?php echo $i; ?>" type="text" name="formattedDate_<?php echo $i; ?>" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="flex">
                                <div class="field-input flex" style="justify-content: space-between;">
                                    <div style="width: 70px;height: 70px;object-fit: cover;">
                                        <img class="url_image_preview-js" style="width: 70px;height:70px;" id="url_image_preview_<?php echo $i; ?>" src="images/image-default.png">
                                    </div>
                                    <div style="width:100%">
                                        <label for="image_large_<?php echo $i; ?>">Imagem principal</label>
                                        <div class="loading">
                                            <input oninput="verificarPreenchimento(this)" class="input-amazon image_large-js" id="image_large_<?php echo $i; ?>" type="text" name="image_large_<?php echo $i; ?>" value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="field-input flex" style="justify-content: space-between;">
                                    <div style="width: 70px;height: 70px;object-fit: cover;">
                                        <img style="width: 70px;height:70px;" id="url_image2_preview_<?php echo $i; ?>" src="images/image-default.png">
                                    </div>
                                    <div style="width:100%">
                                        <label for="image_variant_large_<?php echo $i; ?>">Imagem para o post</label>
                                        <div class="loading">
                                            <input oninput="verificarPreenchimento(this)" class="input-amazon image_variant_large-js" id="image_variant_large_<?php echo $i; ?>" type="text" name="image_variant_large_<?php echo $i; ?>" value="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="field-input">
                                <label for="description_<?php echo $i; ?>">Descrição</label>
                                <div class="loading">
                                    <textarea oninput="verificarPreenchimento(this)" class="input-amazon description-js" spellcheck="false" id="description_<?php echo $i; ?>">   
                                    </textarea>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </form>

        </div>

    </div>

    <script type="text/javascript">
        $( document ).ready(function() {
            $("body").on("click", ".tabs li", function(){
                id = $(this).attr("data-tab");
                $(".tabs li").removeClass("active");
                $(this).addClass("active");
                $(".tab").removeClass("active");
                $("#"+id).addClass("active");
            });
        });
    </script>


<script src="js/script.js"></script>
<script src="js/request.js"></script>

</body>
</html>