<?php

define( 'sprintcodes_VERSION', '0.0.5' );
add_theme_support( 'post-thumbnails' );
add_theme_support( 'title-tag' );

if (function_exists('add_image_size')) {
  add_image_size( 'small_thumbnail', 318, 423, true );
  add_image_size( 'product_thumb', 400, 400, true );
  add_image_size( 'product_card', 480, 480, true );
}

register_nav_menus( array(
  'categorias' => __( 'Menu de Categorias' ),
) );

/**
 * Walker do menu de categorias: para cada item de topo que aponta para uma
 * categoria, adiciona automaticamente um dropdown com as subcategorias reais
 * da taxonomia (sem precisar cadastrar cada subcategoria manualmente no menu).
 */
class GRV_Menu_Walker extends Walker_Nav_Menu {
  public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
    parent::start_el( $output, $item, $depth, $args, $id );

    if ( $depth !== 0 || $item->object !== 'category' ) {
      return;
    }

    $children = get_categories( array(
      'parent'     => (int) $item->object_id,
      'hide_empty' => false,
      'orderby'    => 'name',
    ) );

    if ( empty( $children ) ) {
      return;
    }

    $output .= '<ul class="sub-menu">';
    foreach ( $children as $child ) {
      $output .= '<li><a href="' . esc_url( get_category_link( $child->term_id ) ) . '">' . esc_html( $child->name ) . '</a></li>';
    }
    $output .= '</ul>';
  }
}

/**
 * Árvore de categorias recursiva (usada no drawer mobile).
 */
/**
 * Breadcrumb simples. $items = [ ['label'=>'Casa','url'=>'...'], ['label'=>'Atual','url'=>null] ]
 */
function grv_breadcrumbs( $items ) {
  if ( empty( $items ) ) {
    return;
  }
  echo '<nav class="container grv-crumbs" aria-label="Trilha de navegação"><ol>';
  echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Início</a></li>';
  $last = count( $items ) - 1;
  foreach ( $items as $i => $item ) {
    if ( ! empty( $item['url'] ) && $i !== $last ) {
      echo '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a></li>';
    } else {
      echo '<li aria-current="page">' . esc_html( $item['label'] ) . '</li>';
    }
  }
  echo '</ol></nav>';
}

/**
 * Categorias ancestrais de um termo, da mais antiga para a mais recente
 * (não inclui o próprio termo).
 */
function grv_category_ancestors( $term ) {
  $ancestors = array_reverse( get_ancestors( $term->term_id, 'category' ) );
  $trail = array();
  foreach ( $ancestors as $ancestor_id ) {
    $ancestor = get_term( $ancestor_id, 'category' );
    if ( $ancestor && ! is_wp_error( $ancestor ) ) {
      $trail[] = array( 'label' => $ancestor->name, 'url' => get_category_link( $ancestor->term_id ) );
    }
  }
  return $trail;
}

/**
 * Converte um campo textarea "Rótulo: texto" (uma linha por item) em array
 * de ['label'=>..,'text'=>..]. Linhas sem ":" viram texto simples (label vazio).
 */
function grv_parse_lines( $raw ) {
  $out = array();
  if ( empty( $raw ) ) {
    return $out;
  }
  foreach ( preg_split( "/\r\n|\r|\n/", $raw ) as $line ) {
    $line = trim( $line );
    if ( $line === '' ) {
      continue;
    }
    if ( strpos( $line, ':' ) !== false ) {
      list( $label, $text ) = explode( ':', $line, 2 );
      $out[] = array( 'label' => trim( $label ), 'text' => trim( $text ) );
    } else {
      $out[] = array( 'label' => '', 'text' => $line );
    }
  }
  return $out;
}

/**
 * Card de produto reaproveitado no grid (categoria, home, relacionados).
 */
