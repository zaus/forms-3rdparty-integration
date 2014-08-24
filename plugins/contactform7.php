<?php

// run it
new Forms3rdpartyIntegration_Cf;

/**
 * Does the work of integrating cf7 with 3rdparty
 */
class Forms3rdpartyIntegration_Cf {

	function __construct() {
		add_action(Forms3rdPartyIntegration::$instance->N('init'), array(&$this, 'init'));
		add_filter(Forms3rdPartyIntegration::$instance->N('declare_subpages'), array(&$this, 'add_subpage'));
		add_filter(Forms3rdPartyIntegration::$instance->N('use_form'), array(&$this, 'use_form'), 10, 4);
		add_filter(Forms3rdPartyIntegration::$instance->N('select_forms'), array(&$this, 'select_forms'), 10, 1);
		add_filter(Forms3rdPartyIntegration::$instance->N('get_submission'), array(&$this, 'get_submission'), 10, 2);

	}

	public function init(){
		##pbug(__CLASS__, __FUNCTION__);

		if( !is_admin() )
			add_action( 'wpcf7_before_send_mail', array(&Forms3rdPartyIntegration::$instance, 'before_send') );

		// no longer includes other plugins (i.e. hidden.php)
		// add_action( 'init', array( &$this, 'other_includes' ), 20 );
	}

	/**
	 * Register plugin as a subpage of the given pages
	 * @param array $subpagesOf list of pages to be a subpage of -- add your target here
	 * @return the modified list of subpages
	 */
	public function add_subpage($subpagesOf) {
		$subpagesOf []= 'wpcf7';
		return $subpagesOf;
	}

	/**
	 * Used to identify form in select box
	 */
	const FORM_ID_PREFIX = 'cf7_';

	/**
	 * Gets list of plugin forms, remaps to expected format if needed
	 * @param  array $forms current running list of forms as id, title
	 * @return array        updated list of forms
	 */
	public function select_forms($forms){
		$cf_forms = array();
		$cf_forms = get_posts( array(
			'numberposts' => -1,
			'orderby' => 'ID',
			'order' => 'ASC',
			'post_type' => 'wpcf7_contact_form' ) );
		
		// now remap $cf to generic format: id, title
		foreach($cf_forms as $f) {
			$form = array();
			$form['id'] = $f->ID;	// as WP posttype
			
			$form['title'] = $f->post_title;	// as WP posttype
		
			// prefix
			$form['id'] = self::FORM_ID_PREFIX . $form['id'];
			
			// add to list
			$forms []= $form;
		}

		return $forms;
	}//--	function	select_forms


	private $_use_form;

	/**
	 * which version of the plugin are we supporting?
	 */
	private $_plugin_style;

	/**
	 * How do decide whether the form is being used
	 * @param bool $result           the cascading result: true to use this form
	 * @param  object $form          the CF7 form object
	 * @param  int $service_id    service identifier (from hook, option setting)
	 * @param  array $service_forms list of forms attached to this service
	 * @return bool                whether or not to use this form with this service
	 */
	public function use_form($result, $form, $service_id, $service_forms) {
		// protect against accidental binding between multiple plugins
		$this->_use_form = $result;

		// nothing to check against if nothing selected
		if( empty($service_forms) ) return $this->_use_form;

		if( 'WPCF7_ContactForm' != get_class($form) ) return $this->_use_form;
		
		$form_id = $form->id();
		
		$this->_use_form = in_array(self::FORM_ID_PREFIX . $form_id, $service_forms);
		### _log(__CLASS__ . '::' . __FUNCTION__ . ' using form?', $result ? 'Y':'N', $form_id, $service_forms);

		// also add subsequent hooks
		if($this->_use_form) {
			add_filter(Forms3rdPartyIntegration::$instance->N('remote_success'), array(&$this, 'remote_success'), 10, 3);
			add_filter(Forms3rdPartyIntegration::$instance->N('remote_failure'), array(&$this, 'remote_failure'), 10, 5);
		}

		return $this->_use_form;
	}

	/**
	 * Get the posted submission for the form
	 * @param  array $submission initial values for submission; may have been provided by hooks
	 * @param  object $cf7       the CF7 form object
	 * @return array             list of posted submission values to manipulate and map
	 */
	public function get_submission($submission, $cf7){
		if(!$this->_use_form) return;

		// could check `$this->_plugin_style`, but safer to just check the data instead
		
		return array_merge((array)$submission, WPCF7_Submission::get_instance()->get_posted_data());

		throw new Exception('Unable to figure out which style of Contact Form 7 from which to retrieve post data');
	}


