<?php
/**
 * One-time seed: registers one real example product (the Mônaco Bt07 Luvinco
 * office chair) in the existing "Cadeiras e Banquetas" category (term 11),
 * using the new generic product ACF fields added by seed-01-acf-fields.php.
 *
 * Run once with:
 *   wp eval-file wp-content/themes/guiareview/seed/seed-02-produto-cadeira.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access not allowed.' );
}

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$category_id = 11; // Cadeiras e Banquetas
$title       = 'Cadeira de Escritório Ergonômica Mônaco Bt07 Luvinco';

$existing = get_page_by_title( $title, OBJECT, 'post' );
if ( $existing ) {
	echo "Já existe (post {$existing->ID}), nada foi criado.\n";
	return;
}

$content = "A cadeira Mônaco Bt07 Luvinco é uma opção ergonômica em malha respirável, pensada para quem passa longas horas sentado no home office. Neste review, veja as principais características, especificações técnicas e o que dizem as avaliações de quem já comprou.";

$post_id = wp_insert_post( array(
	'post_title'   => $title,
	'post_content' => $content,
	'post_status'  => 'publish',
	'post_type'    => 'post',
	'post_author'  => 1,
	'post_category'=> array( $category_id ),
	'tags_input'   => 'cadeira ergonomica, cadeira de escritorio, home office',
) );

if ( ! $post_id || is_wp_error( $post_id ) ) {
	echo "Falha ao criar o post.\n";
	return;
}

$images = array(
	'https://m.media-amazon.com/images/I/61shX0F7OgL._AC_SL1024_.jpg',
	'https://m.media-amazon.com/images/I/61Ec+XoPuBL._AC_SL1200_.jpg',
	'https://m.media-amazon.com/images/I/71yEUhdKq-L._AC_SL1200_.jpg',
	'https://m.media-amazon.com/images/I/617ClvAj3tL._AC_SL1200_.jpg',
	'https://m.media-amazon.com/images/I/71NqfqCPXXL._AC_SL1200_.jpg',
	'https://m.media-amazon.com/images/I/61h9MJ9Pr5L._AC_SL1200_.jpg',
	'https://m.media-amazon.com/images/I/619dkJlJjsL._AC_SL1200_.jpg',
	'https://m.media-amazon.com/images/I/61KoQh15+oL._AC_SL1200_.jpg',
);

$attach_ids = array();
foreach ( $images as $i => $url ) {
	$tmp = download_url( $url );
	if ( is_wp_error( $tmp ) ) {
		echo "Falha ao baixar imagem {$i}: " . $tmp->get_error_message() . "\n";
		continue;
	}
	$file_array = array(
		'name'     => 'cadeira-monaco-bt07-luvinco-' . ( $i + 1 ) . '.jpg',
		'tmp_name' => $tmp,
	);
	$attach_id = media_handle_sideload( $file_array, $post_id, $title );
	if ( is_wp_error( $attach_id ) ) {
		@unlink( $tmp );
		echo "Falha ao anexar imagem {$i}: " . $attach_id->get_error_message() . "\n";
		continue;
	}
	$attach_ids[] = $attach_id;
}

if ( empty( $attach_ids ) ) {
	echo "Nenhuma imagem foi anexada. Post {$post_id} criado sem imagens.\n";
} else {
	set_post_thumbnail( $post_id, $attach_ids[0] );
	update_field( 'image', $attach_ids[0], $post_id );
	update_field( 'gallery', $attach_ids, $post_id );
}

update_field( 'url', 'https://www.amazon.com.br/dp/B0DQVLF642', $post_id );
update_field( 'brand', 'LUVINco', $post_id );
update_field( 'color', 'Preto', $post_id );
update_field( 'rating', 4.5, $post_id );
update_field( 'review_count', 465, $post_id );
// Preço não é cadastrado: o card/buybox mostram só "Ver oferta" (preço muda
// com frequência e não deve ser fixado no conteúdo).

update_field( 'bullets', implode( "\n", array(
	'Design ergonômico: confortável e durável, ideal para longas horas de uso.',
	'Estofamento em malha: material respirável que evita o superaquecimento.',
	'Ajuste de altura: assento ajustável de 50 cm a 57 cm de altura desde o chão.',
	'Giratória e com rodas: mobilidade fácil para um uso versátil.',
	'Suporte lombar regulável: oferece o suporte ideal para a região lombar.',
	'Braços confortáveis: apoios de braços projetados para maior conforto.',
	'Apoio para cabeça: encosto ajustável para apoio completo.',
	'Suporta até 200 kg: estrutura robusta para maior durabilidade.',
	'Dimensões completas: 46 cm de largura, até 126 cm de altura total e 50 cm de profundidade.',
	'Enchimento em espuma: garantia de conforto com alta densidade.',
) ), $post_id );

update_field( 'specs', implode( "\n", array(
	'Marca: LUVINco',
	'Cor: Preto',
	'Material: Malha',
	'Dimensões: 65P x 55L x 130A cm',
	'Capacidade: 200 kg',
	'Posições reclináveis: 6',
	'Peso: 19 kg',
	'Profundidade do assento: 50 cm',
) ), $post_id );

echo "Produto criado: post {$post_id}, " . count( $attach_ids ) . " imagens anexadas.\n";
echo 'Link: ' . get_permalink( $post_id ) . "\n";
