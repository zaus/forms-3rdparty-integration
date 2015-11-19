<?php


/**
 * Does the work of integrating FPLUGIN (Gravity Forms) with 3rdparty
 * http://www.gravityhelp.com/documentation
 */
class Forms3rdpartyIntegration_Gf extends Forms3rdpartyIntegration_FPLUGIN {

	/**
	 * An identifier (i.e. the admin page slug) for the associated Forms Plugin we're attached to
	 */
	protected function FPLUGIN() { return 'gf_edit_forms'; }
	/**
	 * What to call the submitting plugin in debug email address; defaults to @see FPLUGIN()
	 */
	protected function REPORTING_NAME() { return 'GravityForms'; }


	/**
	 * What to hook as "before_send", so this extension will process submissions
	 */
	protected function BEFORE_SEND_FILTER() { return 'gform_pre_submission_filter'; }

	/**
	 * static var so we can reuse it in upgrade too
	 */
	const FORM_ID_PREFIX = 'gf_';

	/**
	 * Used to identify form in select box, differentiating them from other plugins' forms
	 */
	protected function FORM_ID_PREFIX() { return self::FORM_ID_PREFIX; }

	/**
	 * Get the ID from the plugin's form listing
	 */
	protected function GET_FORM_LIST_ID($list_entry) { return $list_entry->id; }
	/**
	 * Get the title/name from the plugin's form listing
	 */
	protected function GET_FORM_LIST_TITLE($list_entry) { return $list_entry->title; }

	/**
	 * Get the ID from the form "object"
	 */
	protected function GET_FORM_ID($form) { return $form['id']; }
	/**
	 * Get the title from the form "object"
	 */
	protected function GET_FORM_TITLE($form) { return $form['title']; }


	/**
	 * Returns an array of the plugin's forms, loosely as ID => NAME;
	 * will be reformatted into ID => NAME by @see GET_FORM_LIST_ID and @see GET_FORM_LIST_TITLE
	 */
	protected function GET_PLUGIN_FORMS() {
		// from /wp-content/plugins/gravityforms/form_list.php, ~line 51
		return RGFormsModel::get_forms(true, "title");
	}



	/**
	 * Determine if the form "object" is from the expected plugin (i.e. check its type)
	 */
	protected function IS_PLUGIN_FORM($form) {
		// TODO: figure out a more bulletproof way to confirm it's a GF form
		return is_array($form) && isset($form['id']) && !empty($form['id']);
	}

	/**
	 * Get the posted data from the form (or POST, wherever it is)
	 */
	protected function GET_FORM_SUBMISSION($form) {
		$submission = stripslashes_deep($_POST); // fix issue #42

		### _log('gf-sub', $submission, $form['fields']);
		
		// per issue #35 also include by name
		foreach($submission as $id => $val) {
			// find the field by id -- bonus, this handles checkbox 'input_4_3' -> '4'?
			$fid = intval( str_replace('input_', '', $id) );
			if($fid == 0) continue; // not a mappable input

			$field = $this->findfield($form['fields'], $fid);

			if($field !== false) {
				if(isset($submission[ $field->label ]))
					// preserve indexes
					$submission[ $field->label ] = array_merge((array) $submission[ $field->label ], array($val));
				else
					$submission[ $field->label ] = $val;
			}
		}

		return $submission;
	}

	private function findfield($fields, $fid) {
		foreach($fields as $i => $field) {
			if($field->id == $fid) return $field;
		}
		return false;
	}

	/**
	 * How to attach the callback attachment for the indicated service (using `$this->attachment_heading` or `$this->attachment_heading_html` as appropriate)
	 * @param $form the form "object"
	 * @param $to_attach the content to attach
	 * @param $service_name the name of the service to report in the header
	 * @return $form, altered to contain the attachment
	 */
	protected function ATTACH($form, $to_attach, $service_name) {
		// http://www.gravityhelp.com/documentation/page/Notification
		###_log('attaching to mail body', print_r($cf7->mail, true));
		if(isset($form['notification']))
			$form['notification']['message'] .= "\n\n"
				. (
					isset($form['notification']['disableAutoformat']) && $form['notification']['disableAutoformat']
					? $this->attachment_heading_html($service_name)
					: $this->attachment_heading($service_name)
					)
				. $to_attach;
		
		return $form;
	}

	/**
	 * How to update the confirmation message for a successful result
	 * @param $form the form "object"
	 * @param $message the content to report
	 * @return $form, altered to contain the message
	 */
	protected function SET_OKAY_MESSAGE($form, $message) {
		$this->set_confirmation($form['confirmation'], $message);

		return $form;
	}

	private function set_confirmation(&$confirmation, $message) {
				// http://www.gravityhelp.com/documentation/page/Confirmation
		switch($confirmation['type']) {
			case 'message':
				$confirmation['message'] = $message; // already contains confirmation message, don't append
				break;
			case 'redirect':
				$confirmation['queryString'] .= '&response_message=' . urlencode($message);
				break;
			case 'page':
				/// ???
				break;
		}
	}

	/**
	 * Fetch the original error message for the form
	 */
	protected function GET_ORIGINAL_ERROR_MESSAGE($form) {
		// cheat -- because we're going to deal with multiple confirmation messages,
		// we'll use a placeholder here, and correctly format it later via sprintf if it's present
		return '%s'; //$form['confirmation'];
	}

	/**
	 * How to update the confirmation message for a failure/error
	 * @param $form the form "object"
	 * @param $message the content to report
	 * @param $safe_message a short, sanitized error message, which may already be part of the $message
	 * @return $form, altered to contain the message
	 */
	protected function SET_BAD_MESSAGE($form, $message, $safe_message) {
		// what confirmation do we update? try them all to be safe?
		$this->set_confirmation($form['confirmation'], sprintf($message, $form['confirmation']['message']));
		foreach($form['confirmations'] as $conf => &$confirmation) {
			$this->set_confirmation($confirmation, sprintf($message, $confirmation['message']));
		}
		
		return $form;
	}

	/**
	 * Return the regularly intended confirmation email recipient
	 */
	protected function GET_RECIPIENT($form) {
		return isset($form['notification']) ? $form['notification']['to'] : '--na--';
	}

}///---	class	Forms3rdpartyIntegration_Gf


// engage!
new Forms3rdpartyIntegration_Gf;
