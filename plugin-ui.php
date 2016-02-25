<?php
	///TODO: use "best" option schema - http://planetozh.com/blog/2009/05/handling-plugins-options-in-wordpress-28-with-register_setting/


	$P = $this->N;
	
	if( isset($_POST[$P]) && check_admin_referer($P, $P.'_nonce') ) {
		$options = stripslashes_deep($_POST[$P]);

		// expected fields not really used...
		$expectedFields = array(
			'name'
			, 'url'
			,'mapping'
			, 'success'
			, 'failure'
			, 'forms'
			, 'timeout'
			,self::PARAM_LBL
			,self::PARAM_SRC
			,self::PARAM_3RD
		);
		#pbug($options);
		
		// update_option( $this->N('settings'), $options);
		$this->save_settings($options['debug']);
		$this->save_services($options); // technically, this will overwrite the settings section anyway...
		
		echo '<div id="message" class="updated fade"><p><strong>' . __('Settings saved.') . '</strong></p></div>';
	}
	else {
		$options = array('debug' => $this->get_settings()) + $this->get_services();
		// get_option( $this->N('settings')
	}
	
	
	//prepare list of contact forms --
	$forms = apply_filters($this->N('select_forms'), array());

	$debugUrl = plugins_url('3rd-parties/service_test.php', __FILE__);