function grv_product_card( $post_id ) {
  $title    = get_the_title( $post_id );
  $url      = get_field( 'url', $post_id );
  $image    = get_field( 'image', $post_id );
  $price    = get_field( 'price', $post_id );
  $old_price= get_field( 'old_price', $post_id );
  $rating   = get_field( 'rating', $post_id );
  $reviews  = get_field( 'review_count', $post_id );

  if ( ! $image ) {
    $image = get_the_post_thumbnail_url( $post_id, 'product_card' );
  }
  if ( ! $image ) {
    $image = get_template_directory_uri() . '/src/images/thumbnail-default.jpg';
  }

  $link = get_permalink( $post_id );
  ?>
  <a class="grv-card" href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $title ); ?>">
    <span class="thumb"><img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" width="300" height="300"></span>
    <h3><?php echo esc_html( $title ); ?></h3>
    <?php if ( $rating ) : ?>
      <span class="rating"><span class="stars" aria-hidden="true"><?php echo str_repeat( '★', (int) round( $rating ) ) . str_repeat( '☆', 5 - (int) round( $rating ) ); ?></span> <?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?><?php if ( $reviews ) : ?> (<?php echo esc_html( $reviews ); ?>)<?php endif; ?></span>
    <?php endif; ?>
    <?php if ( $price ) : ?>
      <span class="price"><?php if ( $old_price && $old_price > $price ) : ?><span class="old">R$ <?php echo esc_html( number_format( $old_price, 2, ',', '.' ) ); ?></span><?php endif; ?>R$ <?php echo esc_html( number_format( $price, 2, ',', '.' ) ); ?></span>
    <?php endif; ?>
    <span class="btn-buy-sm"><?php echo $url ? 'Ver oferta' : 'Ver detalhes'; ?></span>
  </a>
  <?php
}

function grv_category_drawer_tree( $parent_id = 0 ) {
  $cats = get_categories( array(
    'parent'     => $parent_id,
    'hide_empty' => false,
    'orderby'    => 'name',
  ) );

  if ( empty( $cats ) ) {
    return;
  }

  foreach ( $cats as $cat ) {
    $has_children = get_categories( array( 'parent' => $cat->term_id, 'hide_empty' => false, 'number' => 1 ) );
    if ( $has_children ) {
      echo '<details><summary>' . esc_html( $cat->name ) . '</summary><ul class="grv-drawer-sub"><li><a href="' . esc_url( get_category_link( $cat->term_id ) ) . '">Ver tudo em ' . esc_html( $cat->name ) . '</a></li></ul>';
      grv_category_drawer_tree( $cat->term_id );
      echo '</details>';
    } else {
      echo '<a class="grv-drawer-flat-link" href="' . esc_url( get_category_link( $cat->term_id ) ) . '">' . esc_html( $cat->name ) . '</a>';
    }
  }
}

remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

// Performance: remove desnecessários do <head> (site mais leve)
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
add_filter( 'xmlrpc_enabled', '__return_false' );
add_action( 'init', function () {
  remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
  remove_action( 'wp_head', 'wp_oembed_add_host_js' );
  wp_deregister_script( 'wp-embed' );
} );

// Functions
require(get_template_directory() . '/functions/functions/scripts-footer.php' );
require(get_template_directory() . '/functions/functions/widgets.php' );
require(get_template_directory() . '/functions/functions/login-style.php' );
require(get_template_directory() . '/functions/functions/pagination.php' );
require(get_template_directory() . '/functions/functions/seo.php' );

// ACF
require(get_template_directory() . '/functions/acf/scripts-header.php' );
require(get_template_directory() . '/functions/acf/scripts-footer.php' );
require(get_template_directory() . '/functions/acf/tutorial.php' );
require(get_template_directory() . '/functions/acf/config-page.php' );

add_filter( 'use_widgets_block_editor', '__return_false' );

function isMobile() {
  return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}
function add_additional_class_on_a($classes, $item, $args) {
    if (isset($args->add_a_class)) {
        $classes['class'] = $args->add_a_class;
    }
    return $classes;
}
add_filter('nav_menu_link_attributes', 'add_additional_class_on_a', 1, 3);

// Adiciona a coluna da imagem em miniatura na tela de administração
function custom_columns_head($defaults) {
    // Move a coluna da miniatura para a primeira posição
    $new_defaults = array_slice($defaults, 0, 1, true) + array('thumbnail' => 'Imagem em Miniatura') + array_slice($defaults, 1, null, true);
    return $new_defaults;
}

