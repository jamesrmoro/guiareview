<?php
/**
 * One-time seed: adds generic product fields to the existing ACF field group
 * "Informações do produto" (post ID 1236), used by all single posts.
 *
 * Does NOT touch the existing book-oriented fields (url, image, isbn, pages,
 * language, measurements, company, author, date_published, file_size,
 * page_flip, vocabulary_tips, font_configuration, isbn_13).
 *
 * Run once with:
 *   wp eval-file wp-content/themes/guiareview/seed/seed-01-acf-fields.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access not allowed.' );
}

$group_id = 1236;

function grv_make_field_key() {
	return 'field_' . substr( md5( uniqid( '', true ) ), 0, 13 );
}

function grv_field_exists_in_group( $group_id, $name ) {
	$existing = get_children( array(
		'post_parent' => $group_id,
		'post_type'   => 'acf-field',
		'numberposts' => -1,
	) );
	foreach ( $existing as $f ) {
		if ( $f->post_excerpt === $name ) {
			return true;
		}
	}
	return false;
}

$base_menu_order = 14; // 14 existing fields (menu_order 0..13)

$new_fields = array(
	array(
		'name'    => 'price',
		'label'   => 'Preço (R$)',
		'content' => array(
			'type'          => 'number',
			'instructions'  => 'Preço atual, em número. Ex: 1234.90 (use ponto, sem "R$").',
			'required'      => 0,
			'conditional_logic' => 0,
			'wrapper'       => array( 'width' => '', 'class' => '', 'id' => '' ),
			'default_value' => '',
			'placeholder'   => '1234.90',
			'prepend'       => 'R$',
			'append'        => '',
			'min'           => 0,
			'max'           => '',
			'step'          => 0.01,
		),
	),
	array(
		'name'    => 'old_price',
		'label'   => 'Preço antigo (R$)',
		'content' => array(
			'type'          => 'number',
			'instructions'  => 'Opcional. Preço "de" riscado, para mostrar desconto.',
			'required'      => 0,
			'conditional_logic' => 0,
			'wrapper'       => array( 'width' => '', 'class' => '', 'id' => '' ),
			'default_value' => '',
			'placeholder'   => '',
			'prepend'       => 'R$',
			'append'        => '',
			'min'           => 0,
			'max'           => '',
			'step'          => 0.01,
		),
	),
	array(
		'name'    => 'brand',
		'label'   => 'Marca',
		'content' => array(
			'type'          => 'text',
			'instructions'  => '',
			'required'      => 0,
			'conditional_logic' => 0,
			'wrapper'       => array( 'width' => '', 'class' => '', 'id' => '' ),
			'default_value' => '',
			'placeholder'   => '',
			'maxlength'     => '',
		),
	),
	array(
		'name'    => 'color',
		'label'   => 'Cor',
		'content' => array(
			'type'          => 'text',
			'instructions'  => '',
			'required'      => 0,
			'conditional_logic' => 0,
			'wrapper'       => array( 'width' => '', 'class' => '', 'id' => '' ),
			'default_value' => '',
			'placeholder'   => '',
			'maxlength'     => '',
		),
	),
	array(
		'name'    => 'rating',
		'label'   => 'Nota (0 a 5)',
		'content' => array(
			'type'          => 'number',
			'instructions'  => 'Nota média de avaliação, de 0 a 5. Ex: 4.5',
			'required'      => 0,
			'conditional_logic' => 0,
			'wrapper'       => array( 'width' => '', 'class' => '', 'id' => '' ),
			'default_value' => '',
			'placeholder'   => '',
			'prepend'       => '',
			'append'        => '/ 5',
			'min'           => 0,
			'max'           => 5,
			'step'          => 0.1,
		),
	),
	array(
		'name'    => 'review_count',
		'label'   => 'Quantidade de avaliações',
		'content' => array(
			'type'          => 'number',
			'instructions'  => '',
			'required'      => 0,
			'conditional_logic' => 0,
			'wrapper'       => array( 'width' => '', 'class' => '', 'id' => '' ),
			'default_value' => '',
			'placeholder'   => '',
			'prepend'       => '',
			'append'        => '',
			'min'           => 0,
			'max'           => '',
			'step'          => 1,
		),
	),
	array(
		'name'    => 'bullets',
		'label'   => 'Destaques do produto',
		'content' => array(
			'type'          => 'textarea',
			'instructions'  => 'Um item por linha. Use "Rótulo: texto" para destacar o começo em negrito. Ex: Design ergonômico: confortável e durável para longas horas de uso.',
			'required'      => 0,
			'conditional_logic' => 0,
			'wrapper'       => array( 'width' => '', 'class' => '', 'id' => '' ),
			'default_value' => '',
			'placeholder'   => '',
			'maxlength'     => '',
			'rows'          => 8,
			'new_lines'     => '',
		),
	),
	array(
		'name'    => 'specs',
		'label'   => 'Especificações técnicas',
		'content' => array(
			'type'          => 'textarea',
			'instructions'  => 'Um item por linha, no formato "Rótulo: valor". Ex: Material: Malha',
			'required'      => 0,
			'conditional_logic' => 0,
			'wrapper'       => array( 'width' => '', 'class' => '', 'id' => '' ),
			'default_value' => '',
			'placeholder'   => '',
			'maxlength'     => '',
			'rows'          => 8,
			'new_lines'     => '',
		),
	),
	array(
		'name'    => 'gallery',
		'label'   => 'Galeria de imagens',
		'content' => array(
			'type'          => 'gallery',
			'instructions'  => 'Fotos adicionais do produto (além da imagem/imagem destacada principal).',
			'required'      => 0,
			'conditional_logic' => 0,
			'wrapper'       => array( 'width' => '', 'class' => '', 'id' => '' ),
			'min'           => '',
			'max'           => '',
			'insert'        => 'append',
			'library'       => 'all',
			'min_width'     => '',
			'min_height'    => '',
			'min_size'      => '',
			'max_width'     => '',
			'max_height'    => '',
			'max_size'      => '',
			'mime_types'    => '',
			'preview_size'  => 'medium',
		),
	),
);

$created = array();
$skipped = array();

foreach ( $new_fields as $i => $field ) {
	if ( grv_field_exists_in_group( $group_id, $field['name'] ) ) {
		$skipped[] = $field['name'];
		continue;
	}

	$field_id = wp_insert_post( array(
		'post_title'  => $field['label'],
		'post_name'   => grv_make_field_key(),
		'post_excerpt'=> $field['name'],
		'post_status' => 'publish',
		'post_type'   => 'acf-field',
		'post_parent' => $group_id,
		'menu_order'  => $base_menu_order + $i,
		'post_content'=> maybe_serialize( $field['content'] ),
	) );

	if ( $field_id && ! is_wp_error( $field_id ) ) {
		$created[] = $field['name'] . ' (post ' . $field_id . ')';
	}
}

if ( function_exists( 'acf_get_store' ) ) {
	// Clear ACF's internal cache so the new fields show up immediately.
	acf_get_store( 'fields' )->reset();
	acf_get_store( 'field-groups' )->reset();
}

echo "Criados: " . ( $created ? implode( ', ', $created ) : '(nenhum)' ) . "\n";
echo "Já existiam (ignorados): " . ( $skipped ? implode( ', ', $skipped ) : '(nenhum)' ) . "\n";
