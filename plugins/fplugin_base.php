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
	 * Insert new fields into the form's submission
	 * @param $form the original form "object"
	 * @param $newfields key/value pairs to inject
	 * @return $form, altered to contain the new fields
	 */
	public function INJECT($form, $newfields) {
		return $form;
	}


	/**
	 * How to update the confirmation message for a successful result
	 * @param $form the form "object"
	 * @param $message the content to report
	 * @return $form, altered to contain the message
	 */
	abstract protected function SET_OKAY_MESSAGE($form, $message);

	/**
	 * How to update the confirmation redirect for a successful result
	 * @param $form the form "object"
	 * @param $redirect the url to redirect to
	 * @return $form, altered to contain the message
	 */
	abstract protected function SET_OKAY_REDIRECT($form, $redirect);

	/**
	 * How to update the confirmation message for a failure/error
	 * @param $form the form "object"
	 * @param $message the content to report
	 * @param $safe_message a short, sanitized error message, which may already be part of the $message
	 * @return $form, altered to contain the message
	 */
	abstract protected function SET_BAD_MESSAGE($form, $message, $safe_message);
	
	/**
	 * Return the regularly intended confirmation email recipient from the form "object"
	 */
	abstract protected function GET_RECIPIENT($form);

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
			$filter = apply_filters(Forms3rdPartyIntegration::$instance->N('plugin_hooks'), (array) $this->BEFORE_SEND_FILTER());
			foreach($filter as $f) add_filter( $f, array(&Forms3rdPartyIntegration::$instance, 'before_send') );
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
		foreach($plugin_forms as $f) {
			$form = array(
				'id' => $this->FORM_ID_PREFIX() . $this->GET_FORM_LIST_ID($f)
				, 'title' => sprintf('(%s) %s', $this->REPORTING_NAME(), $this->GET_FORM_LIST_TITLE($f))
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
	public function use_form($result, $form, $service_id, $service_forms) {
		// protect against accidental binding between multiple plugins
		$this->_use_form = $result;

		// nothing to check against if nothing selected
		if( empty($service_forms) ) return $this->_use_form;

		if(!$this->IS_PLUGIN_FORM($form)) return $this->_use_form;

		// did we choose this form?
		if( in_array($this->FORM_ID_PREFIX() . $this->GET_FORM_ID($form), $service_forms) ) {
			###_log('fplugin-int using form? ' . ($result ? 'Yes' : 'No'), $service_id, $form['id']);
			$this->set_in_use();
	
			// also add subsequent hooks
			add_filter(Forms3rdPartyIntegration::$instance->N('remote_success'), array(&$this, 'remote_success'), 10, 3);
			add_filter(Forms3rdPartyIntegration::$instance->N('remote_failure'), array(&$this, 'remote_failure'), 10, 5);
			// expose injection point for other plugins
			add_filter(Forms3rdPartyIntegration::$instance->N('inject'), array(&$this, 'INJECT'), 10, 2);
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
		if(!$this->in_use()) return $submission; // return existing data, which is probably from another plugin's hook

		// interacting with user submission example -- http://ninjaforms.com/documentation/developer-api/actions/ninja_forms_process/
		$all_fields = $this->GET_FORM_SUBMISSION($form);

		// http://php.net/manual/en/language.operators.array.php
		// rather than `array_merge`, since we may have numeric indices
		// `+` returns the union of two arrays, preserving left hand side and ignoring duplicate keys from right
		$result = (array)$all_fields + (array)$submission;

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
	public function remote_success($form, $callback_results, $service) {
		###_log(__FUNCTION__, __CLASS__, $form, $callback_results);

		//if requested, attach results to message
		if(!empty($callback_results['attach'])) {
			// html -- leave it up to the instance to format properly via `attachment_heading`, etc
			$form = $this->ATTACH($form, $callback_results['attach'], $service['name']);
		}
		
		//if requested, attach message to success notification
		if( !empty($callback_results['message']) ) {
			$form = $this->SET_OKAY_MESSAGE($form, $callback_results['message']);
		}

		//if requested, attach redirect to success notification
		if( !empty($callback_results['redirect']) ) {
			$form = $this->SET_OKAY_REDIRECT($form, $callback_results['redirect']);
		}
		
		###_log(__FUNCTION__, $form);

		return $form;
	}

	/**
	 * Formats the confirmation message based on service settings
	 * @param $confirmation the original confirmation message
	 * @param $response the service response data
	 * @param $service service configuration
	 * @return $confirmation updated
	 */
	protected function update_failure_confirmation($confirmation, $response, $service) {

		if(empty($service['failure'])) {
			$failure = empty($confirmation)
				? $response['safe_message']
				: $confirmation;
		}
		else $failure =
				Forms3rdPartyIntegration::$instance->format_failure_message($service, $response, $confirmation);

		###_log(__FUNCTION__, $failure, $confirmation, $response['safe_message']);

		return $failure;
	}

	/**
	 * Add a warning for failures; also send an email to debugging recipient with details
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
		###_log(__FUNCTION__, __CLASS__, $form);

		//notify frontend

		//$form->get_form_setting($conf_setting);
		$confirmation = $this->update_failure_confirmation($this->GET_ORIGINAL_ERROR_MESSAGE($form), $response, $service);
		
		// TODO: do we add an error, or overwrite the confirmation message?

		// NOTE: assumes PHP > 5.3.14 in order to use `get_class`, which is already used by
		// ninjaforms and cf7 extensions, so...maybe overkill...
		$hook = function_exists('get_class') ? get_class($this) : __CLASS__;
		if(apply_filters($hook . '_show_warning', true)) {
			$form = $this->SET_BAD_MESSAGE($form, $confirmation, $response['safe_message']);
		}


		###_log(__FUNCTION__, __CLASS__, __LINE__);

		//notify admin
		Forms3rdPartyIntegration::$instance->send_service_error(
			$service,
			$debug,
			$post,
			$response,
			$this->GET_FORM_TITLE($form),
			$this->GET_RECIPIENT($form),
			$this->REPORTING_NAME()
			);


		return $form;
	}//---	end function on_response_failure

}///---	class	Forms3rdpartyIntegration_FPLUGIN