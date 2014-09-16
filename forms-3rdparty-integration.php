<?php
/*

Plugin Name: Forms: 3rd-Party Integration
Plugin URI: https://github.com/zaus/forms-3rdparty-integration
Description: Send plugin Forms Submissions (Gravity, CF7, Ninja Forms, etc) to a 3rd-party URL
Author: zaus, atlanticbt, spkane
Version: 1.6.2
Author URI: http://drzaus.com
Changelog:
	1.4 - forked from cf7-3rdparty.  Removed 'hidden field plugin'.
	1.4.1 - minor cleanup, bugfixes; added 'label' and 'drag' columns to admin ui.
	1.4.2 - bugfixes (CF7, empty admin sections), admin JS cleanup, timeout
	1.4.3 - cleaning up admin JS, plugin header warning
	1.4.4 - protecting against non-attached forms; github issue link; extra hooks
	1.4.5 - fixing response failure message notification
	1.4.6 - post args hook + bypass, fix arg-by-reference
	1.4.7 - totally removing hidden field plugin; js fixes; stripslashes
	1.4.8 - fixes for github issue-6 (php5 strict constructor) and issue-8 (configurable multiple values for same key)
	1.4.9 - matching cf7 v3.9
	1.6.0 - better fplugin base, ninjaforms integration (1.5); refactored gf/cf7 to use fplugin base
	1.6.1 - upgrade path
*/

//declare to instantiate
Forms3rdPartyIntegration::$instance = new Forms3rdPartyIntegration;

// handle plugin upgrades
// http://codex.wordpress.org/Function_Reference/register_activation_hook#Examples
include_once dirname( __FILE__ ) . '/upgrade.php';
$ugrader = new Forms3rdPartyIntegrationUpgrade();
$ugrader->register(__FILE__);

class Forms3rdPartyIntegration { 

	#region =============== CONSTANTS AND VARIABLE NAMES ===============
	
	const pluginPageTitle = 'Forms: 3rd Party Integration';
	
	const pluginPageShortTitle = '3rdparty Services';
	
	/**
	 * Admin - role capability to view the options page
	 * @var string
	 */
	const adminOptionsCapability = 'manage_options';

	/**
	 * Version of current plugin -- match it to the comment
	 * @var string
	 */
	const pluginVersion = '1.6.2';

	
	/**
	 * Self-reference to plugin name
	 * @var string
	 */
	private $N;
	
	/**
	 * Namespace the given key
	 * @param string $key the key to namespace
	 * @return the namespaced key
	 */
	public function N($key = false) {
		// nothing provided, return namespace
		if( ! $key || empty($key) ) { return $this->N; }
		return sprintf('%s_%s', $this->N, $key);
	}

	/**
	 * Parameter index for mapping - administrative label (reminder)
	 */
	const PARAM_LBL = 'lbl';
	/**
	 * Parameter index for mapping - source plugin (i.e. GravityForms, CF7, etc)
	 */
	const PARAM_SRC = 'src';
	/**
	 * Parameter index for mapping - 3rdparty destination
	 */
	const PARAM_3RD = '3rd';

	/**
	 * How long (seconds) before considering timeout
	 */
	const DEFAULT_TIMEOUT = 10;

	/**
	 * Singleton
	 * @var object
	 */
	public static $instance = null;

	#endregion =============== CONSTANTS AND VARIABLE NAMES ===============
	
	
	#region =============== CONSTRUCTOR and INIT (admin, regular) ===============
	
	// php5 constructor must come first for 'strict standards' -- http://wordpress.org/support/topic/redefining-already-defined-constructor-for-class-wp_widget

	function __construct() {
		$this->N = __CLASS__;
		
		add_action( 'admin_menu', array( &$this, 'admin_init' ), 20 ); // late, so it'll attach menus farther down
		add_action( 'init', array( &$this, 'init' ) ); // want to run late, but can't because it misses CF7 onsend?
	} // function

	function Forms3rdPartyIntegration() {
		$this->__construct();
	} // function


