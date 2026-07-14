<?php
if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
	'key' => 'group_5ebd94547f12a',
	'title' => 'Tutorial',
	'fields' => array(
		array(
			'key' => 'field_5ebd95ddf3e73',
			'label' => 'Vídeo tutorial',
			'name' => 'video_tutorial',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'collapsed' => '',
			'min' => 0,
			'max' => 0,
			'layout' => 'row',
			'button_label' => 'Adicionar vídeo',
			'sub_fields' => array(
				array(
					'key' => 'field_5ebd95f4f3e74',
					'label' => 'Vídeo',
					'name' => 'video',
					'type' => 'oembed',
					'instructions' => 'Adicione o vídeo tutorial para que o cliente possa saber sobre as funcionalidades desenvolvidas no site',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'width' => '',
					'height' => '',
				),
			),
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'options_page',
				'operator' => '==',
				'value' => 'acf-options-tutorial',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
));

endif;