	/**
	 * What to do when the remote request succeeds
	 * @param  array $callback_results list of 'success' (did it work), 'errors' (list of validation errors), 'attach' (email body attachment), 'message' (when failed)
	 * @param  object $form             the form/plugin object
	 * @param  array $service          associative array of the service options
	 * @return void                   n/a
	 */
	public function remote_success($form, $callback_results, $service) {
		//if requested, attach results to message
		if(!empty($callback_results['attach'])) {

			$mail = $form->prop('mail'); // previous style: $form->mail
			$mail['body'] .= "\n\n" . ($mail['use_html']
										? "<br /><b>Service &quot;{$service['name']}&quot; Results:</b><br />\n"
										: "Service \"{$service['name']}\" Results:\n")
									. $callback_results['attach'];
			$form->set_properties(array('mail'=>$mail));
		}
		
		//if requested, attach message to success notification
		if( !empty($callback_results['message']) ) {
			$messages = $form->prop('messages');
			$messages['mail_sent_ok'] = $callback_results['message'];
			$form->set_properties(array('messages'=>$messages));
		}// has callback message

		return $form; // yes this is redundant when it's an object, but need it for compatibility with GF
	}

	/**
	 * Add a javascript warning for failures; also send an email to debugging recipient with details
	 * parameters passed by reference mostly for efficiency, not actually changed (with the exception of $form)
	 * 
	 * @param $form reference to CF7 plugin object - contains mail details etc
	 * @param $debug reference to this plugin "debug" option array
	 * @param $service reference to service settings
	 * @param $post reference to service post data
	 * @param $response reference to remote-request response
	 * @return the updated form reference
	 */
	public function remote_failure($form, $debug, $service, $post, $response){
		//notify frontend
		$additional = $form->prop('additional_settings');
		$messages = $form->prop('messages');
		$mail = $form->prop('mail');

		$additional .= "\n".'on_sent_ok: \'if(window.console && console.warn){ console.warn("Failed submitting to '.$service['name'].': '.$response['safe_message'].'"); }\'';
		// do we always report, or just pretend it worked, because the original contact plugin may be fine...
		if(!empty($service['failure'])) {
			// kind of a hack -- override the success and fail messages, just in case one or other is displayed
			
			$messages['mail_sent_ok'] =
			$messages['mail_sent_ng'] = 
			sprintf(
				__($service['failure'], Forms3rdPartyIntegration::$instance->N())
				, $messages['mail_sent_ng']
				, __($response['safe_message'], Forms3rdPartyIntegration::$instance->N())
				);
			// $messages['mail_sent_ok'] = isset($service['failure']) ? $service['failure'] : $messages['mail_sent_ng'];
			$form->set_properties(array('messages'=>$messages, 'additional_settings'=>$additional));
		}
		else $form->set_properties(array('additional_settings'=>$additional));

		//notify admin
		$body = sprintf('There was an error when trying to integrate with the 3rd party service {%2$s} (%3$s).%1$s%1$s**FORM**%1$sTitle: %6$s%1$sIntended Recipient: %7$s%1$sSource: %8$s%1$s%1$s**SUBMISSION**%1$s%4$s%1$s%1$s**RAW RESPONSE**%1$s%5$s'
			, "\n"
			, $service['name']
			, $service['url']
			, print_r($post, true)
			, print_r($response, true)
			, $form->title()
			, $mail['recipient']
			, get_bloginfo('url') . $_SERVER['REQUEST_URI']
			);
		$subject = sprintf('CF7-3rdParty Integration Failure: %s'
			, $service['name']
			);
		$headers = array('From: "CF7-3rdparty Debug" <cf7-3rdparty-debug@' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . '>');

		//log if couldn't send debug email
		if(!wp_mail( $debug['email'], $subject, $body, $headers )){
			### $form->additional_settings .= "\n".'on_sent_ok: \'alert("Could not send debug warning '.$service['name'].'");\'';
			error_log(__LINE__.':'.__FILE__ .'	response failed from '.$service['url'].', could not send warning email: ' . print_r($response, true));
		}

		return $form;
	}//---	end function on_response_failure


}///----	class	Forms3rdpartyIntegration_Cf