	function admin_init() {
		# perform your code here
		//add_action('admin_menu', array(&$this, 'config_page'));
		
		//add plugin entry settings link
		add_filter( 'plugin_action_links', array(&$this, 'plugin_action_links'), 10, 2 );
		
		//needs a registered page in order for the above link to work?
		#$pageName = add_options_page("Custom Shortcodes - ABT Options", "Shortcodes -ABT", self::adminOptionsCapability, 'abt-shortcodes-config', array(&$this, 'submenu_config'));
		if ( function_exists('add_submenu_page') ){
			
			// autoattach to cf7, gravityforms
			$subpagesOf = apply_filters($this->N('declare_subpages'), array());
			foreach($subpagesOf as $plugin) {
				$page = add_submenu_page(/*'plugins.php'*/$plugin, __(self::pluginPageTitle), __(self::pluginPageShortTitle), self::adminOptionsCapability, basename(__FILE__,'.php').'-config', array(&$this, 'submenu_config'));
				//add admin stylesheet, etc
				add_action('admin_print_styles-' . $page, array(&$this, 'add_admin_headers'));
			}
			
			
			//register options
			$default_options = array(
				'debug' => array('email'=>get_bloginfo('admin_email'), 'separator'=>', ')
				, 0 => array(
					'name'=>'Service 1'
					, 'url'=>plugins_url('3rd-parties/service_test.php', __FILE__)
					, 'success'=>''
					, 'forms' => array()
					, 'hook' => false
					, 'timeout' => self::DEFAULT_TIMEOUT // timeout in seconds
					, 'mapping' => array(
						array(self::PARAM_LBL=>'The submitter name',self::PARAM_SRC=>'your-name', self::PARAM_3RD=>'name')
						, array(self::PARAM_LBL=>'The email address', self::PARAM_SRC=>'your-email', self::PARAM_3RD=>'email')
					)
				)
			);
			add_option( $this->N('settings'), $default_options );
		}
		
	} // function


	/**
	 * General init
	 * Add scripts and styles
	 * but save the enqueue for when the shortcode actually called?
	 */
	function init(){
		// needed here because both admin and before-send functions require v()
		/// TODO: more intelligently include...
		include_once('includes.php');

		#wp_register_script('jquery-flip', plugins_url('jquery.flip.min.js', __FILE__), array('jquery'), self::pluginVersion, true);
		#wp_register_style('sponsor-flip', plugins_url('styles.css', __FILE__), array(), self::pluginVersion, 'all');
		#
		#if( !is_admin() ){
		#	/*
		#	add_action('wp_print_header_scripts', array(&$this, 'add_headers'), 1);
		#	add_action('wp_print_footer_scripts', array(&$this, 'add_footers'), 1);
		#	*/
		#	wp_enqueue_script('jquery-flip');
		#	wp_enqueue_script('sponsor-flip-init');
		#	wp_enqueue_style('sponsor-flip');
		#}
		

		// allow extensions; remember to check !is_admin
		do_action($this->N('init'), false);

		if(!is_admin()){
			//add_action('wp_footer', array(&$this, 'shortcode_post_slider_add_script'));	//jedi way to add shortcode scripts
		}
	
	}
	
	#endregion =============== CONSTRUCTOR and INIT (admin, regular) ===============
	
	#region =============== HEADER/FOOTER -- scripts and styles ===============
	
	/**
	 * Add admin header stuff 
	 * @see http://codex.wordpress.org/Function_Reference/wp_enqueue_script#Load_scripts_only_on_plugin_pages
	 */
	function add_admin_headers(){
		
		wp_enqueue_script($this->N('admin'), plugins_url('plugin.admin.js', __FILE__), array('jquery', 'jquery-ui-sortable'), self::pluginVersion, true);
		wp_localize_script($this->N('admin'), $this->N('admin'), array(
			'N' => $this->N()
		));
		
		$stylesToAdd = array(
			basename(__FILE__,'.php') => 'plugin.admin.css'	//add a stylesheet with the key matching the filename
		);
		
		// Have to manually add to in_footer
		// Check if script is done, if not, then add to footer
		foreach($stylesToAdd as $handle => $stylesheet){
			wp_enqueue_style(
				$handle									//id
				, plugins_url($stylesheet, __FILE__)	//file
				, array()								//dependencies
				, self::pluginVersion					//version
				, 'all'									//media
			);
		}
	}//---	function add_admin_headers
	
