<?php
function login_enqueue_scripts(){ ?>
    <div class="background-cover"></div>
        <style type="text/css" media="screen">
            body.login{
                background: #f6f6fb;
            }
            .background-cover{
                background: #131921;
                position:fixed;
                top:0;
                left:0;
                z-index:10;
                overflow: hidden;
                width: 100%;
                height:100%;
            }
            #login{
                z-index:9999;
                position:relative;
                padding-top: 45px !important;
            }
            .login h1 {
                text-align: center;
                margin: 0;
                padding: 0;
            }
            .login form {
                margin-top: 0 !important;
                background-color: #fff !important;
                border-radius: 8px;
            }
            .login .message {
                margin-bottom: 0 !important;
                border-left-color: #ffa41c !important;
            }
            .login h1 a {
                background: url('<?php echo get_bloginfo('template_directory') ?>/src/images/logo-guia-review.png') no-repeat center top !important;
                margin-bottom: 20px !important;
                padding-bottom: 0px;
                background-size: 210px !important;
                width: 325px !important;
                height: 60px !important;
                display: block;
            }
            input.button-primary, button.button-primary, .button-primary{
                border-radius: 6px !important;
                border:none !important;
                background-color: #ffa41c !important;
                color: #0f1111 !important;
                font-weight:700 !important;
                text-shadow:none !important;
                }
                .button:active, .submit input:active, .button-secondary:active {
                    background: #e88c0c !important;
                    text-shadow: none !important;
                }
                .login #nav a, .login #backtoblog a {
                    color: #fff !important;
                    text-shadow: none !important;
                }
                .login #nav a:hover, .login #backtoblog a:hover{
                    color: #ffa41c !important;
                    text-shadow: none !important;
                }
                .login #nav, .login #backtoblog{
                    text-shadow: none !important;
                }
                .login form {
                    box-shadow: 0 8px 28px rgba(0,0,0,.25) !important;
                }
                .login input[type=checkbox]:checked::before{
                    color: #ffa41c !important;
                }
            </style>
    <?php } add_action( 'login_enqueue_scripts', 'login_enqueue_scripts' );

function grv_login_logo_url() {
    return home_url( '/' );
}
add_filter( 'login_headerurl', 'grv_login_logo_url' );

function grv_login_logo_title() {
    return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'grv_login_logo_title' );