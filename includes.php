<?php

#if( !file_exists( WP_CONTENT_DIR . '/library/common-functions.php')):

if( !function_exists('v')):
/**
 * Safely get a value or return default if not set
 *
 * @param mixed $v value to get
 * @param mixed $d default if value is not set
 * @return mixed
 */
function v(&$v, $d = NULL){ return isset($v)?$v:$d; }
endif;	//check if common-functions exists

#endif;	//check if common-functions exists in /library


// where to put these? - otherwise hook to $this->N('init') i.e. Forms3rdPartyIntegration::$instance->N('init')
// IMPORTANT:  protective checking - do the related modules exist? - http://codex.wordpress.org/Function_Reference/is_plugin_active
if( ! function_exists('is_plugin_active') ) { include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); }

if(is_plugin_active('contact-form-7/wp-contact-form-7.php') || class_exists('WPCF7_ContactForm') )
	include('plugins/contactform7.php');

if(is_plugin_active('gravityforms/gravityforms.php') || class_exists('RGFormsModel') )
	include('plugins/gravityforms.php');