	/**
	 * Only add scripts and stuff if shortcode found on page
	 * TODO: figure out how this works -- global $wpdb not correct
	 * @source http://shibashake.com/wordpress-theme/wp_enqueue_script-after-wp_head
	 * @source http://old.nabble.com/wp-_-enqueue-_-script%28%29-not-working-while-in-the-Loop-td26818198.html
	 */
	function add_headers() {
		//ignore the examples below
		return;
		
		if(is_admin()) return;
		
		$stylesToAdd = array();
		
		// Have to manually add to in_footer
		// Check if script is done, if not, then add to footer
		foreach($stylesToAdd as $style){
			if (!in_array($style, $wp_styles->done) && !in_array($style, $wp_styles->in_footer)) {
				$wp_styles->in_header[] = $style;
			}
		}
	}//--	function add_headers
	
	/**
	 * Only add scripts and stuff if shortcode found on page
	 * @see http://scribu.net/wordpress/optimal-script-loading.html
	 */
	function add_footers() {
		if(is_admin()){
			wp_enqueue_script($this->N('admin'));
			return;
		}
		
		$scriptsToAdd = array( );
		
		// Have to manually add to in_footer
		// Check if script is done, if not, then add to footer
		foreach($scriptsToAdd as $script){
			if (!in_array($script, $wp_scripts->done) && !in_array($script, $wp_scripts->in_footer)) {
				$wp_scripts->in_footer[] = $script;
			}
		}
	}
	
	#endregion =============== HEADER/FOOTER -- scripts and styles ===============
		
	#region =============== Administrative Settings ========
	
	private $_settings;
	private $_services;
	/**
	 * Return the plugin settings
	 */
	function get_settings($stashed = true){
		// TODO: if this ever changes, make sure to correspondingly fix 'upgrade.php'

		if( $stashed && isset($this->_settings) ) return $this->_settings;

		$this->_settings = get_option($this->N('settings'));
		// but we only want the actual settings, not the services
		$this->_settings = $this->_settings['debug'];

		return $this->_settings;
	}//---	get_settings
	/**
	 * Return the service configurations
	 */
	function get_services($stashed = true) {
		if( $stashed && isset($this->_services) ) return $this->_services;

		$this->_services = get_option($this->N('settings'));
		// but we only want service listing, not the settings
		// TODO: this will go away once we move to custom post type like CF7
		unset($this->_services['debug']);

		return $this->_services;
	}
	function save_services($services) {
		$settings = $this->get_settings(false);
		$merged = array('debug' => $settings) + (array)$services;
		update_option($this->N('settings'), $merged);
		$this->_services = $services; // replace stash
	}
	function save_settings($settings) {
		$services = $this->get_services(false);
		$merged = array('debug' => $settings) + (array)$services;
		update_option($this->N('settings'), $merged);
		$this->_settings = $settings; // replace stash
	}
	
	/**
	 * The submenu page
	 */
	function submenu_config(){
		wp_enqueue_script($this->N('admin'));
		include_once('plugin-ui.php');
	}
	
	/**
	 * HOOK - Add the "Settings" link to the plugin list entry
	 * @param $links
	 * @param $file
	 */
	function plugin_action_links( $links, $file ) {
		if ( $file != plugin_basename( __FILE__ ) )
			return $links;
	
		$url = $this->plugin_admin_url( array( 'page' => basename(__FILE__, '.php').'-config' ) );
	
		$settings_link = '<a title="Capability ' . self::adminOptionsCapability . ' required" href="' . esc_attr( $url ) . '">'
			. esc_html( __( 'Settings', $this->N ) ) . '</a>';
	
		array_unshift( $links, $settings_link );
	
		return $links;
	}
	
