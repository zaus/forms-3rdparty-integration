<?php


/**
 * Does the work of integrating FPLUGIN (CF7) with 3rdparty
 */
class Forms3rdpartyIntegration_CF7 extends Forms3rdpartyIntegration_FPLUGIN {

	/**
	 * An identifier (i.e. the admin page slug) for the associated Forms Plugin we're attached to
	 */
	protected function FPLUGIN() { return 'wpcf7'; }
	/**
	 * What to call the submitting plugin in debug email address; defaults to @see FPLUGIN()
	 */
	protected function REPORTING_NAME() { return 'CF7'; }


	/**
	 * What to hook as "before_send", so this extension will process submissions
	 */
	protected function BEFORE_SEND_FILTER() { return 'wpcf7_before_send_mail'; }

	/**
	 * Used to identify form in select box, differentiating them from other plugins' forms
	 */
	protected function FORM_ID_PREFIX() { return 'cf7_'; }

	/**
	 * Get the ID from the plugin's form listing
	 */
	protected function GET_FORM_LIST_ID($list_entry) { return $list_entry->ID; /* WP Post */ }
	/**
	 * Get the title/name from the plugin's form listing
	 */
	protected function GET_FORM_LIST_TITLE($list_entry) { return $list_entry->post_title; /* WP Post */ }

	/**
	 * Get the ID from the form "object"
	 */
	protected function GET_FORM_ID($form) { return $form->id(); }
	/**
	 * Get the title from the form "object"
	 */
	protected function GET_FORM_TITLE($form) { return $form->title(); }


	/**
	 * Returns an array of the plugin's forms, loosely as ID => NAME;
	 * will be reformatted into ID => NAME by @see GET_FORM_LIST_ID and @see GET_FORM_LIST_TITLE
	 */
	protected function GET_PLUGIN_FORMS() {
		// since they're stored as a custom post type
		return get_posts( array(
			'numberposts' => -1,
			'orderby' => 'ID',
			'order' => 'ASC',
			'post_type' => 'wpcf7_contact_form' ) );
	}



	/**
	 * Determine if the form "object" is from the expected plugin (i.e. check its type)
	 */
	protected function IS_PLUGIN_FORM($form) {
		return 'WPCF7_ContactForm' == get_class($form);
	}

	/**
	 * Get the posted data from the form (or POST, wherever it is)
	 */
	protected function GET_FORM_SUBMISSION($form) {
		return WPCF7_Submission::get_instance()->get_posted_data();
	}

	/**
	 * How to attach the callback attachment for the indicated service (using `$this->attachment_heading` or `$this->attachment_heading_html` as appropriate)
	 * @param $form the form "object"
	 * @param $to_attach the content to attach
	 * @param $service_name the name of the service to report in the header
	 * @return $form, altered to contain the attachment
	 */
	protected function ATTACH($form, $to_attach, $service_name) {
		$mail = $form->prop('mail'); // previous style: $form->mail
		$mail['body'] .= "\n\n" . (
								$mail['use_html']
									? $this->attachment_heading_html($service_name)
									: $this->attachment_heading($service_name)
								)
								. $to_attach;
		$form->set_properties(array('mail'=>$mail));
		
		return $form; // yes this is redundant when it's an object, but need it for compatibility with GF
	}

	/**
	 * How to update the confirmation message for a successful result
	 * @param $form the form "object"
	 * @param $message the content to report
	 * @return $form, altered to contain the message
	 */
	protected function SET_OKAY_MESSAGE($form, $message) {
		$messages = $form->prop('messages');
		$messages['mail_sent_ok'] = $message;
		$form->set_properties(array('messages'=>$messages));

		return $form;
	}

	/**
	 * Fetch the original error message for the form
	 */
	protected function GET_ORIGINAL_ERROR_MESSAGE($form) {
		$messages = $form->prop('messages');
		return $messages['mail_sent_ng'];
	}

	/**
	 * How to update the confirmation message for a failure/error
	 * @param $form the form "object"
	 * @param $message the content to report
	 * @return $form, altered to contain the message
	 */
	protected function SET_BAD_MESSAGE($form, $message) {

		$additional = sprintf("%s\non_sent_ok: 'if(window.console && console.warn){ console.warn(\"Failed cf7 integration: %s\"); }'"
			, $form->prop('additional_settings')
			, $message);
		
		// recreate property array to submit
		$result = array('additional_settings' => $additional);

		// do we always report, or just pretend it worked, because the original contact plugin may be fine...

		// kind of a hack -- override the success and fail messages, just in case one or other is displayed
		$messages = $form->prop('messages');
		
		$messages['mail_sent_ok'] =
		$messages['mail_sent_ng'] = 

			Forms3rdPartyIntegration::$instance->format_failure_message(
				$service,
				$response,
				$messages['mail_sent_ng']
				);
		
		// $messages['mail_sent_ok'] = isset($service['failure']) ? $service['failure'] : $messages['mail_sent_ng'];
		$result['messages'] = $messages;
		
		$form->set_properties($result);
		
		return $form;
	}

	/**
	 * Return the regularly intended confirmation email recipient
	 */
	protected function GET_FORM_RECIPIENT($form) {
		$mail = $form->prop('mail');
		return isset($mail['recipient']) ? $mail['recipient'] : '--na--';
	}

}///---	class	Forms3rdpartyIntegration_CF7


// engage!
new Forms3rdpartyIntegration_CF7;