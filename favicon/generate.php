<?php
/** Regenerate all supplied icon sizes from the owner's favicon.png. */
if ( PHP_SAPI !== 'cli' ) { exit; }
$source = imagecreatefrompng( dirname( __DIR__ ) . '/favicon.png' );
if ( ! $source ) { throw new RuntimeException( 'Invalid favicon source.' ); }
$render = function ( $width, $height, $opaque = false, $padding = 0 ) use ( $source ) {
    $image = imagecreatetruecolor( $width, $height );
    imagealphablending( $image, false ); imagesavealpha( $image, true );
    $background = $opaque ? imagecolorallocate( $image, 255, 255, 255 ) : imagecolorallocatealpha( $image, 0, 0, 0, 127 );
    imagefill( $image, 0, 0, $background );
    imagealphablending( $image, $opaque );
    $size = (int) round( min( $width, $height ) * ( 1 - $padding * 2 ) );
    imagecopyresampled( $image, $source, (int) ( ( $width - $size ) / 2 ), (int) ( ( $height - $size ) / 2 ), 0, 0, $size, $size, imagesx( $source ), imagesy( $source ) );
    return $image;
};
$outputs = array();
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( __DIR__, FilesystemIterator::SKIP_DOTS ) );
foreach ( $iterator as $file ) {
    if ( strtolower( $file->getExtension() ) !== 'png' || $file->getFilename() === 'logo.png' ) { continue; }
    $size = getimagesize( $file->getPathname() );
    if ( $size ) { $outputs[$file->getPathname()] = array( $size[0], $size[1] ); }
}
foreach ( array( 'favicon-16x16.png' => 16, 'favicon-32x32.png' => 32, 'favicon-48x48.png' => 48, 'favicon-96x96.png' => 96, 'favicon.png' => 512, 'apple-touch-icon.png' => 180, 'android-chrome-192x192.png' => 192, 'android-chrome-512x512.png' => 512, 'maskable-192x192.png' => 192, 'maskable-512x512.png' => 512, 'mstile-144x144.png' => 144, 'mstile-150x150.png' => 150, 'mstile-310x310.png' => 310 ) as $name => $size ) { $outputs[__DIR__ . '/images/' . $name] = array( $size, $size ); }
foreach ( $outputs as $file => $size ) {
    $maskable = strpos( basename( $file ), 'maskable' ) !== false;
    $image = $render( $size[0], $size[1], $maskable || strpos( str_replace( '\\', '/', $file ), '/ios/' ) !== false || basename( $file ) === 'apple-touch-icon.png', $maskable ? 0.14 : 0 );
    if ( basename( $file ) === 'monochrome.png' ) {
        for ( $y = 0; $y < imagesy( $image ); $y++ ) { for ( $x = 0; $x < imagesx( $image ); $x++ ) {
            $color = imagecolorsforindex( $image, imagecolorat( $image, $x, $y ) );
            imagesetpixel( $image, $x, $y, imagecolorallocatealpha( $image, 0, 0, 0, min( $color['red'], $color['green'], $color['blue'] ) > 190 && $color['alpha'] < 100 ? 0 : 127 ) );
        } }
    }
    imagepng( $image, $file, 9 ); imagedestroy( $image );
}
// Multi-resolution ICO with lossless PNG entries.
$sizes = array( 16, 32, 48, 64, 128, 256 ); $offset = 6 + 16 * count( $sizes ); $directory = ''; $payload = '';
foreach ( $sizes as $size ) {
    $image = $render( $size, $size ); ob_start(); imagepng( $image ); $png = ob_get_clean(); imagedestroy( $image );
    $directory .= pack( 'CCCCvvVV', $size === 256 ? 0 : $size, $size === 256 ? 0 : $size, 0, 0, 1, 32, strlen( $png ), $offset );
    $payload .= $png; $offset += strlen( $png );
}
file_put_contents( __DIR__ . '/images/favicon.ico', pack( 'vvv', 0, 1, count( $sizes ) ) . $directory . $payload );
// Safari requires a monochrome SVG; trace the white symbol from the source.
$trace = $render( 128, 128 ); $path = '';
for ( $y = 0; $y < 128; $y++ ) {
    $start = null;
    for ( $x = 0; $x <= 128; $x++ ) {
        $color = $x < 128 ? imagecolorsforindex( $trace, imagecolorat( $trace, $x, $y ) ) : null;
        $solid = $color && min( $color['red'], $color['green'], $color['blue'] ) > 190 && $color['alpha'] < 100;
        if ( $solid && $start === null ) { $start = $x; }
        if ( ! $solid && $start !== null ) { $path .= 'M' . $start . ' ' . $y . 'h' . ( $x - $start ) . 'v1H' . $start . 'z'; $start = null; }
    }
}
imagedestroy( $trace );
file_put_contents( __DIR__ . '/images/safari-pinned-tab.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128"><path fill="#000" d="' . $path . '"/></svg>' );
$manifest = array( 'name' => 'Guia Review', 'short_name' => 'Guia Review', 'id' => '../../../../', 'start_url' => '../../../../', 'scope' => '../../../../', 'display' => 'standalone', 'lang' => 'pt-BR', 'background_color' => '#ffffff', 'theme_color' => '#131921', 'icons' => array() );
foreach ( array( 'android-chrome-192x192.png' => array( 192, 'any' ), 'android-chrome-512x512.png' => array( 512, 'any' ), 'maskable-192x192.png' => array( 192, 'maskable' ), 'maskable-512x512.png' => array( 512, 'maskable' ) ) as $file => $data ) {
    $manifest['icons'][] = array( 'src' => 'images/' . $file . '?v=' . substr( hash_file( 'sha256', __DIR__ . '/images/' . $file ), 0, 12 ), 'sizes' => $data[0] . 'x' . $data[0], 'type' => 'image/png', 'purpose' => $data[1] );
}
file_put_contents( __DIR__ . '/site.webmanifest', json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
file_put_contents( __DIR__ . '/browserconfig.xml', '<?xml version="1.0" encoding="utf-8"?><browserconfig><msapplication><tile><square150x150logo src="images/mstile-150x150.png"/><TileColor>#131921</TileColor></tile></msapplication></browserconfig>' );
echo count( $outputs ) . ' PNGs, ICO, SVG, manifest and browserconfig generated.' . PHP_EOL;
