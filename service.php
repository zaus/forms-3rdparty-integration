<?php

class Forms3rdpartyService {

	/**
	 * Namespace the given key
	 * @param string $key the key to namespace
	 * @return the namespaced key
	 */
	public function N($key = false) {
		// nothing provided, return namespace
		if( ! $key || empty($key) ) { return __CLASS__; }
		return sprintf('%s_%s', __CLASS__, $key);
	}


	const LABEL = '3rdparty Service';

	public function __construct() {
		// this class will be called WITHIN the init action, so don't hook
		//add_action( 'init', array(&$this, 'create_post_type') );
		$this->create_post_type();
	}

	public function create_post_type() {
		register_post_type( $this->N(),
			array(
				'description'				=> __( '3rdparty service configuration', 'f3p' ),
				'labels' => array(
					'name' => __( self::LABEL . 's', 'f3p' ),
					'singular_name' => __( self::LABEL, 'f3p' ),
					'archives'          	=> __( self::LABEL . ' Archives', 'f3p' ),
					'attributes'            => __( self::LABEL . ' Attributes', 'f3p' ),
					'parent_item_colon'     => __( sprintf('Parent %s:', self::LABEL), 'f3p' ),
					'all_items'             => __( sprintf('All %ss', self::LABEL), 'f3p' ),
					'add_new_item'          => __( sprintf('Add New %s', self::LABEL), 'f3p' ),
					'add_new'               => __( 'Add New', 'f3p' ),
					'new_item'              => __( sprintf('New %s', self::LABEL), 'f3p' ),
					'edit_item'             => __( sprintf('Edit %s', self::LABEL), 'f3p' ),
					'update_item'           => __( sprintf('Update %s', self::LABEL), 'f3p' ),
					'view_item'             => __( sprintf('View %s', self::LABEL), 'f3p' ),
					'view_items'            => __( sprintf('View %ss', self::LABEL), 'f3p' ),
					'search_items'          => __( sprintf('Search %s', self::LABEL), 'f3p' ),
					'not_found'             => __( 'Not found', 'f3p' ),
					'not_found_in_trash'    => __( 'Not found in Trash', 'f3p' ),
					'insert_into_item'      => __( 'Insert into item', 'f3p' ),
					'uploaded_to_this_item' => __( 'Uploaded to this item', 'f3p' ),
					'items_list'            => __( 'Items list', 'f3p' ),
					'items_list_navigation' => __( 'Items list navigation', 'f3p' ),
				),
				'hierarchical'          	=> false,
				'public'                	=> true,
				'show_ui'               	=> true,
				'show_in_menu'          	=> true,
				'menu_position'         	=> 100,
				'show_in_admin_bar'     	=> true,
				'show_in_nav_menus'     	=> false,
				'can_export'            	=> true,
				'has_archive'           	=> false,
				'exclude_from_search'   	=> true,
				'publicly_queryable'    	=> false,
				//'capability_type'       => $this->N(),
				//'menu_icon'				=> '',
				'supports' => array( 'title', 'excerpt', /*'custom-fields',*/ ),
				'register_meta_box_cb'	=> array(&$this, 'register_metaboxes'),
			)
		);
	}//--	fn create_post_type

	public function register_metaboxes($post) {
		$boxes = array(
			'mappings' => array('label' => 'Mappings', 'priority' => 'advanced'),
		);

		foreach($boxes as $b => $box) {
			add_meta_box($this->N($b), $box['label'], array(&$this, 'render_' . $b), $this->N(), $box['priority'], 'default', array('key' => $b, 'data' => get_post_meta($post->ID, $b, true)));
		}

	}//--	fn register_metaboxes


	#region -------- metaboxes ------------

	public function render_mappings($key, $data) {
		wp_nonce_field( __FUNCTION__, $this->N('nonce') );
		echo 'this is the stuff for ', json_encode($key, JSON_PRETTY_PRINT), '! ', json_encode($data, JSON_PRETTY_PRINT);
		?>
		i've got more stuff here...

		<textarea name="<?php echo $key; ?>"><?php echo json_encode($data, JSON_PRETTY_PRINT) ?></textarea>
		<?php
	}

	#endregion -------- metaboxes ------------

}//---	class Forms3rdpartyService

// engage!
new Forms3rdpartyService();