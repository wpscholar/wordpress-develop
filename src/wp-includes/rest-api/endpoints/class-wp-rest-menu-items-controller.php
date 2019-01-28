<?php
/**
 * REST API: WP_REST_Attachments_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 4.7.0
 */

/**
 * Core controller used to access attachments via the REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Posts_Controller
 */
class WP_REST_Menu_Items_Controller extends WP_REST_Posts_Controller {

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 4.7.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		$schema        = $this->get_item_schema();
		$get_item_args = array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
		register_rest_route(
			$this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the object.' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $get_item_args,
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Prepares a single attachment output for response.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post         $post    Attachment object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $post, $request ) {
		$response = parent::prepare_item_for_response( $post, $request );
		$data     = $response->get_data();

		$menu_item = wp_setup_nav_menu_item( $post );

		$data['id']               = (int) $menu_item->ID;
		$data['menu_item_parent'] = (int) $menu_item->menu_item_parent;

		if ( isset( $menu_item->object_id ) ) {
			$data['object_id'] = (int) $menu_item->object_id;
		}

		$data['title']       = $menu_item->title;
		$data['object']      = $menu_item->object;
		$data['target']      = $menu_item->target;
		$data['attr_title']  = $menu_item->attr_title;
		$data['description'] = $menu_item->description;
		$data['classes']     = $menu_item->classes;
		$data['xfn']         = $menu_item->xfn;

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $post ) );

		/**
		 * Filters an attachment returned from the REST API.
		 *
		 * Allows modification of the attachment right before it is returned.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Post          $post     The original attachment post.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'rest_prepare_nav_menu_item', $response, $post, $request );
	}

	/**
	 * Retrieves the attachment's schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 *
	 * @return array Item schema as an array.
	 */
	public function get_item_schema() {

		$schema = parent::get_item_schema();

		$schema['properties']['object'] = array(
			'description' => __( 'The type of object originally represented, such as "category," "post", or "attachment."' ),
			'type'        => 'string',
			'context'     => array( 'view' ),
		);

		$schema['properties']['object_id'] = array(
			'description' => __( 'The DB ID of the original object this menu item represents, e.g. ID for posts and term_id for categories.' ),
			'type'        => 'integer',
			'context'     => array( 'view' ),
		);

		$schema['properties']['menu_item_parent'] = array(
			'description' => __( 'The DB ID of the nav_menu_item that is this item\'s menu parent, if any. 0 otherwise.' ),
			'type'        => 'integer',
			'context'     => array( 'view' ),
		);

		$schema['properties']['menu_order'] = array(
			'description' => __( 'The order of the object in relation to other object of its type.' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'embed' ),
		);

		$schema['properties']['attr_title'] = array(
			'description' => __( 'Text for the title attribute of the link element for this menu item.' ),
			'type'        => 'string',
			'context'     => array( 'view', 'embed' ),
		);

		$schema['properties']['classes'] = array(
			'description' => __( 'Array of class attribute values for the link element of this menu item.' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
			'context'     => array( 'view', 'embed' ),
		);

		$schema['properties']['target'] = array(
			'description' => __( 'The target attribute of the link element for this menu item.' ),
			'type'        => 'string',
			'context'     => array( 'view', 'embed' ),
		);

		$schema['properties']['xfn'] = array(
			'description' => __( 'The XFN relationship expressed in the link of this menu item.' ),
			'type'        => 'string',
			'context'     => array( 'view', 'embed' ),
		);

		$schema['properties']['description'] = array(
			'description' => __( 'The description of this menu item.' ),
			'type'        => 'string',
			'context'     => array( 'view', 'embed' ),
		);

		$schema['properties']['link'] = array(
			'description' => __( 'The URL to which this menu item points.' ),
			'type'        => 'string',
			'context'     => array( 'view', 'embed' ),
		);

		unset( $schema['properties']['password'] );

		return $schema;
	}
}
