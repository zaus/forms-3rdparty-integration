<?php


/**
 * Does the work of integrating FPLUGIN (Formidable) with 3rdparty
 * https://formidablepro.com/knowledgebase-category/advanced-for-developers/
 */
class Forms3rdpartyIntegration_Formidable extends Forms3rdpartyIntegration_FPLUGIN {

	/**
	 * An identifier (i.e. the admin page slug) for the associated Forms Plugin we're attached to
	 */
	protected function FPLUGIN() { return 'formidable'; }
	
	protected function REPORTING_NAME() { return 'Formidable'; }
	
	/**
	 * What to hook as "before_send", so this extension will process submissions
	 */
	//protected function BEFORE_SEND_FILTER() { return 'frm_after_create_entry'; }
	protected function BEFORE_SEND_FILTER() { return 'frm_entries_before_create'; }

	/**
	 * Used to identify form in select box, differentiating them from other plugins' forms
	 */
	protected function FORM_ID_PREFIX() { return 'frm_'; }

	/**
	 * Returns an array of the plugin's forms, loosely as ID => NAME;
	 * will be reformatted into ID => NAME by @see GET_FORM_LIST_ID and @see GET_FORM_LIST_TITLE
	 */
	protected function GET_PLUGIN_FORMS() {
		$forms = FrmForm::get_published_forms();
		return $forms;
	}

	/**
	 * Get the ID from the plugin's form listing
	 */
	protected function GET_FORM_LIST_ID($list_entry) { return $list_entry->id; }
	protected function GET_FORM_LIST_TITLE($list_entry) { return $list_entry->name; }

	/**
	 * Get the ID from the form "object"
	 */
	protected function GET_FORM_ID($form) {
		return $form->id;
	}
	/**
	 * Get the title from the form "object"
	 */
	protected function GET_FORM_TITLE($form) {
		return $form->name;
	}


	/**
	 * Determine if the form "object" is from the expected plugin (i.e. check its type)
	 */
	protected function IS_PLUGIN_FORM($form) {		
		return is_object($form) && $form instanceof stdClass
			&& isset($form->form_key) && isset($form->name)
			&& $form->status == 'published' && is_array($form->options);

		// The Formidable "form object" is just a stdClass so instanceof
		// doesn't tell us much, but we can rely on:
		// - a defined form_key field
		// - a defined name field
		// - a status field equal to the string "published"
		// - an options field which is an array
		// hopefully this will be enough to differentiate from other Forms plugins
	}

	/**
	 * Get the posted data from the form (or POST, wherever it is)
	 */
	protected function GET_FORM_SUBMISSION($form) {
		$submission = array();
		foreach($_POST['item_meta'] as $field_id => $value) {
			if ($field_id) {
				$submission[$field_id] = $value;
				// keyed by integer field ID
				
				$field = FrmField::getOne($field_id);
				$submission[$field->name] = $value;
				// keyed by field name
			}
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
		$actions = FrmFormAction::get_action_for_form($form->id);
		if (is_array($actions) && count($actions)) {
			$action = reset($actions);
			$heading = $action->post_content['plain_text']
				? $this->attachment_heading($service_name)
				: $this->attachment_heading_html($service_name);
			$action->email_message .= "\n\n$heading$to_attach";
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
		$form->options['success_msg'] = $message;
		return $form;
	}

	/**
	 * How to update the confirmation message for a failure/error
	 * @param $form the form "object"
	 * @param $message the content to report
	 * @param $safe_message a short, sanitized error message, which may already be part of the $message
	 * @return $form, altered to contain the message
	 */
	protected function SET_BAD_MESSAGE($form, $message, $safe_message) {
		$this->errors[] = $message;
		return $form;
	}

	/**
	 * Return the regularly intended confirmation email recipient
	 */
	protected function GET_RECIPIENT($form) {
		$actions = FrmFormAction::get_action_for_form($form->id);
		if (is_array($actions) && count($actions)) {
			$action = reset($actions);
			return $action->post_content['email_to'];
		}
		return false;
	}

	/**
	 * Fetch the original error message for the form
	 */
	protected function GET_ORIGINAL_ERROR_MESSAGE($form) {		
		if (is_array($this->errors) && count($this->errors)) {
			return $this->errors[0];
		} else {
			return $form->options['success_msg'];
		}
	}
	
	/**
	 * Overridding regular initialization, because ninjaforms needs an intermediary step
	 */
	public function init() {
		if( !is_admin() ) {			
			add_filter($this->BEFORE_SEND_FILTER(), array($this, 'before_send_intercept'), 10, 2);
			add_filter( __CLASS__, array(&Forms3rdPartyIntegration::$instance, 'before_send') );
		}

	}

	/**
	 * Intermediary hook attached to FPLUGIN submission processing
	 * that will retrieve the $form object to provide to the Forms-3rdparty
	 * `before_send` hook, like "usual" (i.e. CF7 and GF)
	 * Originally from the Ninja Forms version
	 */	
	public function before_send_intercept($errors, $form) {
		$this->errors = $errors;
		$result = apply_filters(__CLASS__, $form);
		return $this->errors;
	}

		
}///---	class	Forms3rdpartyIntegration_Formidable


// engage!
new Forms3rdpartyIntegration_Formidable;
