<?php
/*  add the following to your functions.php, or plugin file to add callbacks */


#region ------------------------------- CUSTOM FUNCTION CALLS ---------------------------------

if(!class_exists('Cf73rdParty_MailchimpCallbacks')):
/**
 * Encapsulate any and all 3rd-party service callback functions
 */
class Cf73rdParty_MailchimpCallbacks {
	public function __construct(){
		/** newsletter - mailchimp - subscribe **/
		
		//actions require 2 parameters: 3rd-party response, results placeholders
		///NOTE: customize this hook name to match your Service (in the admin settings)
		add_action('Cf73rdPartyIntegration_service_a5', array(&$this, 'mailchimp_newsletter_action'), 10, 2);
		
	}//--	function __construct


	/**
	 * Callback hook for 3rd-party service Mailchimp - Newsletter signup form
	 * @param $response the remote-request response (in this case, it's a serialized string)
	 * @param &$results the callback return results (passed by reference since function can't return a value; also must be "constructed by reference"; see plugin)
	 */
	public function mailchimp_newsletter_action($response, &$results){
		try{
			// look once more for success message, in case someone didn't set up the success clause
			if( false !== strpos($response, 'please click the link in the email we just sent you') ) :
				$results['message'] = 'To complete the subscription process, please click the link in the email we just sent you.';
			else:
				$results['errors'] = array('Failed to submit response to Newsletter Signup at MailChimp');
			endif;
			
			///add_filter('wpcf7_mail_components', (&$this, 'filter_'.__FUNCTION__));
		} catch(Exception $ex){
			$results['errors'] = array($ex->getMessage());
		}
		
		#wp_mail( 'debug_address@email.com', 'Callback Hit', 'callback:'.__FUNCTION__."\n\ndata:\n".print_r($response,true)."\n\nresults:\n".print_r($results, true) );
	}//--	function mailchimp_newsletter_action
	

}//---	class Cf73rdParty_MailchimpCallbacks

//start 'em up
$cf73rdpartycallback_instance = new Cf73rdParty_MailchimpCallbacks();
endif;	//class-exists

#endregion ------------------------------- CUSTOM FUNCTION CALLS ---------------------------------
