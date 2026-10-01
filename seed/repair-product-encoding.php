<?php
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
$replacements = array(
    'Inform?tica' => 'Informática', 're?ne' => 'reúne', '13? gera??o' => '13ª geração',
    'mem?ria' => 'memória', 'Mem?ria' => 'Memória', 'atualiza??o' => 'atualização',
    'gr?ficos' => 'gráficos', 'n?cleos' => 'núcleos', 'c?mera' => 'câmera', 'C?mera' => 'Câmera',
    'descri??o' => 'descrição', 'p?gina' => 'página', 'at? ' => 'até ', 'Resolu??o' => 'Resolução',
    '?udio' => 'Áudio', 'tr?s' => 'três', 'Conex?es' => 'Conexões',
    '15? M5' => '15 polegadas M5', '512 GB ? Meia-noite' => '512 GB - Meia-noite',
);
$clean_specs = function ( $specs ) use ( $replacements ) {
    $lines = preg_split( '/\r\n|\r|\n/', strtr( $specs, $replacements ) );
    return implode( "\n", array_values( array_filter( $lines, function ( $line ) {
        return strpos( $line, 'Avaliações de clientes:' ) !== 0;
    } ) ) );
};
foreach ( array( 1291, 1302 ) as $id ) {
    $post = get_post( $id );
    wp_update_post( wp_slash( array( 'ID' => $id, 'post_title' => strtr( $post->post_title, $replacements ), 'post_content' => strtr( $post->post_content, $replacements ) ) ) );
    foreach ( array( 'brand', 'color', 'bullets', 'specs' ) as $key ) {
        $value = get_post_meta( $id, $key, true );
        update_post_meta( $id, $key, wp_slash( $key === 'specs' ? $clean_specs( $value ) : strtr( $value, $replacements ) ) );
    }
    echo 'Corrigido produto ' . $id . PHP_EOL;
}
$repair_value = function ( $value ) use ( &$repair_value, $replacements ) {
    if ( is_string( $value ) ) { return strtr( $value, $replacements ); }
    if ( is_array( $value ) ) { return array_map( $repair_value, $value ); }
    return $value;
};
foreach ( array( 'produto-1', 'produto-2' ) as $folder ) {
    $file = dirname( __DIR__ ) . '/produtos-inserir/processados/' . $folder . '/dados.json';
    $data = json_decode( file_get_contents( $file ), true );
    $data = $repair_value( $data );
    $data['specs'] = array_values( array_filter( $data['specs'], function ( $line ) { return strpos( $line, 'Avaliações de clientes:' ) !== 0; } ) );
    file_put_contents( $file, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}
