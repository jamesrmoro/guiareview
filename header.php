<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8" />
	<title><?php wp_title( '|', true, 'right' ); ?></title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="initial-scale=1.0, maximum-scale=2.0, width=device-width" />
	<?php $color = '#A32A00'; ?>
	<meta name="theme-color" content="<?php echo $color; ?>">
	<!-- Windows Phone -->
	<meta name="msapplication-navbutton-color" content="<?php echo $color; ?>">
	<!-- iOS Safari -->
	<meta name="apple-mobile-web-app-status-bar-style" content="<?php echo $color; ?>">
	<link rel="mask-icon" href="<?php bloginfo('template_url') ?>/favicon/images/safari-pinned-tab.svg" color="<?php echo $color; ?>">
	<?php include (TEMPLATEPATH . '/favicon/favicon.php');
	$whitelist = array(
	    '127.0.0.1',
	    '::1'
	);
	if(!in_array($_SERVER['REMOTE_ADDR'], $whitelist)){ ?>
	    <link rel="manifest" href="<?php bloginfo('siteurl'); ?>/manifest.json">
	<?php }
	wp_head(); ?>
</head>
<style>.adsbygoogle {text-align: center !important}</style>
<body id="body" <?php body_class(); ?>>

