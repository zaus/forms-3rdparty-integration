<?php
/*  add the following to your functions.php, or plugin file to add callbacks */


#region ------------------------------- CUSTOM FUNCTION CALLS ---------------------------------

if(!class_exists('Cf73rdParty_ListrakCallbacks')):
/**
 * Encapsulate any and all 3rd-party service callback functions
 */
class Cf73rdParty_ListrakCallbacks {
	public function __construct(){
		
		/** subscribe **/
		
		//actions require 2 parameters: 3rd-party response, results placeholders
		# add_action('Cf73rdPartyIntegration_service_a3', array(&$this, 'listrak'), 10, 2);
		//filters require 4 parameters: placeholder, value to filter, field, and service array
		///NOTE: customize this hook name to match your Service (in the admin settings)
		add_filter('Cf73rdPartyIntegration_service_filter_post_3', array(&$this, 'listrak_subscribe_post_filter'), 10, 3);
		
		
		/** frontpage - newsletter **/
		
		//actions require 2 parameters: 3rd-party response, results placeholders
		# add_action('Cf73rdPartyIntegration_service_a4', array(&$this, 'listrak'), 10, 2);
		//filters require 4 parameters: placeholder, value to filter, field, and service array
		///NOTE: customize this hook name to match your Service (in the admin settings)
		add_filter('Cf73rdPartyIntegration_service_filter_post_4', array(&$this, 'listrak_subscribe_post_filter'), 10, 3);
	}//--	function __construct


	/**
	 * Apply filters to integration fields
	 * @see http://codex.wordpress.org/Function_Reference/add_filter
	 * 
	 * @param $values array of post values
	 * @param $service reference to service detail array
	 * @param $cf7 reference to Contact Form 7 object
	 */
	public function listrak_subscribe_post_filter($values, &$service, &$cf7){
		// --- turn single name field into first, last ---
		$field = 'Main Profile.First Name';
		if(isset($values[$field])):
			$names = explode(' ', $values[$field]);
			//extract 1st name
			$values[$field] = array_shift($names);
			//concat what's left
			$values['Main Profile.Last Name'] = implode(' ', $names);
		endif;	// isset Main Profile.First Name
		
		// --- turn checkbox prefix into full list ---
		$field = 'CheckBox.Main Profile.';
		if(isset($values[$field])):
			$list = array();
			$selected_options = $values[$field];
			unset($values[$field]);
			foreach($selected_options as $selected){
				$list[$field.$selected] = 'on';
			}
			
			//add non-selected options
			foreach($cf7->scanned_form_tags as $i => $tag){
				if( 'interested' === $tag['name'] ){
					foreach($tag['raw_values'] as $option){
						//if we haven't already seen the option, then it was unchecked
						if( ! in_array($option, $selected_options) ){
							$list[$field.$option] = 'off';
						}
					}//	loop raw_values
				}
			}//	loop scanned_form_tags
			
			$values = array_merge($values, $list);
		endif;	//isset checkbox.main profile
		
		return $values;
	}//--	function listrak_subscribe_filter


}//---	class Cf73rdParty_ListrakCallbacks

//start 'em up
$cf73rdpartycallback_instance = new Cf73rdParty_ListrakCallbacks();
endif;	//class-exists

#endregion ------------------------------- CUSTOM FUNCTION CALLS ---------------------------------


?>