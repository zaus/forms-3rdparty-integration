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

// the core form plugin wrapper
include('plugins/fplugin_base.php');


// where to put these? - otherwise hook to $this->N('init') i.e. Forms3rdPartyIntegration::$instance->N('init')
// IMPORTANT:  protective checking - do the related modules exist? - http://codex.wordpress.org/Function_Reference/is_plugin_active
if( ! function_exists('is_plugin_active') ) { include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); }

// specific forms plugins

// TODO: maybe make this optional via filter?
// I'm assuming the overhead of checking if plugins exist is low,
// but this could cause headaches if you have multiple form
// plugins active, since 'use-form' checking isn't the best for differentiating

if(is_plugin_active('contact-form-7/wp-contact-form-7.php') || class_exists('WPCF7_ContactForm') )
	include('plugins/contactform7.php');

if(is_plugin_active('gravityforms/gravityforms.php') || class_exists('RGFormsModel') )
	include('plugins/gravityforms.php');

if(is_plugin_active('ninja-forms/ninja-forms.php') || class_exists('Ninja_Forms') )
	include('plugins/ninjaforms.php');

/* to add others, use something like:

add_action( 'plugins_loaded', array('Forms3rdpartyIntegration_YOUR_FORM_PLUGIN', 'init') );

class Forms3rdpartyIntegration_YOUR_FORM_PLUGIN {
	// after plugins ready
	public static function init() {
		// hook way early
		add_action(Forms3rdPartyIntegration::$instance->N('init'), array(__CLASS__, '_include'), 1);
	}


	// actually start the class once Forms3rdparty is ready to include it
	public static function _include() {
		include('DERIVED_INSTANCE_OF_FPLUGIN_BASE.php');
	}
}

*/