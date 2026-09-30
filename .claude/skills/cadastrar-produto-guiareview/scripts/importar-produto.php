<?php
/**
 * Cadastra um produto no Guia Review a partir de um manifesto JSON normalizado.
 *
 * Uso (o caminho do manifesto vai como argumento posicional, SEM "--" antes —
 * um "--" literal antes dele é passado como string e quebra o script):
 *   wp eval-file scripts/importar-produto.php <caminho-do-manifesto.json> --path="C:\wamp64\www\projetos\guiareview" --allow-root
 *
 * Formato do manifesto (todos os campos exceto "title" e "images" são opcionais):
 * {
 *   "title": "Cadeira de Escritório Ergonômica Mônaco Bt07 Luvinco",
 *   "content": "Parágrafo curto de introdução/review.",
 *   "category_path": ["Casa", "Móveis", "Móveis para Escritório", "Cadeiras e Banquetas"],
 *   "url": "https://...",
 *   "brand": "LUVINco",
 *   "color": "Preto",
 *   "rating": 4.5,
 *   "review_count": 465,
 *   "bullets": ["Design ergonômico: confortável e durável.", "..."],
 *   "specs": ["Marca: LUVINco", "Material: Malha"],
 *   "tags": ["cadeira ergonomica", "home office"],
 *   "images": ["C:\\...\\produtos-inserir\\cadeira\\1.jpg", "C:\\...\\2.jpg"]
 * }
 *
 * Saída: um JSON em stdout com { post_id, permalink, created_categories, missing_fields }.
 * Não apaga nem move nada em produtos-inserir — isso é responsabilidade de quem chama.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( "Direct access not allowed.\n" );
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

// $args vem do wp-cli (tudo depois de "--" na chamada de eval-file).
if ( empty( $args[0] ) ) {
	fwrite( STDERR, "Uso: wp eval-file importar-produto.php -- <manifesto.json>\n" );
	exit( 1 );
}

$manifest_path = $args[0];
if ( ! file_exists( $manifest_path ) ) {
	fwrite( STDERR, "Manifesto não encontrado: {$manifest_path}\n" );
	exit( 1 );
}

$data = json_decode( file_get_contents( $manifest_path ), true );
if ( ! is_array( $data ) || empty( $data['title'] ) ) {
	fwrite( STDERR, "Manifesto inválido ou sem \"title\".\n" );
	exit( 1 );
}

$missing_fields = array();
$get = function ( $key, $default = '' ) use ( $data, &$missing_fields ) {
	if ( empty( $data[ $key ] ) ) {
		$missing_fields[] = $key;
		return $default;
	}
	return $data[ $key ];
};

/**
 * Resolve (ou cria) a cadeia de categorias, reaproveitando qualquer nível já
 * existente por nome (case-insensitive) e criando só o que falta, mantendo a
 * hierarquia pai/filho. Retorna [ term_id_da_categoria_final, array_de_criadas ].
 */
function grv_resolve_category_path( $path ) {
	$parent_id = 0;
	$created   = array();
	$term_id   = 0;

	foreach ( $path as $name ) {
		$name = trim( $name );
		if ( $name === '' ) {
			continue;
		}

		$existing = null;
		foreach ( get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false, 'parent' => $parent_id ) ) as $term ) {
			if ( mb_strtolower( $term->name ) === mb_strtolower( $name ) ) {
				$existing = $term;
				break;
			}
		}

		if ( $existing ) {
			$term_id = $existing->term_id;
		} else {
			$result = wp_insert_term( $name, 'category', array( 'parent' => $parent_id ) );
			if ( is_wp_error( $result ) ) {
				fwrite( STDERR, 'Erro ao criar categoria "' . $name . '": ' . $result->get_error_message() . "\n" );
				exit( 1 );
			}
			$term_id   = $result['term_id'];
			$created[] = $name;
		}

		$parent_id = $term_id;
	}

	return array( $term_id, $created );
}

$category_path = isset( $data['category_path'] ) && is_array( $data['category_path'] ) ? $data['category_path'] : array();
if ( empty( $category_path ) ) {
	fwrite( STDERR, "Manifesto sem \"category_path\" — obrigatório definir ao menos a categoria final.\n" );
	exit( 1 );
}
list( $category_id, $created_categories ) = grv_resolve_category_path( $category_path );