	/**
	 * Copied from Contact Form 7, for adding the plugin link
	 * @param unknown_type $query
	 */
	function plugin_admin_url( $query = array() ) {
		global $plugin_page;
	
		if ( ! isset( $query['page'] ) )
			$query['page'] = $plugin_page;
	
		$path = 'admin.php';
	
		if ( $query = build_query( $query ) )
			$path .= '?' . $query;
	
		$url = admin_url( $path );
	
		return esc_url_raw( $url );
	}
	
	/**
	 * Helper to render a select list of available forms
	 * @param array $forms list of  forms from functions like wpcf7_contact_forms()
	 * @param array $eid entry id - for multiple lists on page
	 * @param array $selected ids of selected fields
	 */
	public function form_select_input($forms, $eid, $selected){
		?>
		<select class="multiple" multiple="multiple" id="forms-<?php echo $eid?>" name="<?php echo $this->N?>[<?php echo $eid?>][forms][]">
			<?php
			foreach($forms as $f){
				$form_id = $f['id'];
				
				$form_title = $f['title'];	// as serialized option data
				
				?>
				<option <?php if($selected && in_array($form_id, $selected)): ?>selected="selected" <?php endif; ?>value="<?php echo esc_attr( $form_id );?>"><?php echo esc_html( $form_title ); ?></option>
				<?php
			}//	foreach
			?>
		</select>
		<?php
	}//--	end function form_select_input


	#endregion =============== Administrative Settings ========
	
