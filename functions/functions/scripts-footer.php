<?php
add_action('wp_enqueue_scripts', 'sprintcodes_enqueue_scripts_input');
function sprintcodes_enqueue_scripts_input(){
	$postfix = ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min';
	$js = array(
		'js_global' => [
			'jquery-3.6.0.min',
		],
	);


	foreach ($js['js_global'] as $item) {
		wp_enqueue_script( $item, get_template_directory_uri() . "/js/" . "$item.js", array(), sprintcodes_VERSION );
	}

	wp_enqueue_style( 'single-wp', get_template_directory_uri() . "/css/single.css", array(), sprintcodes_VERSION );
	wp_enqueue_style( 'index-wp', get_template_directory_uri() . "/css/index.css", array(), sprintcodes_VERSION );
	wp_enqueue_style( 'blog-wp', get_template_directory_uri() . "/css/blog.css", array(), sprintcodes_VERSION );
	wp_enqueue_style( 'search-wp', get_template_directory_uri() . "/css/search.css", array(), sprintcodes_VERSION );
	wp_enqueue_style( 'style-wp', get_template_directory_uri() . "/css/style.css", array(), sprintcodes_VERSION );
	wp_enqueue_style( 'swiper-wp', get_template_directory_uri() . "/css/swiper-bundle.min.css", array(), sprintcodes_VERSION );

	if ( (is_single() && !is_singular('post')) || is_home() || is_front_page() || is_category() ) {
	    wp_enqueue_script('swiper', get_template_directory_uri() . "/js/swiper-bundle.min.js", array(), sprintcodes_VERSION, true);
	}

	$translation_array = array(
     	'siteURL' => get_site_url(),
     	'siteUrlTemplate' => get_bloginfo('template_url'),
  	);

  	wp_localize_script( 'jquery-3.6.0.min', 'sprintcodesData', $translation_array );
}

add_action('wp_footer', 'sprintcodes_activate_scripts');

function sprintcodes_activate_scripts(){ ?>

	<script type="text/javascript">
		$(document).ready(function() {
			$('body').on('click', '.cookie-notice .accept', function(){
	            localStorage.setItem("cookie-os10melhoreslivros", 'aceito');
	            $(".cookie-notice").fadeOut();
	        });

	        var status_cookie = localStorage.getItem('cookie-os10melhoreslivros');
	        if (localStorage.getItem("cookie-os10melhoreslivros") == null) {
	            $(".cookie-notice").css("display", "block");
	        }
	    });
	</script>

	<?php if ( (is_single() && !is_singular('post')) || is_home() || is_front_page() || is_category() ) { ?>
		<script>
			$(document).ready(function() {
				var mainSwiper = new Swiper(".mySwiper", {
					slidesPerView: 1,
					watchSlidesProgress: true,
					pagination: {
				        el: ".swiper-pagination",
				    },
				});

				var thumbnailSwiper = new Swiper(".mySwiper2", {
					spaceBetween: 10,
					effect: "cards",
					grabCursor: true,
					navigation: {
						nextEl: ".swiper-button-next",
						prevEl: ".swiper-button-prev",
					},
					thumbs: {
						swiper: mainSwiper,
					},
				});

				mainSwiper.on('slideChange', function () {
				    var currentIndex = mainSwiper.activeIndex;
				    thumbnailSwiper.slideTo(currentIndex);
				});
			});
		</script>
	<?php } ?>

<?php }