<?php
/**
 * Zipni theme functions.
 *
 * @package zipni
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue custom styles for blocks not covered by theme.json.
 */
function zipni_enqueue_styles() {
	wp_enqueue_style(
		'zipni-custom',
		get_theme_file_uri( 'assets/custom.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'zipni_enqueue_styles' );

/**
 * SVG favicon (red square + white Z) from old landing.
 * Overrides WP site icon with a sharper SVG version.
 */
function zipni_svg_favicon() {
	echo '<link rel="icon" type="image/svg+xml" href="' . esc_url( get_theme_file_uri( 'assets/favicon.svg' ) ) . '">' . "\n";
}
add_action( 'wp_head', 'zipni_svg_favicon', 1 );

/**
 * Hide admin bar on mobile for CTA button visibility.
 */
function zipni_hide_on_mobile_css() {
	echo '<style>@media(max-width:767px){.hide-on-mobile{display:none!important}}</style>';
}
add_action( 'wp_head', 'zipni_hide_on_mobile_css' );

/**
 * Register block patterns category.
 */
function zipni_register_pattern_categories() {
	register_block_pattern_category( 'zipni', array(
		'label' => __( 'Zipni', 'zipni' ),
	) );
}
add_action( 'init', 'zipni_register_pattern_categories' );

/* Carousel shortcode moved to zipni-portfolio plugin (v1.1.0).
 * Now dynamic: queries portfolio CPT, falls back to hardcoded images. */

/**
 * Translate Complianz GDPR strings to Russian.
 *
 * Plugin ships without ru_RU .mo file; override key UI strings via gettext.
 */
function zipni_complianz_ru( $translation, $text, $domain ) {
	if ( 'complianz-gdpr' !== $domain ) {
		return $translation;
	}
	static $map = array(
		'Always active'    => 'Всегда активны',
		'Functional'       => 'Необходимые',
		'Statistics'       => 'Статистика',
		'Preferences'      => 'Предпочтения',
		'Marketing'        => 'Маркетинг',
		'Accept'           => 'Принять',
		'Deny'             => 'Отклонить',
		'Save preferences' => 'Сохранить настройки',
		'Close'            => 'Закрыть',
	);
	return $map[ $text ] ?? $translation;
}
add_filter( 'gettext', 'zipni_complianz_ru', 10, 3 );

/* Cookie banner force-show filter lives in mu-plugins/zipni-complianz-banner.php
 * (must load before Complianz constructor). */

/*--------------------------------------------------------------
 * Security hardening
 *--------------------------------------------------------------*/

/** Disable XML-RPC (not used; autoposting uses REST API). */
add_filter( 'xmlrpc_enabled', '__return_false' );

/** Remove X-Pingback header. */
function zipni_remove_x_pingback( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
}
add_filter( 'wp_headers', 'zipni_remove_x_pingback' );

/** Hide WordPress version from HTML and feeds. */
remove_action( 'wp_head', 'wp_generator' );

/** Send security headers. */
function zipni_security_headers() {
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
}
add_action( 'send_headers', 'zipni_security_headers' );