?>
		<div id="<?php echo $P?>" class="wrap metabox-holder"><div id="poststuff">
		
		<h2><?php _e(self::pluginPageTitle);?> &mdash; <?php _e('Settings');?></h2>
		<div class="description">
			<p><?php _e('Set options for 3rd-party integration', $P); ?>.</p>
			<p><?php _e('Map each Form plugin field to its corresponding field in the 3rd-Party service', $P); ?>.</p>
			<p><?php _e('If you need to submit a value directly, check the &quot;Is Value?&quot; box and enter the value for the <em>Form plugin Field</em> column', $P); ?>.</p>
		</div>
		
		<form method="post">
		<?php wp_nonce_field($P, $P.'_nonce'); ?>
			
		
		<fieldset class="postbox"><legend><span>Global Values</span></legend><div class="inside">
			<?php
			$debugOptions = $options['debug'];
			//remove from list for looping
			unset($options['debug']);
			?>
			<div class="field">
				<label for="dbg-email">Email</label>
				<input id="dbg-email" type="text" class="text" name="<?php echo $P?>[debug][email]" value="<?php echo esc_attr($debugOptions['email'])?>" />
				<em class="description"><?php _e('Notification for failures (3rdparty errors, success condition not met) - also used as the debug recipient.  Comma-separate multiple addresses.', $P)?></em>
			</div>
			<div class="field">
				<label for="dbg-email">Email Sender</label>
				<input id="dbg-email" type="text" class="text" name="<?php echo $P?>[debug][sender]" value="<?php echo esc_attr($debugOptions['sender'])?>" />
				<em class="description"><?php _e('Optionally specify the "Sent from" email address for the notifications (failure, debug emails).  If not provided will default to the blog administrator.  Note: some hosting providers may reject arbitrary addresses.', $P)?></em>
			</div>
			<div class="field">
				<label for="dbg-debugmode"><?php _e('Debug Mode', $P); ?></label>
				<input id="dbg-debugmode" type="checkbox" class="checkbox" name="<?php echo $P?>[debug][mode][]" <?php echo $this->selected_input_array($debugOptions, 'mode', 'debug', 'checked'); ?> />
				<em class="description"><?php _e('Send debugging information to indicated address, regardless of success or failure', $P)?>.</em>
				<em class="description"><?php _e('Send service tests for full echo to:', $P); ?> <code><?php echo $debugUrl ?></code></em>
			</div>
			<div class="field">
				<label for="dbg-fallback"><?php _e('Logging Fallback', $P); ?></label>
				<input id="dbg-fallback" type="checkbox" class="checkbox" name="<?php echo $P?>[debug][mode][]" <?php echo $this->selected_input_array($debugOptions, 'mode', 'log', 'checked'); ?> />
				<em class="description"><?php _e('If unable to send the debug message, do we log the response instead?', $P)?>.</em>
			</div>
			<div class="field">
				<label for="dbg-full"><?php _e('Logging Fallback - include service', $P); ?></label>
				<input id="dbg-full" type="checkbox" class="checkbox" name="<?php echo $P?>[debug][mode][]" <?php echo $this->selected_input_array($debugOptions, 'mode', 'full', 'checked'); ?> />
				<em class="description"><?php _e('Because the service dump may contain a lot of information, do we include it in the logging fallback?  This does not affect the normal debug message.', $P)?>.</em>
			</div>
			<div class="field">
				<label for="dbg-sep">Separator</label>
				<input id="dbg-sep" type="text" class="text" name="<?php echo $P?>[debug][separator]" value="<?php echo esc_attr($debugOptions['separator'])?>" />
				<em class="description"><?php _e('Separator for multiple-mapped fields (i.e. if `fname` and `lname` are mapped to the `name` field, how to separate them)', $P)?>. <a title="<?php _e('Help: Read More') ?>" href="https://github.com/zaus/forms-3rdparty-integration#i-need-to-submit-multiple-values-as">(?)</a></em>
			</div>
		</div></fieldset>
		
		<div class="meta-box-sortables">
		<?php
		// make sure we have at least one
		if( empty($options) ){
			$options = array(array(
				'name'=>''
				, 'url'=>''
				, 'success'=>''
				, 'failure' => ''
				, 'forms' => array()
				, 'timeout' => self::DEFAULT_TIMEOUT
				, 'mapping' => array()
				));
		}

		$eid = -1; // always increment to correct for weirdness
		foreach($options as $ekey => $entity):
			$eid++;
		?>
		<div id="metabox-<?php echo $eid; ?>" class="meta-box">
		<div class="shortcode-description postbox">
			<h3 class="hndle"><span>3rd-Party Service: <?php echo esc_attr($entity['name'])?></span></h3>
			
			<div class="description-body inside">
			
			<fieldset class="postbox open"><legend class="hndle"><span>Service</span></legend>
				<div class="inside">
					<div class="field">
						<label for="name-<?php echo $eid?>">Service Name</label>
						<input id="name-<?php echo $eid?>" type="text" class="text" name="<?php echo $P?>[<?php echo $eid?>][name]" value="<?php echo esc_attr($entity['name'])?>" />
					</div>
			
					<div class="field">
						<label for="url-<?php echo $eid?>">Submission URL</label>
						<input id="url-<?php echo $eid?>" type="text" class="text" name="<?php echo $P?>[<?php echo $eid?>][url]" value="<?php echo esc_attr(empty($entity['url']) ? $debugUrl : $entity['url'])?>" />
						<em class="description"><?php echo sprintf(__('The url of the external submission -- usually the <code>action</code> attribute of the 3rd-party form.  See <a href="%s">Debug Mode</a> setting for a <a href="%s">working test url</a>.', $P), '#dbg-debugmode', $debugUrl);?></em>
					</div>
					
		
					<div class="field">
						<label for="forms-<?php echo $eid?>">Attach to Forms</label>
						<?php
							// print various forms
							$this->form_select_input($forms, $eid, isset($entity['forms']) ? $entity['forms'] : '');
						?>
						<em class="description"><?php _e('Choose which forms submit to this service', $P);?>.</em>
					</div>
					
					<div class="field">
						<label for="success-<?php echo $eid?>">Success Condition</label>
						<input id="success-<?php echo $eid?>" type="text" class="text" name="<?php echo $P?>[<?php echo $eid?>][success]" value="<?php echo esc_attr($entity['success'])?>" />
						<em class="description"><?php _e('Text to expect from the return-result indicating submission success', $P);?>.  <?php _e('Leave blank to ignore', $P);?>.</em>
						<em class="description"><?php _e('Note - you can use more complex processing in the hook, rendering this irrelevant', $P);?>.</em>
					</div>
					<div class="field">
						<label for="failure-<?php echo $eid?>">Failure Message (Mask)</label>
						<textarea id="failure-<?php echo $eid?>" type="text" class="text" name="<?php echo $P?>[<?php echo $eid?>][failure]"><?php echo esc_attr($entity['failure'])?></textarea>
						<em class="description"><?php _e('Notification text to append to form confirmation (i.e. what\'s shown on-screen) when <strong>service</strong> fails', $P); ?>.</em>
						<em class="description"><?php _e('This message may contain a placeholder for the autogenerated safe message (<code>%2$s</code>) and/or the original plugin message (<code>%1$s</code>)', $P);?>.  <?php _e('Leave blank to ignore', $P);?>.</em>
						<em class="description"><?php _e('Note - mask is formatted using <code>sprintf($mask, $original_message, $nice_message)</code>', $P);?>.</em>
					</div>
					<div class="field">
						<label for="timeout-<?php echo $eid?>">Request timeout</label>
						<input id="timout-<?php echo $eid?>" type="text" class="text" name="<?php echo $P?>[<?php echo $eid?>][timeout]" value="<?php echo esc_attr($entity['timeout'])?>" />
						<em class="description"><?php echo sprintf(__('How long (in seconds) to attempt the 3rd-party remote request before giving up.  Default %d', $P), self::DEFAULT_TIMEOUT);?>.</em>
					</div>
				</div>
			</fieldset><!-- Service -->

			<?php
			do_action($this->N('service_settings'), $eid, $P, $entity);
			?>

			<fieldset class="postbox open"><legend class="hndle"><span>Mapping</span></legend>
				<table class="mappings inside">
				<caption><?php _e('Listing of Form(s) Plugin fields to 3rd-party Mappings.  <br />
				* Note that the label is just for you to remind yourself what the mapping is for, and does not do anything.  <br />
				Also, if you accidentally delete all of the mapping fields, try deleting the Service entry and refreshing the page, then adding another Service.', $P);?></caption>
				<thead>
					<tr>
						<th id="th-<?php echo $eid?>-static" class="thin" title="<?php _e('If checked, will use the \'Form Submission Field\' column as the submission value, rather than the user input', $P);?>"><?php _e('Is Value?', $P);?></th>
						<th id="th-<?php echo $eid, '-', self::PARAM_LBL ?>">
							<strong><?php _e('Label', $P);?>*</strong>
							<p class="descr"><?php _e('Administrative text -- just describes this row', $P);?></p>
						</th>
						<th id="th-<?php echo $eid, '-', self::PARAM_SRC ?>">
							<strong><?php _e('Form Submission Field', $P);?></strong>
							<p class="descr"><?php _e('The input name/id from the form plugin (CF7/GF),<br /> or the value to submit if \'Is Value\' is checked', $P);?></p>
						</th>
						<th id="th-<?php echo $eid, '-', self::PARAM_3RD ?>">
							<strong><?php _e('3rd-Party Field', $P);?></strong>
							<p class="descr"><?php _e('The input name/id from the external service', $P);?></p>
						</th>
						<th id="th-<?php echo $eid?>-action" class="thin"><?php _e('Drag', $P);?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					//only print the 'add another' button for the last one
					$numPairs = count($entity['mapping']);

					// make sure we have at least one
					if( $numPairs == 0 ) {
						$entity['mapping'] = array(array(
							'val'=>''
							, self::PARAM_LBL => ''
							, self::PARAM_SRC => ''
							, self::PARAM_3RD => ''
							));
					}

					$pairNum = 0;	//always increments correctly?
					foreach($entity['mapping'] as $k => $pair):
					?>
					<tr class="tr-values fields sortable<?php if($pairNum%2 == 1) echo ' alt'; ?>">
						<td headers="th-<?php echo $eid?>-static" class="drag-handle">
							<label for="mapping-<?php echo $eid?>-<?php echo $pairNum?>c" class="invisible">Is Value?</label>
							<input id="mapping-<?php echo $eid?>-<?php echo $pairNum?>c" type="checkbox" class="checkbox c" name="<?php echo $P?>[<?php echo $eid?>][mapping][<?php echo $pairNum?>][val]" value="1"<?php if(v($pair['val'])) echo ' checked="checked"'; ?> />
						</td>
						<td headers="th-<?php echo $eid, '-', self::PARAM_SRC ?>">
							<label for="mapping-<?php echo $eid?>-<?php echo $pairNum?>d" class="invisible">Label:</label>
							<strong><input id="mapping-<?php echo $eid?>-<?php echo $pairNum?>d" type="text" class="text d" name="<?php echo $P?>[<?php echo $eid?>][mapping][<?php echo $pairNum?>][<?php echo self::PARAM_LBL ?>]" value="<?php echo esc_attr($pair[self::PARAM_LBL])?>" /></strong>
						</td>
						<td headers="th-<?php echo $eid, '-', self::PARAM_SRC ?>">
							<label for="mapping-<?php echo $eid?>-<?php echo $pairNum?>a" class="invisible">Form Submission Field:</label>
							<input id="mapping-<?php echo $eid?>-<?php echo $pairNum?>a" type="text" class="text a" name="<?php echo $P?>[<?php echo $eid?>][mapping][<?php echo $pairNum?>][<?php echo self::PARAM_SRC ?>]" value="<?php echo esc_attr($pair[self::PARAM_SRC])?>" />
						</td>
						<td headers="th-<?php echo $eid?>-3rd">
							<label for="mapping-<?php echo $eid?>-<?php echo $pairNum?>b" class="invisible">3rd-party Field:</label>
							<input id="mapping-<?php echo $eid?>-<?php echo $pairNum?>b" type="text" class="text b" name="<?php echo $P?>[<?php echo $eid?>][mapping][<?php echo $pairNum?>][<?php echo self::PARAM_3RD ?>]" value="<?php echo esc_attr($pair[self::PARAM_3RD])?>" />
						</td>
						<td headers="th-<?php echo $eid?>-action" class="thin drag-handle icon row-actns">
							<a href="#" title="<?php _e('Delete'); ?>" class="minus actn" data-actn="remove" data-after="row" data-rel="tr.fields"><?php _e('Delete', $P);?></a>
							<?php
							$pairNum++;
							#if( $pairNum == $numPairs):
								?>
								<a href="#" title="<?php _e('Add Another'); ?>" class="plus actn" data-actn="clone" data-after="row" data-rel="tr.fields"><?php _e('Add Another', $P);?></a>
								<?php
							#endif;	//numPairs countdown
							?>
						</td>
					</tr>
					<?php
					endforeach;	//loop $entity[mapping] pairs
					?>
				</tbody>
				</table>
			</fieldset><!-- Mappings -->
			
			<section class="info example hook-example">
			<fieldset class="postbox"><legend class="hndle"><code>Hooks</code></legend>
				<div class="inside">
					<div class="description">
						<p>The following are examples of action callbacks and content filters you can use to customize this service.</p>
						<p>Add them to your <code>functions.php</code> or another plugin.</p>
					</div>
					<div>
						<label for="hook-ex-<?php echo $eid; ?>">WP Action Callback:</strong>
						<input style="width:500px;" name="hook-ex[<?php echo $eid; ?>]" id="hook-ex-<?php echo $eid; ?>" class="code example" value="<?php echo esc_attr("add_action('{$P}_service_a{$eid}', array(&\$this, 'YOUR_CALLBACK'), 10, 2);"); ?>" readonly="readonly" />
						<em class="description">used for post-processing on the callback results</em>
					</div>
					<div>
						<label for="hook-exf-<?php echo $eid; ?>">WP Input Filter:</strong>
						<input style="width:500px;" name="hook-exf[<?php echo $eid; ?>]" id="hook-exf-<?php echo $eid; ?>" class="code example" value="<?php echo esc_attr("add_filter('{$P}_service_filter_post_{$eid}', array(&\$this, 'YOUR_FILTER'), 10, 4);"); ?>" readonly="readonly" />
						<em class="description">used to alter static inputs (the Form plugin field)</em>
					</div>
				</div>
			</fieldset><!-- Hooks -->
			</section>

			<span class="button"><a href="#" class="actn" data-actn="remove" data-after="metabox" data-rel="div.meta-box">Delete Service</a></span>
			<span class="button"><a href="#" class="actn" data-actn="clone" data-after="metabox" data-rel="div.meta-box">Add Another Service</a></span>

			
			</div><?php /*-- end div.description-body inside  --*/ ?>
			
		</div><!-- .postbox -->
		</div><!-- .meta-box -->
		<?php
		endforeach;	//loop through option groups

		do_action($this->N('service_metabox'), $P, $options);
		?>

		</div><!-- .meta-box-sortables -->

			<div class="buttons">
				<input type="submit" id="submit" name="submit" class="button button-primary" value="Save" />
			</div>
				
		</form>

		<?php
		do_action($this->N('service_metabox_after'), $P, $options);
		?>


		<div class="last-box">
			<div class="postbox" data-icon="?">
				<h3 class="hndle"><span>Examples of callback hooks.</span></h3>
				<div class="description-body inside">

			<section class="info callback">
				<p>You can also see examples in the plugin folder <code>3rd-Parties</code>.</p>
				<h4>Action</h4>
				<pre>
/**
 * Callback hook for 3rd-party service XYZ
 * @param $response the remote-request response (in this case, it's a serialized string)
 * @param $results the callback return results (passed by reference since function can't return a value; also must be "constructed by reference"; see plugin)
 */
public function service1_action_callback($response, $results){
	try {
		// do something with $response
		
		// set return results - text to attach to the end of the email
		$results['attach'] = $output;
		
		///add_filter('wpcf7_mail_components', (&$this, 'filter_'.__FUNCTION__));
	} catch(Exception $ex){
		// indicate failure by adding errors as an array
		$results['errors'] = array($ex->getMessage());
	}
}//--	function service1_action_callback
				</pre>
				
				<h4>Filter</h4>
				<pre>
/**
 * Apply filters to integration fields
 * so that you could say "current_visitor={IP}" and dynamically retrieve the visitor IP
 * @see http://codex.wordpress.org/Function_Reference/add_filter
 * 
 * @param $values array of post values
 * @param $service reference to service detail array
 * @param $cf7 reference to Contact Form 7 object
 */
public function service1_filter_callback($values, $service, $cf7){
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
				break;
		}
	endforeach;
	
	return $values;
}//--	function multitouch1_filter
				</pre>
			</section>
			
				</div><!-- .inside -->
			</div><!-- .postbox -->
		</div><!-- .meta-box -->
		
		<!-- 
		<div class="meta-box postbox" id="emptybox">
			<h3 class="hndle"><span>Empty Section</span></h3>
			<h4>Shortcode = <code>abt_featured_slider</code></h4>
			<div class="inside">
				<p>stuff inside</p>
				<br class="clear">
			</div>
		</div>
		 -->
		
		</div><!-- //#post-stuff --></div><!--  //div#plugin.wrap -->