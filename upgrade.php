<?php

if(!class_exists('WpPluginUpgradeBase')) :

abstract class WpPluginUpgradeBase {

	function __construct() {
		add_action( 'admin_init', array(&$this, 'load_plugin') );
	}

	/**
	 * Namespace the given key
	 * @param string $key the key to namespace
	 * @return the namespaced key
	 */
	public function N($key = false) {
		// nothing provided, return namespace
		if( ! $key || empty($key) ) { return get_class($this); }
		return sprintf('%s_%s', get_class($this), $key);
	}
	
	const HOOK_ACTIVATED = 'activated';
	const HOOK_LOADED = 'loaded';
	
	public function loaded_hook_name() { return $this->N(self::HOOK_LOADED); }
	public function activated_hook_name() { return $this->N(self::HOOK_ACTIVATED); }

	public function register($original_file) {
		register_activation_hook( $original_file, array( &$this, 'activate' ) );
	}

	public function activate() {
		// delay doing anything until plugins are actually ready
		// see http://codex.wordpress.org/Function_Reference/register_activation_hook#Process_Flow
		add_option( $this->N(), $this->N() );

		/* other non-plugin-dependent activation code here */
		do_action( $this->activated_hook_name(), 'activated' );
	}

	public function load_plugin() {

		if ( is_admin() && get_option( $this->N() ) == $this->N() ) {

			// clear the 'run once' flag
			delete_option( $this->N() );

			/* do stuff once right after activation */
			do_action( $this->loaded_hook_name(), 'loaded' );
		}
	}
}//---	class	WpPluginUpgradeBase


endif; // class_exists

class Forms3rdPartyIntegrationUpgrade extends WpPluginUpgradeBase {

	function __construct() {
		parent::__construct();
		
		add_action($this->loaded_hook_name(), array(&$this, 'loaded'));

		## test
		### add_action($this->loaded_hook_name(), array(&$this, 'test'));
		### add_action($this->activated_hook_name(), array(&$this, 'test'));
	}
	
	public function test($action) {
		// just prove it was called
		error_log(print_r(array(__FILE__, __CLASS__, __FUNCTION__, $action), true));
	}

	/**
	 * List of important upgrade steps
	 */
	private $upgrades = array(self::VERSION_FPLUGINBASE);
	const VERSION_FPLUGINBASE = '1.6.0';
	
	public function loaded($action) {
		// check current plugin version
		$current = Forms3rdPartyIntegration::pluginVersion;
		
		// compare against prev version and do stuff
		$prev = get_option( Forms3rdPartyIntegration::$instance->N('version') );

		### error_log('prev version ' . $prev . ', current ' . $current);

		// special case: we've never set the version before; not all plugins will need to upgrade in that case
		if(empty($prev) || version_compare($prev, $current) < 0) {
			// are there upgrade steps depending on how out-of-date?
			foreach($this->upgrades as $next_version) {
				if(version_compare($prev, $next_version) < 0) $this->do_upgrade($prev, $next_version);

				$prev = $next_version;
			}
		}

		// update stored plugin version for next time
		update_option(Forms3rdPartyIntegration::$instance->N('version'), $current);
	}

	function do_upgrade($prev, $next) {
		## error_log('upgrade from ' . $prev . ' to ' . $next);
		switch($next) {
			case self::VERSION_FPLUGINBASE:
				## error_log('doing fpluginbase upgrade...');

				// check the attached forms, and guess what prefixes should be added based on the
				// currently activated form plugins.
				// should be okay to "overdo it" and add multiple prefixes,
				// because they'll get selected and then corrected on next admin save
				
				$services = Forms3rdPartyIntegration::$instance->get_services();

				// only add prefix if corresponding plugin is active
				$has_cf7 = is_plugin_active('contact-form-7/wp-contact-form-7.php');
				$has_gf = is_plugin_active('gravityforms/gravityforms.php');
				// $has_ninja = is_plugin_active('ninja-forms/ninja-forms.php'); // don't need this for < 1.6.0

				$prefixes = array();
				if($has_cf7) $prefixes []= Forms3rdpartyIntegration_CF7::FORM_ID_PREFIX;
				if($has_gf) $prefixes []= Forms3rdpartyIntegration_Gf::FORM_ID_PREFIX;

				// nothing to do? quit
				if(empty($prefixes)) break; // return?

				foreach($services as &$service) {
					if( !isset($service['forms']) || empty($service['forms']) ) continue; // nothing attached

					$new_forms_list = array();
					foreach($service['forms'] as &$form_id) {
						// old style, no prefix?
						if( ! is_numeric($form_id) ) {
							// don't really need to preserve this, since this shouldn't ever happen
							// unless someone migrated and messed with settings on purpose
							// any any invalid values will disappear the next time the plugin settings are saved
							$new_forms_list []= $form_id;
							continue;
						}

						foreach($prefixes as $prefix) {
							$new_forms_list []= $prefix . $form_id;
						}
					}
					$service['forms'] = $new_forms_list;
				} // foreach service

				// now save the service changes
				Forms3rdPartyIntegration::$instance->save_services($services);

				break;
		}
	}
}
