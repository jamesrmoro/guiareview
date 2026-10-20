<?php
/** Lightweight branding for the native WordPress sign-in screen. */
defined( 'ABSPATH' ) || exit;

add_action( 'login_enqueue_scripts', function () {
    wp_enqueue_style( 'grv-login', get_template_directory_uri() . '/css/login.css', array( 'login' ), filemtime( get_template_directory() . '/css/login.css' ) );
} );

add_action( 'login_header', function () {
    ?>
    <div class="grv-login-background" aria-hidden="true">
        <svg viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" fill="none" focusable="false">
            <defs>
                <pattern id="grvLoginGrid" width="72" height="72" patternUnits="userSpaceOnUse"><path d="M72 0H0V72" stroke="#8bb5be" stroke-opacity=".055"/></pattern>
                <linearGradient id="grvLoginLines" x1="40" y1="840" x2="1380" y2="60" gradientUnits="userSpaceOnUse"><stop stop-color="#78b5bc" stop-opacity=".2"/><stop offset=".5" stop-color="#78b5bc" stop-opacity=".025"/><stop offset="1" stop-color="#78b5bc" stop-opacity=".22"/></linearGradient>
            </defs>
            <path fill="url(#grvLoginGrid)" d="M0 0h1440v900H0z"/>
            <g stroke="url(#grvLoginLines)" stroke-width="1.2">
                <path d="M-180 740C90 340 270 1040 690 650S1220 100 1600 280"/>
                <path d="M-180 780C90 380 270 1080 690 690S1220 140 1600 320"/>
                <path d="M-180 820C90 420 270 1120 690 730S1220 180 1600 360"/>
            </g>
            <g class="grv-login-orbit" stroke="#82b4bd" stroke-opacity=".12">
                <circle cx="1190" cy="180" r="155"/>
                <circle cx="1190" cy="180" r="235"/>
                <path d="M1190-135a315 315 0 1 1-315 315"/>
                <circle cx="1350" cy="8" r="5" fill="#82b4bd" fill-opacity=".65" stroke="none"/>
                <circle cx="1056" cy="258" r="4" fill="#f43b4a" fill-opacity=".7" stroke="none"/>
            </g>
            <g class="grv-login-orbit grv-login-orbit-secondary" stroke="#82b4bd" stroke-opacity=".1">
                <circle cx="130" cy="800" r="150"/>
                <circle cx="130" cy="800" r="250"/>
                <circle cx="276" cy="765" r="4" fill="#82b4bd" fill-opacity=".65" stroke="none"/>
            </g>
        </svg>
    </div>
    <?php
} );

function grv_login_logo_url() {
    return home_url( '/' );
}
add_filter( 'login_headerurl', 'grv_login_logo_url' );

function grv_login_logo_title() {
    return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'grv_login_logo_title' );
