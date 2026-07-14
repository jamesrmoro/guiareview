<?php
require_once('../../../../wp-load.php');
$title = isset($_POST["title"]) ? $_POST["title"] : "";
$asin = isset($_POST["asin"]) ? $_POST["asin"] : "";

$asin = isset($_POST["asin"]) ? $_POST["asin"] : "";
$url_afiliado = isset($_POST["url_afiliado"]) ? $_POST["url_afiliado"] : "";
$isbn_10 = isset($_POST["isbn_10"]) ? $_POST["isbn_10"] : "";
$isbn_13 = isset($_POST["isbn_13"]) ? $_POST["isbn_13"] : "";
$author = isset($_POST["author"]) ? $_POST["author"] : "";
$company = isset($_POST["company"]) ? $_POST["company"] : "";
$dimensions = isset($_POST["dimensions"]) ? $_POST["dimensions"] : "";
$language = isset($_POST["language"]) ? $_POST["language"] : "";
$pages_count = isset($_POST["pages_count"]) ? $_POST["pages_count"] : "";
$formattedDate = isset($_POST["formattedDate"]) ? $_POST["formattedDate"] : "";
$image_large = isset($_POST["image_large"]) ? $_POST["image_large"] : "";
$image_variant_large = isset($_POST["image_variant_large"]) ? $_POST["image_variant_large"] : "";
$description = isset($_POST["description"]) ? $_POST["description"] : "";
$category_id_wp = isset($_POST["category_id_wp"]) ? $_POST["category_id_wp"] : "";
$tags = isset($_POST["tags"]) ? $_POST["tags"] : "";

$data = [
	"title" => $title,
	"asin" => $asin,
	"url_afiliado" => $url_afiliado,
	"isbn_10" => $isbn_10,
	"isbn_13" => $isbn_13,
	"author" => $author,
	"company" => $company,
	"dimensions" => $dimensions,
	"language" => $language,
	"pages_count" => $pages_count,
	"formattedDate" => $formattedDate,
	"image_large" => $image_large,
	"image_variant_large" => $image_variant_large,
	"description" => $description,
	"category_id_wp" => $category_id_wp,
	"tags" => $tags
];

//echo json_encode($data);

///////////////////////////////////////////////////////
//Register Post Data
$post = array();
$post['post_status']   = 'publish';
$post['post_type']     = 'post'; // can be a CPT too
$post['post_title']    = $title;
$post['post_content']  = $description;
$post['post_author']   = 1;
$post['post_category']   = array($category_id_wp);
$post['tags_input']   = $tags;

// Create Post
$post_id = wp_insert_post( $post );

$slug_post = get_post_field('post_name', $post_id);

// Check image file type
$wp_filetype = wp_check_filetype( $image_large, null );

// Add Featured Image to Post
$image_url        = $image_large; // Define the image URL here
$image_name       = 'livro-'.$slug_post.'.'.$wp_filetype['ext'];
$upload_dir       = wp_upload_dir(); // Set upload folder
$image_data       = file_get_contents($image_url); // Get image data
$unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
$filename         = basename( $unique_file_name ); // Create image file name

// Check folder permission and define file location
if( wp_mkdir_p( $upload_dir['path'] ) ) {
    $file = $upload_dir['path'] . '/' . $filename;
} else {
    $file = $upload_dir['basedir'] . '/' . $filename;
}

// Create the image  file on the server
file_put_contents( $file, $image_data );

// Set attachment data
$attachment = array(
    'post_mime_type' => $wp_filetype['type'],
    'post_title'     => sanitize_file_name( $filename ),
    'post_content'   => '',
    'post_status'    => 'inherit'
);

// Create the attachment
$attach_id = wp_insert_attachment( $attachment, $file, $post_id );

// Include image.php
require_once('../../../../wp-admin/includes/image.php');

// Define attachment metadata
$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

// Assign metadata to attachment
wp_update_attachment_metadata( $attach_id, $attach_data );

// And finally assign featured image to post
set_post_thumbnail( $post_id, $attach_id );

//Update fields ACF
update_field("url", $url_afiliado, $post_id);
update_field("pages", $pages_count, $post_id);
update_field("language", $language, $post_id);
update_field("company", $company, $post_id);
update_field("date_published", $date_published, $post_id);
update_field("isbn", $isbn_10, $post_id);
update_field("isbn_13", $isbn_13, $post_id);
update_field("measurements", $dimensions, $post_id);
update_field("image", $attach_id, $post_id);
update_field("author", $author, $post_id);

$dataPost = [];
$dataPost['sucesso'] = true;
echo json_encode($dataPost);