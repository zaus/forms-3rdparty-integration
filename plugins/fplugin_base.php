<?php

/**
 * Does the work of integrating FPLUGIN (Ninja Forms) with 3rdparty
 * http://ninjaforms.com/documentation/developer-api/
 */
abstract class Forms3rdpartyIntegration_FPLUGIN {

	/**
	 * An identifier (i.e. the admin page slug) for the associated Forms Plugin we're attached to
	 */
	abstract protected function FPLUGIN();
	/**
	 * What to call the submitting plugin in debug email address; defaults to @see FPLUGIN()
	 */
	protected function REPORTING_NAME() {
		return $this->FPLUGIN();
	}

	abstract protected function BEFORE_SEND_FILTER();

	/**
	 * Used to identify form in select box, differentiating them from other plugins' forms
	 */
	abstract protected function FORM_ID_PREFIX();

	/**
	 * Returns an array of the plugin's forms as ID => NAME
	 */
	abstract protected function GET_PLUGIN_FORMS();

	/**
	 * Get the ID from the plugin's form listing
	 */
	abstract protected function GET_FORM_LIST_ID($list_entry);
	abstract protected function GET_FORM_LIST_TITLE($list_entry);

	/**
	 * Get the ID from the form "object"
	 */
	abstract protected function GET_FORM_ID($form);
	/**
	 * Get the title from the form "object"
	 */
	abstract protected function GET_FORM_TITLE($form);


	/**
	 * Determine if the form "object" is from the expected plugin (i.e. check its type)
	 */
	abstract protected function IS_PLUGIN_FORM($form);

	/**
	 * Get the posted data from the form (or POST, wherever it is)
	 */
	abstract protected function GET_FORM_SUBMISSION($form);

	/**
	 * How to attach the callback attachment for the indicated service (using `$this->attachment_heading` or `$this->attachment_heading_html` as appropriate)
	 * @param $form the form "object"
	 * @param $to_attach the content to attach
	 * @param $service_name the name of the service to report in the header
	 * @return $form, altered to contain the attachment
	 */
	abstract protected function ATTACH($form, $to_attach, $service_name);
	/* EXAMPLE
			if(isset($form['notification']))
			$form['notification']['message'] .= "\n\n" .
				(
				isset($form['notification']['disableAutoformat']) && $form['notification']['disableAutoformat']
					? $this->attachment_heading_html($service_name)
					: $this->attachment_heading($service_name)
				)
				. $to_attach;
	*/

	/**
	 * How to update the confirmation message for a successful result
	 * @param $form the form "object"
	 * @param $message the content to report
	 * @return $form, altered to contain the message
	 */
	abstract protected function SET_OKAY_MESSAGE($form, $message);

	/**
	 * How to update the confirmation message for a failure/error
	 * @param $form the form "object"
	 * @param $message the content to report
	 * @return $form, altered to contain the message
	 */
	abstract protected function SET_BAD_MESSAGE($form, $message);
	
	/**
	 * Return the regularly intended confirmation email recipient
	 */
	abstract protected function GET_FORM_RECIPIENT($Form);

	/**
	 * Fetch the original error message for the form
	 */
	abstract protected function GET_ORIGINAL_ERROR_MESSAGE($form);



	function __construct() {
		add_action(Forms3rdPartyIntegration::$instance->N('init'), array(&$this, 'init'));
		add_filter(Forms3rdPartyIntegration::$instance->N('declare_subpages'), array(&$this, 'add_subpage'));
		add_filter(Forms3rdPartyIntegration::$instance->N('use_form'), array(&$this, 'use_form'), 10, 4);
		add_filter(Forms3rdPartyIntegration::$instance->N('select_forms'), array(&$this, 'select_forms'), 10, 1);
		add_filter(Forms3rdPartyIntegration::$instance->N('get_submission'), array(&$this, 'get_submission'), 10, 2);
	}

	public function init() {
		if( !is_admin() ) {
			// http://ninjaforms.com/documentation/developer-api/actions/ninja_forms_process/
			// http://ninjaforms.com/documentation/developer-api/actions/ninja_forms_post_process/

			// this is a little tricky, because the $form object isn't available from their hook
			// like it is with GF or CF7, so we interpose an 'intermediary' hook
			// which will provide the form object instead

			add_filter( $this->BEFORE_SEND_FILTER(), array(&Forms3rdPartyIntegration::$instance, 'before_send') );
		}

		//add_action( 'init', array( &$this, 'other_includes' ), 20 );
	}

	/**
	 * Register plugin as a subpage of the given pages
	 * @param array $subpagesOf list of pages to be a subpage of -- add your target here
	 * @return the modified list of subpages
	 */
	public function add_subpage($subpagesOf) {
		$subpagesOf []= $this->FPLUGIN();
		return $subpagesOf;
	}


	/**
	 * Helper to render a select list of available FPLUGIN forms
	 * @param array $forms list of FPLUGIN forms
	 * @param array $eid entry id - for multiple lists on page
	 * @param array $selected ids of selected fields
	 */
	public function select_forms($forms){
		// from http://ninjaforms.com/documentation/developer-api/functions/ninja_forms_get_all_forms/
		// use like https://github.com/wpninjas/ninja-forms/blob/e4bc7d40c6e91ce0eee7c5f50a8a4c88d449d5f8/includes/admin/post-metabox.php#L43
		$plugin_forms = $this->GET_PLUGIN_FORMS();
		foreach($forms as $f) {
			$form = array(
				'id' => $this->FORM_ID_PREFIX() . $this->GET_FORM_LIST_ID($f)
				, 'title' => $this->GET_FORM_LIST_TITLE($f)
			);
			// add to list
			$forms []= $form;
		}

		return $forms;
	}//--	end function select_forms


