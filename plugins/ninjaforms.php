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
	protected function BEFORE_SEND_FILTER() { return 'ninja_forms_process'; }

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
	protected function GET_FORM_ID($form) { return $form->get_form_ID(); }
	/**
	 * Get the title from the form "object"
	 */
	protected function GET_FORM_TITLE($form) {
		return $form->get_form_setting('form_title');
	}


	/**
	 * Determine if the form "object" is from the expected plugin (i.e. check its type)
	 */
	protected function IS_PLUGIN_FORM($form) { 
		return is_object($form) && 'Ninja_Forms_Processing' == get_class($form);
	}

	/**
	 * Get the posted data from the form (or POST, wherever it is)
	 */
	protected function GET_FORM_SUBMISSION($form) {
		// interacting with user submission example -- http://ninjaforms.com/documentation/developer-api/actions/ninja_forms_process/
		$submission = $form->get_all_fields();

		// per issue #35 also include by name
		foreach($submission as $id => $val) {
			$field = $form->get_field_settings($id);
			### _log('nja-fld ' . $id, $field);
			$submission[ $field['data']['label'] ] = $val;
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
		$setting = 'admin_email_msg'; //'user_email_msg'?;
		$body = $form->get_form_setting($setting);


		if(isset($body)) {
			$body .= "\n\n" .
				(
				$form->get_form_setting( 'email_type' ) == 'html'
					? $this->attachment_heading_html($service_name)
					: $this->attachment_heading($service_name)
				)
				. $to_attach;
			$form->update_form_setting($setting, $body);
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
			if(!empty($v)) $_POST[$k] = $v;
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
		
		$setting = 'success_msg';
		$original_message = $form->get_form_setting($setting);
		$form->update_form_setting($setting, wpautop($message));

		return $form; // just to match expectation
	}

	/**
	 * How to update the confirmation redirect for a successful result
	 * @param $form the form "object"
	 * @param $redirect the url to redirect to
	 * @return $form, altered to contain the message
	 */
	protected function SET_OKAY_REDIRECT($form, $redirect) {
		$setting = 'success_msg';
		$message = $form->get_form_setting($setting);
		$url = esc_url_raw( $redirect );
		$message .= "<script type=\"text/javascript\">window.open('$url', '_blank');</script>";
		$form->update_form_setting($setting, wpautop($message));

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
		// http://ninjaforms.com/documentation/developer-api/code-examples/modifying-form-settings-and-behavior/
		// TODO: do we add an error, or overwrite the confirmation message?

		### TODO:  not sure what the 'original' failure message would be here...
		//$conf_setting = 'success_msg';
		//$form->get_form_setting($conf_setting);

		// http://ninjaforms.com/documentation/developer-api/ninja_forms_processing/
		$form->add_error(__CLASS__, $message, 'general');
		//$form->update_form_setting($conf_setting, $message);
		
		return $form; // just to match expectation
	}

	/**
	 * Return the regularly intended confirmation email recipient
	 */
	protected function GET_RECIPIENT($form) {
		// to get the recipient, we need to scan all form fields
		// and find ones with `send_email` set?

		// see https://github.com/wpninjas/ninja-forms/blob/e4bc7d40c6e91ce0eee7c5f50a8a4c88d449d5f8/includes/display/processing/email-user.php

		$user_mailto = array();
		$all_fields = $form->get_all_fields();
		if(is_array($all_fields) AND !empty($all_fields)){
			foreach($all_fields as $field_id => $user_value) {
				$field_row = $form->get_field_settings( $field_id );

				if(isset($field_row['data']['send_email']) && $field_row['data']['send_email']){
					array_push($user_mailto, $user_value);
				}
			}// foreach
		}// if
		/**/
		
		return $user_mailto;
		
		/*
		// see https://github.com/wpninjas/ninja-forms/blob/e4bc7d40c6e91ce0eee7c5f50a8a4c88d449d5f8/includes/display/processing/email-user.php
		return $form->get_form_setting( 'user_email_fields' ) == 1
			? $form->get_form_setting( 'user_email' )
			: '--no recipient--';
		*/
	}

	/**
	 * Fetch the original error message for the form
	 */
	protected function GET_ORIGINAL_ERROR_MESSAGE($form) {
		### TODO: not sure what the original failure message would be...
		return $form->get_form_setting('success_msg');
	}
	

	/**
	 * Overridding regular initialization, because ninjaforms needs an intermediary step
	 */
	public function init() {
		if( !is_admin() ) {
			// http://ninjaforms.com/documentation/developer-api/actions/ninja_forms_process/
			// http://ninjaforms.com/documentation/developer-api/actions/ninja_forms_post_process/

			// this is a little tricky, because the $form object isn't available from their hook
			// like it is with GF or CF7, so we interpose an 'intermediary' hook
			// which will provide the form object instead

			add_filter( $this->BEFORE_SEND_FILTER(), array(&$this, 'before_send_intercept') );
			add_filter( __CLASS__, array(&Forms3rdPartyIntegration::$instance, 'before_send') );
		}

	}

	/**
	 * Intermediary hook attached to FPLUGIN submission processing
	 * that will retrieve the $form object to provide to the Forms-3rdparty
	 * `before_send` hook, like "usual" (i.e. CF7 and GF)
	 */
	public function before_send_intercept() {
		// get the ninja form object
		// via global accessor http://ninjaforms.com/documentation/developer-api/ninja_forms_processing/
		global $ninja_forms_processing;

		// provide it to the regular `before_send` hook, since it's basically the form object
		return apply_filters(__CLASS__, $ninja_forms_processing);
	}
	
}///---	class	Forms3rdpartyIntegration_Ninja


// engage!
new Forms3rdpartyIntegration_Ninja;