<?php
/** Run explicitly from CLI; preserve existing page IDs and content revisions. */
if ( PHP_SAPI !== 'cli' ) { exit; }
if ( ! defined( 'FS_METHOD' ) ) { define( 'FS_METHOD', 'direct' ); }
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
wp_set_current_user( 1 );
if ( ! file_exists( WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php' ) ) {
    $info = plugins_api( 'plugin_information', array( 'slug' => 'contact-form-7', 'fields' => array( 'sections' => false ) ) );
    if ( is_wp_error( $info ) ) { throw new RuntimeException( $info->get_error_message() ); }
    $upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
    $result = $upgrader->install( $info->download_link );
    if ( is_wp_error( $result ) || ! $result ) { throw new RuntimeException( 'Falha ao instalar Contact Form 7: ' . wp_json_encode( $upgrader->skin->get_upgrade_messages() ) ); }
}
$result = activate_plugin( 'contact-form-7/wp-contact-form-7.php' );
if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
if ( ! class_exists( 'WPCF7_ContactForm' ) ) { require_once WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php'; }
if ( ! post_type_exists( 'wpcf7_contact_form' ) ) { WPCF7_ContactForm::register_post_type(); }

// Back up the affected content before consolidating the duplicate category.
$backup_file = __DIR__ . '/site-pages-before.json';
if ( ! file_exists( $backup_file ) ) {
    file_put_contents( $backup_file, wp_json_encode( array( 'pages' => array_map( 'get_post', array( 134, 136, 3 ) ), 'duplicate_category' => get_term( 80, 'category' ), 'category_meta' => get_term_meta( 80 ), 'products' => array_map( 'get_post', array( 1291, 1302 ) ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
}
$duplicate = get_term( 80, 'category' );
if ( $duplicate && ! is_wp_error( $duplicate ) && $duplicate->name === 'Computadores e Inform?tica' ) {
    foreach ( get_objects_in_term( 80, 'category' ) as $object_id ) {
        wp_set_object_terms( $object_id, array( 1 ), 'category', true );
        wp_remove_object_terms( $object_id, 80, 'category' );
    }
    foreach ( get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false, 'parent' => 80 ) ) as $child ) {
        $result = wp_update_term( $child->term_id, 'category', array( 'parent' => 1 ) );
        if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
    }
    foreach ( get_posts( array( 'post_type' => 'nav_menu_item', 'posts_per_page' => -1, 'meta_key' => '_menu_item_object_id', 'meta_value' => '80' ) ) as $item ) {
        if ( get_post_meta( $item->ID, '_menu_item_object', true ) === 'category' ) {
            update_post_meta( $item->ID, '_menu_item_object_id', 1 );
            wp_update_post( array( 'ID' => $item->ID, 'post_title' => str_replace( 'Inform?tica', 'Informática', $item->post_title ) ) );
        }
    }
    foreach ( get_term_meta( 80 ) as $key => $values ) {
        if ( ! metadata_exists( 'term', 1, $key ) ) { update_term_meta( 1, $key, maybe_unserialize( $values[0] ) ); }
    }
    update_option( 'grv_category_redirects', array( $duplicate->slug => 1 ), false );
    $result = wp_delete_term( 80, 'category' );
    if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
}
// Only replace known corruption; preserve genuine question marks in articles.
foreach ( get_posts( array( 'post_type' => array( 'post', 'attachment', 'revision' ), 'posts_per_page' => -1, 'post_status' => 'any', 's' => 'MacBook Air' ) ) as $post ) {
    $title = str_replace( array( '15? M5', '512 GB ? Meia-noite' ), array( '15 polegadas M5', '512 GB - Meia-noite' ), $post->post_title );
    if ( $title !== $post->post_title ) { wp_update_post( array( 'ID' => $post->ID, 'post_title' => $title ) ); }
}

$existing = get_posts( array( 'post_type' => 'wpcf7_contact_form', 'posts_per_page' => 1, 'title' => 'Contato Guia Review' ) );
$form = $existing ? WPCF7_ContactForm::get_instance( $existing[0]->ID ) : WPCF7_ContactForm::get_template( array( 'locale' => 'pt_BR' ) );
$form->set_title( 'Contato Guia Review' );
$properties = $form->get_properties();
$privacy_url = home_url( '/politica-de-privacidade/' );
$properties['form'] = '<p><label>Nome [text* your-name autocomplete:name]</label></p>
<p><label>E-mail [email* your-email autocomplete:email]</label></p>
<p><label>Assunto [text* your-subject]</label></p>
<p><label>Mensagem [textarea* your-message]</label></p>
<p>[acceptance privacy] Li a <a href="' . esc_url( $privacy_url ) . '">Política de Privacidade</a> e concordo com o uso dos dados informados para responder ao meu contato. [/acceptance]</p>
<p>[submit "Enviar mensagem"]</p>';
$mail = $properties['mail'];
$mail['recipient'] = get_option( 'admin_email' );
$mail['sender'] = 'Guia Review <contato@guiareview.com.br>';
$mail['subject'] = 'Guia Review: [your-subject]';
$mail['additional_headers'] = 'Reply-To: [your-email]';
$mail['body'] = "Nome: [your-name]\nE-mail: [your-email]\nAssunto: [your-subject]\n\nMensagem:\n[your-message]\n\nEnviado pelo formulário de contato do Guia Review.";
$mail['use_html'] = false;
$properties['mail'] = $mail;
$properties['mail_2']['active'] = false;
$properties['messages'] = array_merge( $properties['messages'], array(
    'mail_sent_ok' => 'Sua mensagem foi enviada. Obrigado pelo contato!',
    'mail_sent_ng' => 'Não foi possível enviar a mensagem. Tente novamente mais tarde.',
    'validation_error' => 'Confira os campos indicados e tente novamente.',
    'invalid_required' => 'Preencha este campo.',
    'invalid_email' => 'Informe um endereço de e-mail válido.',
    'accept_terms' => 'Confirme a leitura da Política de Privacidade para continuar.',
) );
$form->set_properties( $properties );
$form->save();
if ( ! $form->id() ) { throw new RuntimeException( 'Falha ao salvar formulário.' ); }

$about = <<<'HTML'
<p>O <strong>Guia Review</strong> reúne informações sobre produtos para ajudar você a comparar opções e decidir o que faz sentido para suas necessidades.</p>
<h2>O que você encontra aqui</h2>
<p>Organizamos características, especificações, imagens e avaliações disponíveis nas páginas dos produtos. Você pode explorar as categorias, conferir detalhes e acessar a oferta na loja responsável pela venda.</p>
<h2>Como apresentamos as informações</h2>
<p>As informações podem vir dos fabricantes e das lojas. As notas exibidas correspondem às avaliações informadas na fonte consultada e podem mudar. A presença de um produto no site não significa que ele foi testado presencialmente pela nossa equipe.</p>
<p>Preços, estoque, condições de entrega e garantia devem ser confirmados diretamente com o vendedor antes da compra.</p>
<h2>Transparência sobre os links</h2>
<p>Alguns links podem ser de afiliados. Se você comprar por eles, o Guia Review poderá receber uma comissão, sem custo adicional para você. A compra e o atendimento pós-venda são realizados pela loja de destino.</p>
<h2>Fale com a gente</h2>
<p>Encontrou uma informação incorreta, tem uma sugestão de produto ou quer conversar sobre uma parceria? Entre em contato pela nossa <a href="{{contact}}">página de contato</a>.</p>
HTML;
$contact = '<p>Envie sua dúvida, sugestão, pedido de correção ou proposta de parceria pelo formulário abaixo.</p><p>Para dúvidas sobre um pedido, entrega, troca ou garantia, procure a loja em que a compra foi realizada. O Guia Review apresenta informações e links de ofertas, mas não realiza a venda dos produtos.</p><p>Os campos são obrigatórios. Informe apenas os dados necessários para explicar seu pedido; não envie senhas, dados de cartão ou documentos pessoais.</p>[contact-form-7 id="' . $form->id() . '" title="Contato Guia Review"]';
$privacy = <<<'HTML'
<p>Esta política explica como o <strong>Guia Review</strong> trata os dados relacionados à navegação e ao contato com a equipe. Para dúvidas ou solicitações sobre privacidade, utilize nossa <a href="{{contact}}">página de contato</a>.</p>
<h2>Dados do formulário de contato</h2>
<p>Quando você envia uma mensagem, usamos nome, e-mail, assunto e conteúdo da mensagem para atender à sua solicitação. O formulário encaminha esses dados por e-mail à administração do site. Na configuração atual, o Contact Form 7 não mantém um histórico das mensagens no banco de dados do WordPress.</p>
<p>O tratamento dos dados do formulário ocorre com a sua autorização e para responder ao contato solicitado. Não utilize o formulário para enviar informações sensíveis que não sejam necessárias ao atendimento.</p>
<h2>Dados técnicos e navegação</h2>
<p>O servidor e os serviços utilizados para disponibilizar o site podem processar dados técnicos, como endereço IP, navegador, data, horário e página acessada, para funcionamento, diagnóstico e segurança.</p>
<p>Ao interagir com os anúncios, o site pode registrar página, data, horário, tipo de clique, navegador e sistema operacional. O recurso de registro de cliques também pode consultar o serviço IP-API com o endereço IP para obter uma localização aproximada em nível de cidade. Esses dados ajudam a acompanhar o funcionamento dos anúncios.</p>
<h2>Cookies e armazenamento local</h2>
<p>Usamos cookies para lembrar o fechamento de anúncios e avisos durante até três ou sete dias, conforme o recurso. O armazenamento local do navegador lembra preferências como o recolhimento do banner e a confirmação de leitura do aviso de cookies.</p>
<p>Você pode apagar esses dados nas configurações do navegador. Isso pode fazer os avisos reaparecerem. O aviso de cookies registra uma preferência de interface; ele não representa autorização geral para todas as formas de tratamento de dados.</p>
<h2>Serviços externos e links de afiliados</h2>
<p>Recursos externos, como scripts hospedados no CDN da Cloudflare, podem receber dados técnicos necessários para entregar os arquivos ao navegador. Ao clicar em uma oferta, você acessa outra loja, que aplica suas próprias regras de privacidade e pode utilizar cookies para identificar a origem do acesso ou da compra.</p>
<p>Links de afiliados podem gerar comissão para o Guia Review. Consulte a política da loja de destino para entender como seus dados serão tratados durante a compra.</p>
<h2>Compartilhamento e conservação</h2>
<p>Os dados de contato são encaminhados aos serviços de hospedagem e e-mail necessários ao atendimento. Prestadores podem processar dados fora do Brasil, conforme sua infraestrutura. Dados também poderão ser tratados quando necessário para cumprir obrigações legais ou exercer direitos.</p>
<p>As mensagens e registros são conservados enquanto forem necessários ao atendimento, ao funcionamento do site ou ao cumprimento de obrigações aplicáveis. Você pode solicitar informações sobre a conservação e a exclusão pelo canal de contato.</p>
<h2>Seus direitos</h2>
<p>Nos termos da Lei Geral de Proteção de Dados Pessoais (LGPD), você pode solicitar confirmação do tratamento, acesso e correção dos seus dados, informações sobre compartilhamento e, quando aplicável, exclusão, anonimização, bloqueio ou portabilidade. Você também pode revogar o consentimento. A equipe poderá solicitar informações para confirmar sua identidade antes de atender ao pedido.</p>
<h2>Segurança e atualização desta política</h2>
<p>A administração do site é responsável por controlar o acesso aos dados e manter as ferramentas de hospedagem e atendimento. Nenhum meio de transmissão ou armazenamento é inteiramente livre de riscos. Esta política poderá ser atualizada quando os recursos do site mudarem.</p>
<p><strong>Última atualização:</strong> {{date}}.</p>
HTML;
$contents = array( 'sobre' => array( 'Sobre o Guia Review', $about ), 'contato' => array( 'Contato', $contact ), 'politica-de-privacidade' => array( 'Política de Privacidade', $privacy ) );
foreach ( $contents as $slug => $data ) {
    $page = get_page_by_path( $slug, OBJECT, 'page' );
    $content = str_replace( array( '{{contact}}', '{{date}}' ), array( esc_url( home_url( '/contato/' ) ), wp_date( 'd/m/Y' ) ), $data[1] );
    $result = wp_insert_post( wp_slash( array( 'ID' => $page ? $page->ID : 0, 'post_type' => 'page', 'post_name' => $slug, 'post_title' => $data[0], 'post_content' => $content, 'post_status' => 'publish', 'post_author' => 1 ) ), true );
    if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
    update_post_meta( $result, '_wp_page_template', 'default' );
    if ( $slug === 'politica-de-privacidade' ) { update_option( 'wp_page_for_privacy_policy', $result ); }
    echo $slug . ': ' . $result . PHP_EOL;
}
echo 'Contact Form 7: ' . $form->id() . PHP_EOL;