	private $_use_form;
	protected function set_in_use() {
		$this->_use_form = $this->FPLUGIN();
	}
	protected function in_use() {
		return $this->_use_form == $this->FPLUGIN();
	}

	/**
	 * How do decide whether the form is being used
	 * @param bool $result           the cascading result: true to use this form
	 * @param  object $form          the FPLUGIN form object
	 * @param  int $service_id    service identifier (from hook, option setting)
	 * @param  array $service_forms list of forms attached to this service
	 * @return bool                whether or not to use this form with this service
	 */
	public function use_form($result, &$form, $service_id, $service_forms) {
		// protect against accidental binding between multiple plugins
		$this->_use_form = $result;

		// nothing to check against if nothing selected
		if( empty($service_forms) ) return $this->_use_form;

		if(!$this->IS_PLUGIN_FORM($form)) return $this->_use_form;

_log(__CLASS__, __FUNCTION__, __LINE__, $this->_use_form);

		// did we choose this form?
		if( in_array($this->FORM_ID_PREFIX() . $this->GET_FORM_ID($form), $service_forms) ) {
			### _log('fplugin-int using form? ' . ($result ? 'Yes' : 'No'), $service_id, $form['id']);
			$this->set_in_use();
	
			// also add subsequent hooks
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
	public function get_submission(&$submission, &$form){
		if(!$this->in_use()) return;

		// interacting with user submission example -- http://ninjaforms.com/documentation/developer-api/actions/ninja_forms_process/
		$all_fields = $this->GET_FORM_SUBMISSION($form);

		$result = array_merge((array)$submission, $all_fields);

				_log(__FUNCTION__, $all_fields);

		return $result;
	}

	protected function attachment_heading($service_name) {
		return "Service \"$service_name\" Results:\n";
	}
	protected function attachment_heading_html($service_name) {
		return "<br /><b>Service &quot;$service_name&quot; Results:</b><br />\n";
	}

	/**
	 * What to do when the remote request succeeds
	 * @param  array $callback_results list of 'success' (did it work), 'errors' (list of validation errors), 'attach' (email body attachment), 'message' (when failed)
	 * @param  object $form             the form object
	 * @param  array $service          associative array of the service options
	 * @return void                   n/a
	 */
	public function remote_success(&$form, &$callback_results, &$service) {
		### _log(__FUNCTION__, __CLASS__, $form, $callback_results['form']);

		//if requested, attach results to message
		if(!empty($callback_results['attach'])) {
			// html -- leave it up to the instance to format properly via `attachment_heading`, etc
			$form = $this->ATTACH($form, $callback_results['attach'], $service['name']);
		}
		
		//if requested, attach message to success notification
		if( !empty($callback_results['message']) ) {
			$form = $this->SET_OKAY_MESSAGE($form, $callback_results['message']);
		}

		return $form;
	}

	/**
	 * Formats the confirmation message based on service settings
	 * @param $confirmation the original confirmation message
	 * @param $response the service response data
	 * @param $service service configuration
	 * @return $confirmation updated
	 */
	protected function update_failure_confirmation($confirmation, &$response, &$service) {

		if(empty($service['failure'])) {
			$failure = empty($confirmation)
				? $confirmation
				: $response['safe_message'];
		}
		else $failure = Forms3rdPartyIntegration::$instance->format_failure_message($service, $response, 
			$confirmation
			);

		return $failure;
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

		// http://ninjaforms.com/documentation/developer-api/code-examples/modifying-form-settings-and-behavior/

		### TODO:  not sure what the 'original' failure message would be here...
		//$conf_setting = 'success_msg';
		//$form->get_form_setting($conf_setting);
		$confirmation = $this->update_failure_confirmation($this->GET_ORIGINAL_ERROR_MESSAGE($form), $response, $service);
		
		// TODO: do we add an error, or overwrite the confirmation message?
		$form = $this->SET_BAD_MESSAGE($form, $confirmation);

		//notify admin
		Forms3rdPartyIntegration::$instance->send_service_error(
			$service,
			$debug,
			$post,
			$response,
			$this->GET_FORM_TITLE($form),
			$$this->GET_RECIPIENT($form),
			$this->REPORTING_NAME()
			);


		return $form;
	}//---	end function on_response_failure

	private function get_recipient($form) {

		// https://github.com/wpninjas/ninja-forms/blob/e4bc7d40c6e91ce0eee7c5f50a8a4c88d449d5f8/includes/display/processing/email-user.php

		$user_mailto = array();
		$all_fields = $form->get_all_fields();
		if(is_array($all_fields) AND !empty($all_fields)){
			foreach($all_fields as $field_id => $user_value) {
				$field_row = $form->get_field_settings( $field_id );

				if(isset($field_row['data']['send_email'])){
					$send_email = $field_row['data']['send_email'];
				}else{
					$send_email = 0;
				}

				if($send_email) {
					array_push($user_mailto, $user_value);
				}
			}// foreach
		}// if
	}//--	fn	get_recipient


}///---	class	Forms3rdpartyIntegration_FPLUGIN