	/**
	 * Callback to perform before Form (i.e. Contact-Form-7, Gravity Forms) fires
	 * @param $form
	 * 
	 * @see http://www.alexhager.at/how-to-integrate-salesforce-in-contact-form-7/
	 */
	function before_send($form){
		###_log(__LINE__.':'.__FILE__, '	begin before_send', $form);

		//get field mappings and settings
		$debug = $this->get_settings();
		$services = $this->get_services();
		
		$submission = false;

		//loop services
		foreach($services as $sid => $service):
			//check if we're supposed to use this service
			if( !isset($service['forms']) || empty($service['forms']) ) continue; // nothing provided

			$use_this_form = apply_filters($this->N('use_form'), false, $form, $sid, $service['forms']);

			###_log('are we using this form?', $use_this_form ? "YES" : "NO", $sid, $service);
			if( !$use_this_form ) continue;
			
			// only build the submission once; we've moved the call here so it respects use_form
			if(false === $submission) {
				// alias to submission data - in GF it's $_POST, in CF7 it's $cf7->posted_data
				$submission = apply_filters($this->N('get_submission'), array(), $form);
			}

			$post = array();
			
			$service['separator'] = $debug['separator']; // alias here for reporting
			
			//find mapping
			foreach($service['mapping'] as $mid => $mapping){
				$third = $mapping[self::PARAM_3RD];
				
				//is this static or dynamic (userinput)?
				if(v($mapping['val'])){
					$input = $mapping[self::PARAM_SRC];
				}
				else {
					//check if we have that field in post data
					if( !isset($submission[ $mapping[self::PARAM_SRC] ]) ) continue;

					$input = $submission[ $mapping[self::PARAM_SRC] ];
				}

				//allow multiple values to attach to same entry
				if( isset( $post[ $third ] ) ){
					###echo "multiple @$mid - $fsrc, $third :=\n";

					if(!is_array($post[$third])) {
						$post[$third] = array($post[$third]);
					}
					$post[$third] []= $input;
				}
				else {
					$post[$third] = $input;
				}
			}// foreach mapping
			
			//extract special tags;
			$post = apply_filters($this->N('service_filter_post_'.$sid), $post, $service, $form);
			$post = apply_filters($this->N('service_filter_post'), $post, $service, $form, $sid);

			// fix for multiple values
			switch($service['separator']) {
				case '[#]':
					// don't do anything to include numerical index (default behavior of `http_build_query`)
					break;
				case '[]':
					// must build as querystring then strip `#` out of `[#]=`
					$post = http_build_query($post);
					$post = preg_replace('/%5B[0-9]+%5D=/', '%5B%5D=', $post);
					break;
				default:
					// otherwise, find the arrays and implode
					foreach($post as $f => &$v) {
						###_log('checking array', $f, $v, is_array($v) ? 'array' : 'notarray');
						
						if(is_array($v)) $v = implode($service['separator'], $v);
					}
					break;
			}
			
			###_log(__LINE__.':'.__FILE__, '	sending post to '.$service['url'], $post);

			// change args sent to remote post -- add headers, etc: http://codex.wordpress.org/Function_Reference/wp_remote_post
			// optionally, return an array with 'response_bypass' set to skip the wp_remote_post in favor of whatever you did in the hook
			$post_args = apply_filters($this->N('service_filter_args')
				, array(
					'timeout' => empty($service['timeout']) ? self::DEFAULT_TIMEOUT : $service['timeout']
					,'body'=>$post
					)
				, $service
				, $form
			);

			//remote call
			// optional bypass -- replace with a SOAP call, etc
			if(isset($post_args['response_bypass'])) {
				$response = $post_args['response_bypass'];
			}
			else {
				//@see http://planetozh.com/blog/2009/08/how-to-make-http-requests-with-wordpress/
				$response = wp_remote_post( $service['url'], $post_args );
			}

			###pbug(__LINE__.':'.__FILE__, '	response from '.$service['url'], $response);
			
			$can_hook = true;
			//if something went wrong with the remote-request "physically", warn
			if (!is_array($response)) {	//new occurrence of WP_Error?????
				$response_array = array('safe_message'=>'error object', 'object'=>$response);
				$form = $this->on_response_failure($form, $debug, $service, $post_args, $response_array);
				$can_hook = false;
			}
			elseif(!$response || !isset($response['response']) || !isset($response['response']['code']) || 200 != $response['response']['code']) {
				$response['safe_message'] = 'physical request failure';
				$form = $this->on_response_failure($form, $debug, $service, $post_args, $response);
				$can_hook = false;
			}
			//otherwise, check for a success "condition" if given
			elseif(!empty($service['success'])) {
				if(strpos($response['body'], $service['success']) === false){
					$failMessage = array(
						'reason'=>'Could not locate success clause within response'
						, 'safe_message' => 'Success Clause not found'
						, 'clause'=>$service['success']
						, 'response'=>$response['body']
					);
					$form = $this->on_response_failure($form, $debug, $service, $post_args, $failMessage);
					$can_hook = false;
				}
			}
			
			if($can_hook && isset($service['hook']) && $service['hook']){
				###_log('performing hooks for:', $this->N.'_service_'.$sid);
				
				//hack for pass-by-reference
				//holder for callback return results
				$callback_results = array('success'=>false, 'errors'=>false, 'attach'=>'', 'message' => '');
				// TODO: use object?
				$param_ref = array();	foreach($callback_results as $k => &$v){ $param_ref[$k] = &$v; }
				
				//allow hooks
				do_action($this->N('service_a'.$sid), $response['body'], $param_ref);
				do_action($this->N('service'), $response['body'], $param_ref, $sid);
				
				###_log('after success', $form);

				//check for callback errors; if none, then attach stuff to message if requested
				if(!empty($callback_results['errors'])){
					$failMessage = array(
						'reason'=>'Service Callback Failure'
						, 'safe_message' => 'Service Callback Failure'
						, 'errors'=>$callback_results['errors']);
					$form = $this->on_response_failure($form, $debug, $service, $post_args, $failMessage);
				}
				else {
					###_log('checking for attachments', print_r($callback_results, true));
					$form = apply_filters($this->N('remote_success'), $form, $callback_results, $service);
				}
			}// can hook
			
			//forced debug contact
			if($debug['mode'] == 'debug'){
				$this->send_debug_message($debug['email'], $service, $post_args, $response, $submission);
			}
			
		endforeach;	//-- loop services
		
		###_log(__LINE__.':'.__FILE__, '	finished before_send', $form);
		
		// some plugins expected usage is as filter, so return (modified?) form
		return $form;
	}//---	end function before_send
	