// Exibe a imagem em miniatura na coluna adicionada
function custom_columns_content($column_name, $post_ID) {
    if ($column_name == 'thumbnail') {
    	echo '<div style="display:flex;align-items: center;justify-content: center;">';
        echo get_the_post_thumbnail($post_ID, array(100, 100));
    	echo '</div>';
    }
}

// Adiciona as colunas personalizadas
add_filter('manage_posts_columns', 'custom_columns_head');
add_action('manage_posts_custom_column', 'custom_columns_content', 10, 2);

function remove_block_library_style() {
    wp_dequeue_style('wp-block-library');
}

add_action('wp_enqueue_scripts', 'remove_block_library_style');




// Adiciona uma coluna personalizada na listagem de categorias
function adicionar_coluna_imagem_social($columns) {
    $columns['imagem_social'] = 'Imagem Social';
    return $columns;
}
add_filter('manage_edit-category_columns', 'adicionar_coluna_imagem_social');

// Exibe o conteúdo na coluna personalizada
function exibir_imagem_social_coluna($content, $column_name, $term_id) {
    static $contador = 0; // Inicializa o contador

    ?>

        <style type="text/css">
            .color1 {
                background-color: #D52D60;
            }
            .color2 {
                background-color: #0078A8;
            }
            .color3 {
                background-color: #FAAD31;
            }
            .color {
                width: 15px;
                height: 15px;
                display: inline-block;
            }
        </style>

    <?php

    if ($column_name === 'imagem_social') {
        // Incrementa o contador a cada chamada
        $contador++;

        // Define as cores a serem usadas
        $cores = ['color1', 'color2', 'color3'];

        // Determina a cor com base na posição
        $cor_index = ($contador - 1) % count($cores); // Usa o contador para ciclo
        $cor = $cores[$cor_index];

        // Obtém todos os metadados para a categoria
        $metadados = get_term_meta($term_id);

        // Obtém a imagem social, se disponível
        $imagem_social = isset($metadados['rank_math_facebook_image']) ? $metadados['rank_math_facebook_image'][0] : get_bloginfo('template_url') . '/src/images/no-seo.gif';

        // Exibe a cor e a imagem social
        echo '<div class="color ' . $cor . '"></div><img style="width: 95px" src="' . $imagem_social . '">';
    }

    return $content;
}
add_filter('manage_category_custom_column', 'exibir_imagem_social_coluna', 10, 3);

function enviar_email_download() {
    // Verificar nonce para segurança
    if ( !isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'meu_nonce') ) {
        wp_send_json_error(['message' => 'Nonce inválido.']);
        exit;
    }

    // Email para o qual será enviado
    $para = "jamesrmoro@gmail.com";

    $cidade = isset($_POST['cidade']) ? sanitize_text_field($_POST['cidade']) : 'Desconhecida';
    $aparelho = isset($_POST['aparelho']) ? sanitize_text_field($_POST['aparelho']) : 'Desconhecido';

    $datetime = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    $data_horario = $datetime->format('d/m/Y \à\s H:i');

    // Assunto do email
    $assunto = "Clique no app às {$data_horario}";

    // Corpo do email
    $mensagem = "Um visitante clicou no botão de download no dia {$data_horario}.<br>";
    $mensagem .= "Cidade: {$cidade}<br>";
    $mensagem .= "Aparelho: {$aparelho}<br>";

    // Cabeçalhos do email
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Os 10 melhores livros <contato@os10melhoreslivros.com.br>'
    );

    // Enviar email
    $enviado = wp_mail($para, $assunto, $mensagem, $headers);

    // Retornar resposta AJAX
    if ($enviado) {
        wp_send_json_success(['message' => 'Email enviado com sucesso.']);
    } else {
        wp_send_json_error(['message' => 'Erro ao enviar o email.']);
    }
}

