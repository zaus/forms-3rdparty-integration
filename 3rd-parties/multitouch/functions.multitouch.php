<?php
/*  add the following to your functions.php, or plugin file to add callbacks */


#region ------------------------------- CUSTOM FUNCTION CALLS ---------------------------------

if(!class_exists('Cf73rdPartyCallbacks')):
/**
 * Encapsulate any and all 3rd-party service callback functions
 */
class Cf73rdPartyCallbacks {
	public function __construct(){
		//actions require 2 parameters: 3rd-party response, results placeholders
		add_action('Cf73rdPartyIntegration_service_a0', array(&$this, 'multitouch1'), 10, 2);
		//filters require 4 parameters: placeholder, value to filter, field, and service array
		add_filter('Cf73rdPartyIntegration_service_filter_post_0', array(&$this, 'multitouch1_filter'), 10, 3);
	}//--	function __construct
	
	/**
	 * Callback hook for 3rd-party service Multitouch
	 * @param $response the remote-request response (in this case, it's a serialized string)
	 * @param &$results the callback return results (passed by reference since function can't return a value; also must be "constructed by reference"; see plugin)
	 */
	public function multitouch1($response, &$results){
		try{
			//unserialize results to append to email
			$data = unserialize($response);
			
			//only if it worked!
			if(!$data['success']):
				$output = '<strong>Errors:</strong><ul><li>'.(is_array($data['errors']) ? implode('</li><li>', $data['errors']) : $data['errors']).'</li></ul>';
				$results['errors'] = $data['errors'];
			else:
			
				//easier output
				
				$columns = array(
					'id' => 'Visit ID'
					, 'account' => 'Account'
					, 'date' => 'Date'
					, 'visit_url' => 'Page'
					, 'user_id' => 'User'
					, 'user_ip' => 'User IP'
					, 'action' => 'Action'
					, 'referer' => 'Referer'
					, 'referer_keywords' => 'Keywords'
					, 'ppc' => 'PPC'
					, 'var1' => 'Var1'
					, 'var2' => 'Var2'
					, 'var3' => 'Var3'
				);
			
				ob_start();
				?>
				<div class="query">
					<h3>Results returned for filters:</h3>
					<dl>
						<?php foreach($data['filters'] as $filter => $filter_detail){
							?><dt><?php echo $filter; ?></dt>
						<dd><?php echo is_array($filter_detail) ? implode(' ', $filter_detail) : $filter_detail; ?></dd>
							<?php
						}?>
					</dl>
				</div>
<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
	<thead>
		<tr>
			<?php
			foreach($columns as $field=>$label):
				?><th id="th-<?php echo $field; ?>"><?php echo $label; ?></th><?php
			endforeach; ?>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach($data['submissions'] as $row => $entry):
		?>
		<tr<?php if($row%2 == 1): echo ' style="background-color:#ccc;" class="alt"'; endif; ?>>
			<?php
			foreach($columns as $field=>$label):
				?><td headers="th-<?php echo $field; ?>"><?php echo $entry[$field]; ?></td><?php
			endforeach; ?>
		</tr>
		<?php
	endforeach;
	?>
	</tbody>
</table>
				<?php
				$output = ob_get_clean();
			endif;	//if $data['success']
			
			$results['attach'] = $output;
			
			///add_filter('wpcf7_mail_components', (&$this, 'filter_'.__FUNCTION__));
		} catch(Exception $ex){
			$results['errors'] = array($ex->getMessage());
		}
		
		#wp_mail( 'debug_address@email.com', 'Callback Hit', 'callback:'.__FUNCTION__."\n\ndata:\n".print_r($response,true)."\n\nresults:\n".print_r($results, true) );
	}//--	function multitouch1

	/**
	 * Apply filters to integration fields
	 * @see http://codex.wordpress.org/Function_Reference/add_filter
	 * 
	 * @param $values array of post values
	 * @param $service reference to service detail array
	 * @param $cf7 reference to Contact Form 7 object
	 */
	public function multitouch1_filter($values, &$service, &$cf7){
		foreach($values as $field => &$value):
		//filter depending on field
		switch($field){
			case 'filters':
				//look for placeholders, replace with stuff
				$orig = $value;
				if(strpos($value, '{IP}') !== false){
					$headers = apache_request_headers(); 
					$ip = isset($headers['X-Forwarded-For']) ? $headers['X-Forwarded-For'] : $_SERVER['REMOTE_ADDR'];
					$value = str_replace('{IP}', $ip, $value);
				}
				### _log("changed from $orig to $value in $field");
				break;
		}
		endforeach;
		
		return $values;
	}//--	function multitouch1_filter

}//---	class Cf73rdPartyCallbacks

//start 'em up
$cf73rdpartycallback_instance = new Cf73rdPartyCallbacks();
endif;	//class-exists

#endregion ------------------------------- CUSTOM FUNCTION CALLS ---------------------------------

?>