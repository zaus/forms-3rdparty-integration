<?php

// run it
new Forms3rdpartyIntegration_Gf;

/**
 * Does the work of integrating cf7 with 3rdparty
 */
class Forms3rdpartyIntegration_Gf {

	function __construct() {
		add_action(Forms3rdPartyIntegration::$instance->N('init'), array(&$this, 'init'));
		add_filter(Forms3rdPartyIntegration::$instance->N('declare_subpages'), array(&$this, 'add_subpage'));
		add_filter(Forms3rdPartyIntegration::$instance->N('use_form'), array(&$this, 'use_form'), 10, 4);
		add_filter(Forms3rdPartyIntegration::$instance->N('select_forms'), array(&$this, 'select_forms'), 10, 1);
		add_filter(Forms3rdPartyIntegration::$instance->N('get_submission'), array(&$this, 'get_submission'), 10, 2);
	}

	public function init(){
		if( !is_admin() )
			// use "..._filter" to actually update the form
			add_filter( 'gform_pre_submission_filter', array(&Forms3rdPartyIntegration::$instance, 'before_send') );

		add_action( 'init', array( &$this, 'other_includes' ), 20 );
	}

	/**
	 * Register plugin as a subpage of the given pages
	 * @param array $subpagesOf list of pages to be a subpage of -- add your target here
	 * @return the modified list of subpages
	 */
	public function add_subpage($subpagesOf) {
		$subpagesOf []= 'gf_edit_forms';
		return $subpagesOf;
	}

	/**
	 * Used to identify form in select box
	 */
	const FORM_ID_PREFIX = 'gf_';

	/**
	 * Helper to render a select list of available cf7 forms
	 * @param array $forms list of CF7 forms from function wpcf7_contact_forms()
	 * @param array $eid entry id - for multiple lists on page
	 * @param array $selected ids of selected fields
	 */
	public function select_forms($forms){
		// from /wp-content/plugins/gravityforms/form_list.php, ~line 51
		$gf_forms = RGFormsModel::get_forms(true, "title");
		foreach($gf_forms as $f) {
			$form = array(
				'id' => self::FORM_ID_PREFIX . $f->id
				, 'title' => $f->title
			);
			// add to list
			$forms []= $form;
		}

		return $forms;
	}//--	end function select_forms


	private $_use_form;

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

		// TODO: figure out a more bulletproof way to confirm it's a GF form
		if( !is_array($form) || !isset($form['id']) || empty($form['id']) ) return $this->_use_form;

		// nothing to check against if nothing selected
		if( empty($service_forms) ) return $this->_use_form;


		$this->_use_form = in_array(self::FORM_ID_PREFIX . $form['id'], $service_forms);

		### _log('gf-int using form? ' . ($result ? 'Yes' : 'No'), $service_id, $form['id']);

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
	 * @param  object $form       the form object
	 * @return array             list of posted submission values to manipulate and map
	 */
	public function get_submission($submission, $form){
		if(!$this->_use_form) return;

		// merge with $submission?
		$result = array_merge((array)$submission, $_POST);
		return $result;
	}

	/**
	 * Late-loading - include hidden plugin really late, so the actual plugin has a chance to work first
	 * @return void n/a
	 */
	public function other_includes() {
		//only run if we haven't before
	}

