<?php
require_once('../../../../wp-load.php');
$title = isset($_POST["title"]) ? $_POST["title"] : "";
$imagem_url_amazon = isset($_POST["imagem_url"]) ? $_POST["imagem_url"] : "";
$text = isset($_POST["text"]) ? $_POST["text"] : "";
$category = isset($_POST["category"]) ? $_POST["category"] : "";
$tags = isset($_POST["tags"]) ? $_POST["tags"] : "";

$url = isset($_POST["url"]) ? $_POST["url"] : "";
$author = isset($_POST["author"]) ? $_POST["author"] : "";
$pages = isset($_POST["pages"]) ? $_POST["pages"] : "";
$language = isset($_POST["language"]) ? $_POST["language"] : "";
$company = isset($_POST["company"]) ? $_POST["company"] : "";
$date_published = isset($_POST["date_published"]) ? $_POST["date_published"] : "";
$file_size = isset($_POST["file_size"]) ? $_POST["file_size"] : "";
$page_flip = isset($_POST["page_flip"]) ? $_POST["page_flip"] : "";
$vocabulary_tips = isset($_POST["vocabulary_tips"]) ? $_POST["vocabulary_tips"] : "";
$font_configuration = isset($_POST["font_configuration"]) ? $_POST["font_configuration"] : "";
$vocabulary_tips = isset($_POST["vocabulary_tips"]) ? $_POST["vocabulary_tips"] : "";
$isbn = isset($_POST["isbn"]) ? $_POST["isbn"] : "";
$isbn_13 = isset($_POST["isbn_13"]) ? $_POST["isbn_13"] : "";
$measurements = isset($_POST["measurements"]) ? $_POST["measurements"] : "";

///////////////////////////////////////////////////////
//Register Post Data
$post = array();
$post['post_status']   = 'publish';
$post['post_type']     = 'post'; // can be a CPT too
$post['post_title']    = $title;
$post['post_content']  = $text;
$post['post_author']   = 1;
$post['post_category']   = array($category);
$post['tags_input']   = $tags;

// Create Post
$post_id = wp_insert_post( $post );

$slug_post = get_post_field('post_name', $post_id);

// Check image file type
$wp_filetype = wp_check_filetype( $imagem_url_amazon, null );

// Add Featured Image to Post
$image_url        = $imagem_url_amazon; // Define the image URL here
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
grv_update_field("url", $url, $post_id);
grv_update_field("pages", $pages, $post_id);
grv_update_field("language", $language, $post_id);
grv_update_field("company", $company, $post_id);
grv_update_field("date_published", $date_published, $post_id);
grv_update_field("file_size", $file_size, $post_id);
grv_update_field("page_flip", $page_flip, $post_id);
grv_update_field("vocabulary_tips", $vocabulary_tips, $post_id);
grv_update_field("font_configuration", $font_configuration, $post_id);
grv_update_field("vocabulary_tips", $vocabulary_tips, $post_id);
grv_update_field("isbn", $isbn, $post_id);
grv_update_field("isbn_13", $isbn_13, $post_id);
grv_update_field("measurements", $measurements, $post_id);
grv_update_field("image", $attach_id, $post_id);
grv_update_field("author", $author, $post_id);

$dataPost = [];
$dataPost['sucesso'] = true;
echo json_encode($dataPost);