// Evita duplicar o mesmo produto se o script for rodado de novo por engano.
$existing_post = get_page_by_title( $data['title'], OBJECT, 'post' );
if ( $existing_post ) {
	echo wp_json_encode( array(
		'post_id'            => $existing_post->ID,
		'permalink'          => get_permalink( $existing_post->ID ),
		'created_categories' => $created_categories,
		'missing_fields'     => array(),
		'note'               => 'Já existia um post com este título — nada foi recriado.',
	), JSON_UNESCAPED_UNICODE ) . "\n";
	exit( 0 );
}

$post_id = wp_insert_post( array(
	'post_title'   => $data['title'],
	'post_content' => $get( 'content', '' ),
	'post_status'  => 'publish',
	'post_type'    => 'post',
	'post_author'  => 1,
	'post_category'=> array( $category_id ),
	'tags_input'   => isset( $data['tags'] ) && is_array( $data['tags'] ) ? implode( ', ', $data['tags'] ) : '',
) );

if ( ! $post_id || is_wp_error( $post_id ) ) {
	fwrite( STDERR, "Falha ao criar o post.\n" );
	exit( 1 );
}

// Imagens: arquivos locais (da pasta do produto), ordem = ordem do array.
// A primeira vira destaque + campo "image" (legado); todas entram em "gallery".
$images = isset( $data['images'] ) && is_array( $data['images'] ) ? $data['images'] : array();
$attach_ids = array();
foreach ( $images as $i => $file_path ) {
	if ( ! file_exists( $file_path ) ) {
		fwrite( STDERR, "Aviso: imagem não encontrada, pulando: {$file_path}\n" );
		continue;
	}
	$file_array = array(
		'name'     => sanitize_file_name( basename( $file_path ) ),
		'tmp_name' => $file_path,
	);
	// media_handle_sideload move/renomeia o tmp_name; como é um arquivo do
	// usuário (não um upload temporário real), copiamos antes para não
	// apagar o original da pasta produtos-inserir.
	$tmp_copy = wp_tempnam( $file_array['name'] );
	copy( $file_path, $tmp_copy );
	$file_array['tmp_name'] = $tmp_copy;

	$attach_id = media_handle_sideload( $file_array, $post_id, $data['title'] );
	if ( is_wp_error( $attach_id ) ) {
		@unlink( $tmp_copy );
		fwrite( STDERR, 'Falha ao anexar imagem "' . $file_path . '": ' . $attach_id->get_error_message() . "\n" );
		continue;
	}
	$attach_ids[] = $attach_id;
}

if ( empty( $attach_ids ) ) {
	$missing_fields[] = 'images';
} else {
	set_post_thumbnail( $post_id, $attach_ids[0] );
	update_field( 'image', $attach_ids[0], $post_id );
	update_field( 'gallery', $attach_ids, $post_id );
}

update_field( 'url', $get( 'url' ), $post_id );
update_field( 'brand', $get( 'brand' ), $post_id );
update_field( 'color', $get( 'color' ), $post_id );
if ( ! empty( $data['rating'] ) ) {
	update_field( 'rating', $data['rating'], $post_id );
} else {
	$missing_fields[] = 'rating';
}
if ( ! empty( $data['review_count'] ) ) {
	update_field( 'review_count', $data['review_count'], $post_id );
} else {
	$missing_fields[] = 'review_count';
}

$bullets = isset( $data['bullets'] ) && is_array( $data['bullets'] ) ? $data['bullets'] : array();
if ( $bullets ) {
	update_field( 'bullets', implode( "\n", $bullets ), $post_id );
} else {
	$missing_fields[] = 'bullets';
}

$specs = isset( $data['specs'] ) && is_array( $data['specs'] ) ? $data['specs'] : array();
if ( $specs ) {
	update_field( 'specs', implode( "\n", $specs ), $post_id );
} else {
	$missing_fields[] = 'specs';
}

// Campos legados (só usados se o produto for um livro e vierem no manifesto).
foreach ( array( 'author', 'company', 'pages', 'language', 'isbn', 'isbn_13', 'measurements', 'date_published' ) as $legacy_key ) {
	if ( ! empty( $data[ $legacy_key ] ) ) {
		update_field( $legacy_key, $data[ $legacy_key ], $post_id );
	}
}

// Preço nunca é cadastrado (decisão explícita do site) — não existe chave
// "price" em lugar nenhum deste script de propósito.

echo wp_json_encode( array(
	'post_id'            => $post_id,
	'permalink'          => get_permalink( $post_id ),
	'created_categories' => $created_categories,
	'missing_fields'     => array_values( array_unique( $missing_fields ) ),
), JSON_UNESCAPED_UNICODE ) . "\n";
