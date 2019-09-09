<?php


/**
 * Does the work of integrating FPLUGIN (Ninja Forms) with 3rdparty
 * http://ninjaforms.com/documentation/developer-api/
 */
class Forms3rdpartyIntegration_Ninja extends Forms3rdpartyIntegration_FPLUGIN {

	/**
	 * An identifier (i.e. the admin page slug) for the associated Forms Plugin we're attached to
	 */
	protected function FPLUGIN() { return 'ninja-forms'; }
	
	/**
	 * What to hook as "before_send", so this extension will process submissions
	 */
	protected function BEFORE_SEND_FILTER() { return 'ninja_forms_after_submission'; } // `ninja_forms_after_submission` might be too late? but `ninja_forms_submit_data` is before ninja handles it

	/**
	 * Used to identify form in select box, differentiating them from other plugins' forms
	 */
	protected function FORM_ID_PREFIX() { return 'njn_'; }

	/**
	 * Returns an array of the plugin's forms, loosely as ID => NAME;
	 * will be reformatted into ID => NAME by @see GET_FORM_LIST_ID and @see GET_FORM_LIST_TITLE
	 */
	protected function GET_PLUGIN_FORMS() { return ninja_forms_get_all_forms(); }

	/**
	 * Get the ID from the plugin's form listing
	 */
	protected function GET_FORM_LIST_ID($list_entry) { return $list_entry['id']; }
	protected function GET_FORM_LIST_TITLE($list_entry) { return $list_entry['data']['form_title']; }

	/**
	 * Get the ID from the form "object"
	 */
	protected function GET_FORM_ID($form) { return $form['form_id']; }
	/**
	 * Get the title from the form "object"
	 */
	protected function GET_FORM_TITLE($form) {
		return $form['settings']['title'];
	}


	/**
	 * Determine if the form "object" is from the expected plugin (i.e. check its type)
	 */
	protected function IS_PLUGIN_FORM($form) {
		###_log(__CLASS__, __FUNCTION__, substr(print_r($form, true), 0, 100) . '...');
		
		// pick some things that seem unique to ninjaforms form array (particularly after v3)
		return is_array($form)
			&& isset($form['form_id'])
			&& isset($form['settings'])
			&& isset($form['fields'])
			&& isset($form['fields_by_key'])
			;
	}

	/**
	 * Get the posted data from the form (or POST, wherever it is)
	 */
	protected function GET_FORM_SUBMISSION($form) {
		// interacting with user submission example -- http://ninjaforms.com/documentation/developer-api/actions/ninja_forms_process/
		$submission = array();

		// per issue #35 also include by name
		foreach($form['fields'] as $id => $field) {
			### _log('nja-fld ' . $id, $field);
			$val = $field['value'];
			$submission[ $field['id'] ] = $val;
			$submission[ $field['key'] ] = $val;
		}

		return $submission;
	}

	/**
	 * How to attach the callback attachment for the indicated service (using `$this->attachment_heading` or `$this->attachment_heading_html` as appropriate)
	 * @param $form the form "object"
	 * @param $to_attach the content to attach
	 * @param $service_name the name of the service to report in the header
	 * @return $form, altered to contain the attachment
	 */
	protected function ATTACH($form, $to_attach, $service_name) {
		// http://ninjaforms.com/documentation/developer-api/code-examples/modifying-form-settings-and-behavior/
		// need to hook before `ninja_form_process`?
		// although may be able to use hook `ninja_forms_user_email` in post_process -- https://github.com/wpninjas/ninja-forms/blob/e4bc7d40c6e91ce0eee7c5f50a8a4c88d449d5f8/includes/display/processing/filter-msgs.php
		$body = &$form['actions']['email']['sent'];


		if(isset($body)) {
			$body .= "\n\n" .
				(
				substr(ltrim($body), 0, 1) === '<'
					? $this->attachment_heading_html($service_name)
					: $this->attachment_heading($service_name)
				)
				. $to_attach;
		}

		return $form; // just to match expectation
	}

	/**
	 * Insert new fields into the form's submission
	 * @param $form the original form "object"
	 * @param $newfields key/value pairs to inject
	 * @return $form, altered to contain the new fields
	 */
	public function INJECT($form, $newfields) {
		// TODO: TBD -- maybe this works like gravity forms?
		foreach($newfields as $k => $v) {
			// don't overwrite with empty values (but is that always appropriate?), see forms-3rdparty-inject-results#1
			// not sure how to actually inject, but at least we can overwrite
			if(!empty($v) && isset($form['fields_by_key'][$k])) {
				$_POST[$k] = $v;
				$field = &$form['fields_by_key'][$k];
				$field['value'] = $v;
				// also overwrite the 'regular' field list (by id)
				$form['fields'][$field['id']]['value'] = $v;
			}
		}
		return $form;
	}


	/**
	 * How to update the confirmation message for a successful result
	 * @param $form the form "object"
	 * @param $message the content to report
	 * @return $form, altered to contain the message
	 */
	protected function SET_OKAY_MESSAGE($form, $message) {
		
		// like https://github.com/wpninjas/ninja-forms/blob/e4bc7d40c6e91ce0eee7c5f50a8a4c88d449d5f8/includes/display/processing/filter-msgs.php
		
		$form['actions']['success_message'] = wpautop($message);

		return $form; // just to match expectation
	}

	/**
	 * How to update the confirmation redirect for a successful result
	 * @param $form the form "object"
	 * @param $redirect the url to redirect to
	 * @return $form, altered to contain the message
	 */
	protected function SET_OKAY_REDIRECT($form, $redirect) {
		$url = esc_url_raw( $redirect );
		$form['actions']['success_message'] .= wpautop("<script type=\"text/javascript\">window.open('$url', '_blank');</script>");
		
		return $form; // just to match expectation
	}


	/**
	 * How to update the confirmation message for a failure/error
	 * @param $form the form "object"
	 * @param $message the content to report
	 * @param $safe_message a short, sanitized error message, which may already be part of the $message
	 * @return $form, altered to contain the message
	 */
	protected function SET_BAD_MESSAGE($form, $message, $safe_message) {
		return $this->SET_OKAY_MESSAGE($form, $message);
	}

	/**
	 * Return the regularly intended confirmation email recipient
	 */
	protected function GET_RECIPIENT($form) {
		return $form['actions']['email']['to'];
	}

	/**
	 * Fetch the original error message for the form
	 */
	protected function GET_ORIGINAL_ERROR_MESSAGE($form) {
		### TODO: not sure what the original failure message would be...
		return $form['actions']['success_message'];
	}


	/**
	 * Register all plugin hooks; override in form-specific plugins if necessary
	 */
	public function init() {
		// because ninja forms submits via ajax, can't check for `is_admin` anymore (> 3.0)
		// if( !is_admin() ) {
			$filter = apply_filters(Forms3rdPartyIntegration::$instance->N('plugin_hooks'), (array) $this->BEFORE_SEND_FILTER());
			###_log(__CLASS__, $filter);
			foreach($filter as $f) add_filter( $f, array(&Forms3rdPartyIntegration::$instance, 'before_send') );
		// }

		//add_action( 'init', array( &$this, 'other_includes' ), 20 );
	}
	
}///---	class	Forms3rdpartyIntegration_Ninja


// engage!
new Forms3rdpartyIntegration_Ninja;