add_action('wp_ajax_enviar_email_download', 'enviar_email_download');
add_action('wp_ajax_nopriv_enviar_email_download', 'enviar_email_download');

function adicionar_scripts() {
    // Registra o script JavaScript
    wp_enqueue_script('notification-app', get_template_directory_uri() . '/js/notification-app.js', array('jquery'), null, true);

    // Passa o valor da URL do admin-ajax.php para o JavaScript
    wp_localize_script('notification-app', 'ajaxurl', admin_url('admin-ajax.php'));

    // Criar um nonce e passá-lo para o JavaScript
    wp_localize_script('notification-app', 'meu_objeto_ajax', array(
        'ajax_nonce' => wp_create_nonce('meu_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'adicionar_scripts');


define('ENVIAR_EMAIL_POR_CLIQUE', false); // ou false para desativar

add_action('wp_ajax_enviar_clique_anuncio', 'enviar_clique_anuncio');
add_action('wp_ajax_nopriv_enviar_clique_anuncio', 'enviar_clique_anuncio');

function enviar_clique_anuncio() {
    date_default_timezone_set('America/Sao_Paulo');

    if (!function_exists('update_field')) {
        wp_send_json_error(['message' => 'ACF não disponível']);
        return;
    }

    $anuncio_id   = isset($_POST['anuncio']) ? sanitize_text_field($_POST['anuncio']) : 'desconhecido';
    $pagina       = isset($_POST['pagina']) ? esc_url_raw($_POST['pagina']) : 'página desconhecida';
    $tipo_clique  = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : 'desconhecido';
    $ip           = $_SERVER['REMOTE_ADDR'];
    $user_agent   = $_SERVER['HTTP_USER_AGENT'];
    $data         = date('d/m/Y');
    $hora         = date('H:i:s');

    // Cidade via IP
    $cidade = 'Desconhecida';
    $geo = @file_get_contents("http://ip-api.com/json/$ip?fields=city,status");
    if ($geo) {
        $geo_data = json_decode($geo);
        if ($geo_data && $geo_data->status === 'success') {
            $cidade = $geo_data->city;
        }
    }

    // Sistema operacional
    $so = 'Desconhecido';
    if (stripos($user_agent, 'Windows') !== false) $so = 'Windows';
    elseif (stripos($user_agent, 'Mac OS') !== false) $so = 'macOS';
    elseif (stripos($user_agent, 'Linux') !== false) $so = 'Linux';
    elseif (stripos($user_agent, 'Android') !== false) $so = 'Android';
    elseif (stripos($user_agent, 'like Mac') !== false) $so = 'iOS';

    // Emoji e nome do anúncio
    switch ($anuncio_id) {
        case 'ads-1':
            $nome_anuncio = 'Kindle Colorsoft - rodapé';
            $emoji = '🔵';
            break;
        case 'ads-2':
            $nome_anuncio = 'Kindle Colorsoft - modal';
            $emoji = '🟣';
            break;
        case 'ads-3':
            $nome_anuncio = 'Close';
            $emoji = '🔴';
            break;
        default:
            $nome_anuncio = 'Anúncio desconhecido';
            $emoji = '⚪️';
    }

    // Registro
    $registro = [
        'data' => $data,
        'hora' => $hora,
        'link' => $pagina,
        'tipo_de_clique' => $tipo_clique,
        'sistema_operacional' => $so,
        'user_agent' => $user_agent,
        'anuncio_id' => $anuncio_id // <--- novo campo
    ];

    // JSON ACF (field: report_log)
    $historico = get_field('report_log', 'option');
    $historico_array = [];

    if ($historico) {
        $historico_array = json_decode($historico, true);
        if (!is_array($historico_array)) {
            $historico_array = [];
        }
    }

    $historico_array[] = $registro;
    update_field('report_log', json_encode($historico_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 'option');

    // Enviar email
    if (defined('ENVIAR_EMAIL_POR_CLIQUE') && ENVIAR_EMAIL_POR_CLIQUE === true) {
        $to = 'jamesrmoro@gmail.com';
        $subject = "$emoji $nome_anuncio - $hora";
        $message = "
            <strong>Anúncio:</strong> $nome_anuncio<br>
            <strong>Página:</strong> <a href=\"$pagina\">$pagina</a><br>
            <strong>Tipo:</strong> $tipo_clique<br>
            <strong>Sistema:</strong> $so<br>
            <strong>Cidade:</strong> $cidade<br>
            <strong>User Agent:</strong> $user_agent<br>
            <strong>Data/Hora:</strong> $data $hora
        ";
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Os 10 melhores livros <contato@os10melhoreslivros.com.br>'
        ];

        wp_mail($to, $subject, $message, $headers);
    }

    wp_send_json_success(['message' => 'Clique registrado com sucesso']);
}

add_action('init', function () {
    $hook = 'enviar_relatorio_diario_cliques';

    // Cancela agendamento antigo (se existir)
    if ($timestamp = wp_next_scheduled($hook)) {
        wp_unschedule_event($timestamp, $hook);
    }

    // Cria novo agendamento com novo horário
    wp_schedule_event(strtotime('23:55:00'), 'daily', $hook);
});

add_action('enviar_relatorio_diario_cliques', 'funcao_enviar_relatorio_cliques');

function funcao_enviar_relatorio_cliques() {
    $json = get_field('report_log', 'option');
    $registros = json_decode($json, true);

    if (empty($registros) || !is_array($registros)) {
        return;
    }

    // Filtra apenas os cliques de hoje
    $hoje = date('d/m/Y');
    $cliques_hoje = array_filter($registros, function ($item) use ($hoje) {
        return isset($item['data']) && $item['data'] === $hoje;
    });

    if (empty($cliques_hoje)) return;

    // Contagem por tipo
    $contagem = [
        'ads-1' => 0,
        'ads-2' => 0,
        'ads-3' => 0
    ];

    foreach ($cliques_hoje as $item) {
        $id = $item['anuncio_id'] ?? '';
        if (isset($contagem[$id])) {
            $contagem[$id]++;
        }
    }

    // Monta o corpo do e-mail
    $mensagem = "<h2>📊 Relatório de Cliques - $hoje</h2>";
    $mensagem .= "<ul>";
    $mensagem .= "<li>🔵 Kindle Colorsoft - rodapé: {$contagem['ads-1']}</li>";
    $mensagem .= "<li>🟣 Kindle Colorsoft - modal: {$contagem['ads-2']}</li>";
    $mensagem .= "<li>🔴 Fechou anúncio: {$contagem['ads-3']}</li>";
    $mensagem .= "</ul>";
    $mensagem .= "<hr><h3>Detalhes:</h3><ul>";

    foreach ($cliques_hoje as $item) {
        $hora = $item['hora'] ?? '-';
        $link = $item['link'] ?? '-';
        $so = $item['sistema_operacional'] ?? '-';
        $tipo = $item['tipo_de_clique'] ?? '-';

        $anuncio_id = isset($item['anuncio_id'])
            ? $item['anuncio_id']
            : '';

        switch ($anuncio_id) {
            case 'ads-1':
                $emoji = '🔵 rodapé';
                break;

            case 'ads-2':
                $emoji = '🟣 modal';
                break;

            case 'ads-3':
                $emoji = '🔴 fechou';
                break;

            default:
                $emoji = '⚪️';
                break;
        }

        $mensagem .= "<li>$emoji às <strong>$hora</strong> — <a href=\"$link\">$link</a> — $tipo — $so</li>";
    }
    $mensagem .= "</ul>";

    // Envia o e-mail
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: Os 10 melhores livros <contato@os10melhoreslivros.com.br>'
    ];

    wp_mail('jamesrmoro@gmail.com', "📬 Relatório de Cliques - $hoje", $mensagem, $headers);
}


// Altera o nome do remetente
add_filter('wp_mail_from_name', function($name) {
    return 'Os 10 Melhores Livros'; // Nome que aparecerá
});

// Altera o e-mail do remetente
add_filter('wp_mail_from', function($email) {
    return 'contato@os10melhoreslivros.com.br'; // Endereço de email que aparecerá
});