	/**
	 * How to send the debug message
	 * @param  string $email      recipient
	 * @param  array $service    service options
	 * @param  array $post       details sent to 3rdparty
	 * @param  object $response   the response object
	 * @param  object $submission the form submission
	 * @return void             n/a
	 */
	private function send_debug_message($email, $service, $post, $response, $submission){
		// did the debug message send?
		if( !wp_mail( $email
			, self::pluginPageTitle . " Debug: {$service['name']}"
			, "*** Service ***\n".print_r($service, true)."\n*** Post (Form) ***\n" . get_bloginfo('url') . $_SERVER['REQUEST_URI'] . "\n".print_r($submission, true)."\n*** Post (to Service) ***\n".print_r($post, true)."\n*** Response ***\n".print_r($response, true)
			, array('From: "'.self::pluginPageTitle.' Debug" <'.$this->N.'-debug@' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . '>')
		) ) {
			///TODO: log? another email? what?
		}

	}
	
	/**
	 * Add a javascript warning for failures; also send an email to debugging recipient with details
	 * parameters passed by reference mostly for efficiency, not actually changed (with the exception of $form)
	 * 
	 * @param $form reference to CF7 plugin object - contains mail details etc
	 * @param $debug reference to this plugin "debug" option array
	 * @param $service reference to service settings
	 * @param $post_args reference to service post data
	 * @param $response reference to remote-request response
	 */
	private function on_response_failure($form, $debug, $service, $post_args, $response){
		// failure hooks; pass-by-value
		
		$form = apply_filters($this->N('remote_failure'), $form, $debug, $service, $post_args, $response);

		return $form;
	}//---	end function on_response_failure


	/**
	 * Email helper for `remote_failure` hooks
	 * @param array $service			service configuration
	 * @param array $debug				the debug settings (to get email)
	 * @param array $post				details sent to 3rdparty
	 * @param array $response 			remote-request response
	 * @param string $form_title		name of the form used
	 * @param string $form_recipient	email of the original form recipient, so we know who to follow up with
	 * @param debug_from_id				short identifier of the Form plugin which failed (like 'CF7' or 'GF', etc)
	 *
	 * @return true if the warning email sent, false otherwise
	 */
	public function send_service_error(&$service, &$debug, &$post, &$response, $form_title, $form_recipient, $debug_from_id) {
		$body = sprintf('There was an error when trying to integrate with the 3rd party service {%2$s} (%3$s).%1$s%1$s**FORM**%1$sTitle: %6$s%1$sIntended Recipient: %7$s%1$sSource: %8$s%1$s%1$s**SUBMISSION**%1$s%4$s%1$s%1$s**RAW RESPONSE**%1$s%5$s'
			, "\n"
			, $service['name']
			, $service['url']
			, print_r($post, true)
			, print_r($response, true)
			, $form_title
			, $form_recipient
			, get_bloginfo('url') . $_SERVER['REQUEST_URI']
			);
		$subject = sprintf('%s-3rdParty Integration Failure: %s'
			, $debug_from_id
			, $service['name']
			);
		$headers = array(
			sprintf('From: "%1$s-3rdparty Debug" <%1$s-3rdparty-debug@%2$s>'
				, $debug_from_id
				, str_replace('www.', '', $_SERVER['HTTP_HOST'])
				)
			);

		//log if couldn't send debug email
		if(wp_mail( $debug['email'], $subject, $body, $headers )) return true;

		###$form->additional_settings .= "\n".'on_sent_ok: \'alert("Could not send debug warning '.$service['name'].'");\'';
		error_log(__LINE__.':'.__FILE__ .'	response failed from '.$service['url'].', could not send warning email: ' . print_r($response, true));
		return false;
	}//--	fn	send_service_error


	/**
	 * Format the configured service failure message using the $response "safe message" and the plugin's original error message
	 * @param array $service			service configuration
	 * @param array $response			remote-request response
	 * @param string $original_message	the plugin's original failure message
	 * @return the newly formatted failure message
	 */
	public function format_failure_message(&$service, &$response, $original_message) {
		return sprintf(
				__($service['failure'], $this->N())
				, $original_message
				, __($response['safe_message'], $this->N())
				);
	}
}//end class
