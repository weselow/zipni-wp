<?php
/**
 * Plugin Name: Zipni — Force Complianz Banner
 * Description: Always show cookie banner (informational, 152-FZ). Must be mu-plugin to load before Complianz constructor.
 */
add_filter( 'cmplz_site_needs_cookiewarning', '__return_true' );