	/**
	 * What to do when the remote request succeeds
	 * @param  array $callback_results list of 'success' (did it work), 'errors' (list of validation errors), 'attach' (email body attachment), 'message' (when failed)
	 * @param  object $form             the form object
	 * @param  array $service          associative array of the service options
	 * @return void                   n/a
	 */
	public function remote_success($form, $callback_results, $service) {
		### _log(__FUNCTION__, __CLASS__, $form, $callback_results['form']);

		//if requested, attach results to message
		if(!empty($callback_results['attach'])){
			// http://www.gravityhelp.com/documentation/page/Notification
			### _log('attaching to mail body', print_r($cf7->mail, true));
			if(isset($form['notification']))
				$form['notification']['message'] .= "\n\n" . (isset($form['notification']['disableAutoformat']) && $form['notification']['disableAutoformat'] ? "<br /><b>Service &quot;{$service['name']}&quot; Results:</b><br />\n":"Service \"{$service['name']}\" Results:\n") . $callback_results['attach'];
		}
		
		//if requested, attach message to success notification
		if( !empty($callback_results['message']) ) :
			// http://www.gravityhelp.com/documentation/page/Confirmation
			switch($form['confirmation']['type']) {
				case 'message':
					$form['confirmation']['message'] .= $callback_results['message'];
					break;
				case 'redirect':
					$form['confirmation']['queryString'] .= '&response_message=' . urlencode($callback_results['message']);
					break;
				case 'page':
					/// ???
					break;
			}
			
		endif;// has callback message

		return $form;
	}

	private function update_confirmation($confirmation, $nice_message, $service) {

		if(empty($service['failure'])) {
			$failure = $confirmation['type'] == 'message'
				? $confirmation['message']
				: $nice_message;
		}
		else $failure = sprintf(
			__($service['failure'], Forms3rdPartyIntegration::$instance->N())
			, $confirmation['message'] // technically we don't want this for redirect...just don't set it then
			, __($nice_message, Forms3rdPartyIntegration::$instance->N())
			);

		switch($confirmation['type']) {
			case 'message':
				// use both html and newlines just in case auto-formatting is disabled
				$confirmation['message'] = $failure;
				break;
			case 'redirect':
				$confirmation['queryString'] .= '&response_failure=' . urlencode($failure);
				break;
			case 'page':
				/// ???
				// all we have is the page id
				break;
		}
		return $confirmation;
	}

	/**
	 * Add a javascript warning for failures; also send an email to debugging recipient with details
	 * parameters passed by reference mostly for efficiency, not actually changed (with the exception of $form)
	 * 
	 * @param $form reference to plugin object - contains mail details etc
	 * @param $debug reference to this plugin "debug" option array
	 * @param $service reference to service settings
	 * @param $post reference to service post data
	 * @param $response reference to remote-request response
	 * @return the updated form reference
	 */
	public function remote_failure($form, $debug, $service, $post, $response){
		//notify frontend

		// http://www.gravityhelp.com/documentation/page/Confirmation

		// what confirmation do we update? try them all to be safe?
		$form['confirmation'] = $this->update_confirmation($form['confirmation'], $response['safe_message'], $service);
		foreach($form['confirmations'] as $conf => &$confirmation) {
			$confirmation = $this->update_confirmation($confirmation, $response['safe_message'], $service);
		}
		
		//notify admin


		$body = sprintf('There was an error when trying to integrate with the 3rd party service {%2$s} (%3$s).%1$s%1$s**FORM**%1$sTitle: %6$s%1$sIntended Recipient: %7$s%1$sSource: %8$s%1$s%1$s**SUBMISSION**%1$s%4$s%1$s%1$s**RAW RESPONSE**%1$s%5$s'
			, "\n"
			, $service['name']
			, $service['url']
			, print_r($post, true)
			, print_r($response, true)
			, $form['title']
			, isset($form['notification']) ? $form['notification']['to'] : '--na--'
			, get_bloginfo('url') . $_SERVER['REQUEST_URI']
			);
		$subject = sprintf('Gravity Forms-3rdParty Integration Failure: %s'
			, $service['name']
			);
		$headers = array('From: "GF-3rdparty Debug" <gf-3rdparty-debug@' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . '>');

		//log if couldn't send debug email
		if(!wp_mail( $debug['email'], $subject, $body, $headers )){
			### $form->additional_settings .= "\n".'on_sent_ok: \'alert("Could not send debug warning '.$service['name'].'");\'';
			error_log(__LINE__.':'.__FILE__ .'	response failed from '.$service['url'].', could not send warning email: ' . print_r($response, true));
		}

		return $form;
	}//---	end function on_response_failure




}///---	class	Forms3rdpartyIntegration_Gf