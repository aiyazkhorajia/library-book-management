<?php
namespace libspace;
use WP_Query;
class LibraryManagement {

    private $settings = null;
	private $plugin_name;
	private $plugin_version;
	
    public function __construct() {

		$this->plugin_name = "Library Book Management";
		$this->plugin_version = "1.0.0";
        $this->settings = unserialize(get_option('lbm-field-settings'));
		
		// enqueue js and css 
        add_action('admin_enqueue_scripts', array($this, 'lbm_enqueue_admin_scripts'));
		add_action('wp_enqueue_scripts', array($this, 'lbm_enqueue_styles'));
		add_action('wp_enqueue_scripts', array($this, 'lbm_enqueue_scripts'));
		
		// Admin menu
 		add_action('admin_menu', array($this, 'lbm_register_page'));
		
		// register post type and taxonomies
		add_action('init', array($this, 'lbm_books_post_type'));
		add_action('init', array($this, 'lbm_book_publisher_taxonomy'));
		add_action( 'add_meta_boxes', array($this, 'lbm_extra_add_meta_box' ));
		add_action( 'save_post', array($this, 'lbm_extra_save' ));
		
		// ajax
		add_action( 'wp_ajax_lbm_generate_taxonomy_list', array($this, 'lbm_generate_taxonomy_list'));
		add_action( 'wp_ajax_lbm_generate_terms_list', array($this, 'lbm_generate_terms_list'));
		add_action( 'wp_ajax_lbm_load_posts', array($this, 'lbm_load_posts'));
		add_action( 'wp_ajax_nopriv_lbm_load_posts', array($this, 'lbm_load_posts'));
		
		//shortcode
		add_shortcode( 'library-book-management',  array($this,'lbm_generate_shortcode'));
    }
	
	/**
     * Register the Stylesheets for the public-facing side of the site.
	 *
	 */
	public function lbm_enqueue_styles() {
		wp_enqueue_style( 'library-admin-css', plugin_dir_url( __FILE__ ) . '../css/lbm-public.css', array(), $this->plugin_version, 'all' );
		wp_enqueue_style( 'lbm-jquery-ui-css', plugin_dir_url( __FILE__ ) . '../css/jquery-ui.css', array(), $this->plugin_version, 'all' );
	}

	/**
     * Register the JavaScript for the public-facing side of the site.
	 *
	 */
	public function lbm_enqueue_scripts() {
		wp_enqueue_script( 'library-public', plugin_dir_url( __FILE__ ) . '../js/lbm-public.js', array( 'jquery' ), $this->plugin_version, false );
		wp_enqueue_script( 'lbm-jquery-ui-js', plugin_dir_url( __FILE__ ) . '../js/jquery-ui.js', '', '', false );
		wp_localize_script( 'library-public', 'libfront', array(
			 'ajax_url'               => admin_url( 'admin-ajax.php' ), 
			 'security'             => wp_create_nonce("ajax_nonce"),
		));
	}	
	
	/**
     * Register the JavaScript for the admin side of the site.
	 *
	 */
	public function lbm_enqueue_admin_scripts() {
		wp_enqueue_script( 'library-admin-js', plugin_dir_url( __FILE__ ) . 'js/lbm-admin.js', array( 'jquery' ), $this->plugin_version, false );
		wp_localize_script( 'library-admin-js', 'libadmin', array(
			 'ajax_url'               => admin_url( 'admin-ajax.php' ), 
			 'security'             => wp_create_nonce("ajax_nonce"),
		));
	}

	/**
     * get taxonomies for ajax request and on function call
	 *
	 */
	public function lbm_generate_taxonomy_list(){
		
		$settings = unserialize(get_option('lbm-field-settings')); // retrive selected taxonomy 
		
		if(!empty($_REQUEST['post_type'])){
			check_ajax_referer( 'ajax_nonce', 'security' );
			$taxonomy_objects = get_object_taxonomies( $_REQUEST['post_type'], 'objects' );
			echo json_encode( $taxonomy_objects);
			die();
		}
		else if(!empty($settings['post-types'])){
			$taxonomy_objects = get_object_taxonomies( $settings['post-types'], 'objects' );
			return $taxonomy_objects;
		}
		return;
	}

	/**
     * get taxonomy terms for ajax request and on function call
	 *
	 */
	public function lbm_generate_terms_list(){
		
		$settings = unserialize(get_option('lbm-field-settings')); // retrive selected term
		
		if(!empty($_REQUEST['taxonomy'])){
			check_ajax_referer( 'ajax_nonce', 'security' );
			$terms = get_terms( $_REQUEST['taxonomy'], array(
				'hide_empty' => false,
			) );
			echo json_encode( $terms);
			die();
		}
		else if(!empty($settings['post-taxonomies'])){
			$terms = get_terms( $settings['post-taxonomies'], array(
				'hide_empty' => false,
			) );
			return $terms;
		}
		return;
	}
	
	/**
     * Admin menu page
	 *
	 */
    public function lbm_register_page() {
        add_menu_page('Library Manager', 'Manage Library', 'read', 'eapm-export-posts', array($this, 'lbm_render_settings_page'));
    }

	/**
     * Admin menu page callback function
	 *
	 */
    public function lbm_render_settings_page() {

		echo _e('<p>Select which post types to be displayed on front:</p>');
		
		$exclude = array('attachment', 'revision', 'page');
        $post_types = get_post_types(array(
            'public' => true,

        ));
        $settings = null;

        if (!empty($_POST)) {
			update_option('lbm-field-settings', serialize($_POST));
        }

        $settings = unserialize(get_option('lbm-field-settings'));

        echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
        echo '<select name="post-types" id="post-types">';
		echo '<option value="">Select Post type</option>';
        foreach ($post_types as $key=>$val) {
			if (!in_array($key, $exclude)) {
				echo '<option value="'.$key.'"'. ( $settings['post-types'] && $settings['post-types'] == $key ? ' selected' : '').'>'.$val.'</option>';
			}
		}
        echo '</select>';
		
		$taxonomies = $this->lbm_generate_taxonomy_list();
		echo '<select name="post-taxonomies" id="post-taxonomies">';
		echo '<option value="">Select Taxonomy</option>';
		if(!empty($taxonomies)){
			foreach ($taxonomies as $taxonomy) {
				echo '<option value="'.$taxonomy->name.'"'. ( $settings['post-taxonomies'] && $settings['post-taxonomies'] == $taxonomy->name ? ' selected' : '').'>'.$taxonomy->label.'</option>';
			}
		}
		echo '</select>';
		$terms = $this->lbm_generate_terms_list();
		echo '<select name="taxonomy-terms" id="taxonomy-terms">';
		echo '<option value="">Select Term</option>';
		
		if(!empty($terms)){
			foreach ($terms as $term) {
				echo '<option value="'.$term->term_id.'"'. ( $settings['taxonomy-terms'] && $settings['taxonomy-terms'] == $term->id ? ' selected' : '').'>'.$term->name.'</option>';
  
			}
		}
		echo '</select>';
        echo '<p><input type="submit" value="Save Settings" class="button"><span class="description">Save & Use bellow shortcode for front view.</span></p>';
        echo '</form>';
		
		if(!empty($settings)){
				echo '<table class="widefat"><thead>';
				echo '<tr>';
				echo '<td>Shortcode</td>';
				echo '</tr></thead>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[library-book-management]</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
		}
	}
	
	
	/**
     * Shortcode Callback function
	 *
	 */
	public function lbm_generate_shortcode( $atts ) {
		
		$settings = unserialize(get_option('lbm-field-settings'));
		$post_type =   isset($settings['post-types']) ? $settings['post-types'] : 'post' ;
		$post_taxonomy =  isset($settings['post-taxonomies']) ? $settings['post-taxonomies'] : '' ;
		$post_terms =  isset($settings['taxonomy-terms']) ? $settings['taxonomy-terms'] : '' ;
		$tax_query = array();
		
		if( !empty($post_taxonomy) && !empty($post_terms) ){
			
			$tax_query = array(
				array(
					'taxonomy' => $post_taxonomy,
					'field'    => 'slug',
					'terms'    => $post_terms,
				));
		}
		// WP_Query arguments
		$args = array(
			'post_type'              => $post_type,
			'paged'              	 => true,
			'posts_per_page'         => '10',
			'order'                  => 'DESC',
			'orderby'                => 'date',
			'tax_query' 			 => $tax_query
		);
		$result_text = $this->lbm_load_posts( $args );
		$serch_form = $this->search_filter_form();
		$result = '<div class="search-container">'. $serch_form .'</div>';
		$result .= '<div class="posts-table-container">'. $result_text .'</div>';
		return $result;
	}
	
	/**
     * Search/filter form for front view
	 *
	 */
	public function search_filter_form(){
		$text  = '';
		$text  = '<form name="search_filter_form" class="form-tag">';
		$text .= '<input type="text" name="search_term" value=""/>';

			$taxonomy_objects = $this->lbm_generate_taxonomy_list();
			if(!empty($taxonomy_objects)){
				foreach($taxonomy_objects as $taxonomy){
					$all_terms = get_terms( $taxonomy->name, array(
					'orderby'    => 'count',
					'hide_empty' => 0,
					) );
					
					if(!empty($all_terms)){
						$text .= "Filter by ". $taxonomy->label .":";
						foreach($all_terms as $terms){
							$text .= ' <input type="checkbox" class="checkbox-filter" name="'.$taxonomy->name.'[]" value="'.$terms->term_id.'"/><label>'.$terms->name.'</lable>';
						}
					}
					$text .= '</br>';
				}
			}
		
		$settings = unserialize(get_option('lbm-field-settings'));
		$post_type =   isset($settings['post-types']) ? $settings['post-types'] : '' ;
		if( $post_type == 'books' )
		{
			$text .= '<input type="hidden" name="min-price" id="min-price" value=""/>';
			$text .= '<input type="hidden" name="max-price" id="max-price" value=""/>';
			$text .= '<input type="text" id="price" disabled style="border:0; color:#000; font-weight:bold;">';
			$text .= '<div id = "price-slider"></div>';
			$text .= '<label for="extra_rating">'. __( 'Rating: ' ) .'</label>';
			$text .= '<select name="rating" id="rating">';
			$text .= '<option value="" >ALL</option>';
			$text .= '<option value="1" >1</option>';
			$text .= '<option value="2" >2</option>';
			$text .= '<option value="3" >3</option>';
			$text .= '<option value="4" >4</option>';
			$text .= '<option value="5" >5</option>';
			$text .= '</select>';
		}
		$text .= '</br><button class="custom-search">Apply</button>';
		$text .= '</form>';
		return $text;
	}
	
	/**
     * Load posts function based on arguments or ajax request
	 *
	 */
	public function lbm_load_posts( $args = array()){

		$text = '';
	
		if(isset($_POST['form_data'])){
			
			$settings = unserialize(get_option('lbm-field-settings'));
			parse_str($_POST['form_data'], $formdata); 
			//print_r($settings);
			$tax_query = array();
			$post_type =   isset($settings['post-types']) ? $settings['post-types'] : '' ;
			$taxonomy_objects = $this->lbm_generate_taxonomy_list();
			$tax_array = array();
			// WP_Query arguments
			$args = array(
				'post_type'              => $post_type,
				'paged'              	 => true,
				'posts_per_page'         => '10',
				'order'                  => 'DESC',
				'orderby'                => 'date',
			);
			
			// Taxonomy filter
			if(!empty($taxonomy_objects)){
				foreach($taxonomy_objects as $taxonomy){
					if(!empty($formdata[$taxonomy->name])){
						$tax_array[] = array(
							'taxonomy' => $taxonomy->name,
							'field'    => 'term_id',
							'terms'    => $formdata[$taxonomy->name],
						);
					}
				}
				if(!empty($tax_array)){
					$tax_query = array(
						'relation' => 'AND',
						$tax_array,
					);
					$args['tax_query'] = $tax_query;
				}
			}

			// Search text filter
			if(!empty($formdata['search_term'])){
				$args['s'] = $formdata['search_term'];
			}
			
			// Price range filter for book post type
			if( !empty($formdata['min-price']) && !empty($formdata['max-price']) && $post_type == 'books' ){
				$args['meta_query'] = array(
						array(
							'key' => 'extra_price',
							'value'   => array( $formdata['min-price'], $formdata['max-price'] ),
							'type'    => 'numeric',
							'compare' => 'BETWEEN',
						),
				);
			}
			
			// Rating filter for book post type
			if( !empty($formdata['rating']) && $post_type == 'books' ){
				$args['meta_query'][] = array(
							'key' => 'extra_rating',
							'value'   => $formdata['rating'],
							'type'    => 'numeric',
							'compare' => '=',
						);
			}
		}

		// The Query
		$query = new WP_Query( $args );

		// The Loop
		if ( $query->have_posts() ) {
			$text = '<table class="minimalistBlack"><thead><tr>';
			$text .= '<th>title</th>';
			$text .= '<th>description</th>';
			$text .= '<th>author</th>';
			$text .= '<th>rating</th>';
			$text .= '<th>price</th>';
			$text .= '</thead><tbody>';
			while ( $query->have_posts() ) {
				$query->the_post();
				$text .= '<tr>';
				$text .= '<td>'. get_the_title() .'</td>';
				$text .= '<td>'. get_the_content() .'</td>';
				$text .= '<td>cell4_5</td>';
				$text .= '<td>'. $this->extra_get_meta( 'extra_rating' ) .'</td>';
				$text .= '<td>'. $this->extra_get_meta( 'extra_price' ) .'</td>';
				$text .= '</tr>';
			}
			$text .= '</tbody></table>';
		} else {
			$text .= '<p> No posts found </p>';
		}
		
		// Restore original Post Data
		wp_reset_postdata();

		if( isset($_POST['form_data']))  // for ajax call
		{
			echo $text;
			wp_die();
		}
		else{
			return $text;
		}
	}

 	
	// Register Custom Post Type
	public function lbm_books_post_type() {

		$labels = array(
			'name'                  => _x( 'Books', 'Post Type General Name', 'library-manager-locale' ),
			'singular_name'         => _x( 'Book', 'Post Type Singular Name', 'library-manager-locale' ),
			'menu_name'             => __( 'Books', 'library-manager-locale' ),
			'name_admin_bar'        => __( 'Book', 'library-manager-locale' ),
			'archives'              => __( 'Item Archives', 'library-manager-locale' ),
			'attributes'            => __( 'Item Attributes', 'library-manager-locale' ),
			'parent_item_colon'     => __( 'Parent Item:', 'library-manager-locale' ),
			'all_items'             => __( 'All Items', 'library-manager-locale' ),
			'add_new_item'          => __( 'Add New Item', 'library-manager-locale' ),
			'add_new'               => __( 'Add New', 'library-manager-locale' ),
			'new_item'              => __( 'New Item', 'library-manager-locale' ),
			'edit_item'             => __( 'Edit Item', 'library-manager-locale' ),
			'update_item'           => __( 'Update Item', 'library-manager-locale' ),
			'view_item'             => __( 'View Item', 'library-manager-locale' ),
			'view_items'            => __( 'View Items', 'library-manager-locale' ),
			'search_items'          => __( 'Search Item', 'library-manager-locale' ),
			'not_found'             => __( 'Not found', 'library-manager-locale' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'library-manager-locale' ),
			'featured_image'        => __( 'Featured Image', 'library-manager-locale' ),
			'set_featured_image'    => __( 'Set featured image', 'library-manager-locale' ),
			'remove_featured_image' => __( 'Remove featured image', 'library-manager-locale' ),
			'use_featured_image'    => __( 'Use as featured image', 'library-manager-locale' ),
			'insert_into_item'      => __( 'Insert into item', 'library-manager-locale' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'library-manager-locale' ),
			'items_list'            => __( 'Items list', 'library-manager-locale' ),
			'items_list_navigation' => __( 'Items list navigation', 'library-manager-locale' ),
			'filter_items_list'     => __( 'Filter items list', 'library-manager-locale' ),
		);
		$args = array(
			'label'                 => __( 'Book', 'library-manager-locale' ),
			'description'           => __( 'Site books.', 'library-manager-locale' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail' ),
			'taxonomies'            => array( 'publisher' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,		
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'page',
		);
		register_post_type( 'books', $args );
	}
	// Register Custom Taxonomy
	public function lbm_book_publisher_taxonomy() {

		$labels = array(
			'name'                       => _x( 'Publishers', 'Taxonomy General Name', 'library-manager-locale' ),
			'singular_name'              => _x( 'Publisher', 'Taxonomy Singular Name', 'library-manager-locale' ),
			'menu_name'                  => __( 'Publisher', 'library-manager-locale' ),
			'all_items'                  => __( 'All Publishers', 'library-manager-locale' ),
			'parent_item'                => __( 'Parent Publisher', 'library-manager-locale' ),
			'parent_item_colon'          => __( 'Parent Publisher:', 'library-manager-locale' ),
			'new_item_name'              => __( 'New Publisher Name', 'library-manager-locale' ),
			'add_new_item'               => __( 'Add New Publisher', 'library-manager-locale' ),
			'edit_item'                  => __( 'Edit Publisher', 'library-manager-locale' ),
			'update_item'                => __( 'Update Publisher', 'library-manager-locale' ),
			'view_item'                  => __( 'View Item', 'library-manager-locale' ),
			'separate_items_with_commas' => __( 'Separate publishers with commas', 'library-manager-locale' ),
			'add_or_remove_items'        => __( 'Add or remove publishers', 'library-manager-locale' ),
			'choose_from_most_used'      => __( 'Choose from the most used publishers', 'library-manager-locale' ),
			'popular_items'              => __( 'Popular Items', 'library-manager-locale' ),
			'search_items'               => __( 'Search Publishers', 'library-manager-locale' ),
			'not_found'                  => __( 'Not Found', 'library-manager-locale' ),
			'no_terms'                   => __( 'No items', 'library-manager-locale' ),
			'items_list'                 => __( 'Items list', 'library-manager-locale' ),
			'items_list_navigation'      => __( 'Items list navigation', 'library-manager-locale' ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		register_taxonomy( 'publisher', array( 'books' ), $args );
		
				$labels = array(
			'name'                       => _x( 'Authors', 'Taxonomy General Name', 'library-manager-locale' ),
			'singular_name'              => _x( 'Author', 'Taxonomy Singular Name', 'library-manager-locale' ),
			'menu_name'                  => __( 'Author', 'library-manager-locale' ),
			'all_items'                  => __( 'All Authors', 'library-manager-locale' ),
			'parent_item'                => __( 'Parent Author', 'library-manager-locale' ),
			'parent_item_colon'          => __( 'Parent Author:', 'library-manager-locale' ),
			'new_item_name'              => __( 'New Author Name', 'library-manager-locale' ),
			'add_new_item'               => __( 'Add New Author', 'library-manager-locale' ),
			'edit_item'                  => __( 'Edit Author', 'library-manager-locale' ),
			'update_item'                => __( 'Update Author', 'library-manager-locale' ),
			'view_item'                  => __( 'View Item', 'library-manager-locale' ),
			'separate_items_with_commas' => __( 'Separate authors with commas', 'library-manager-locale' ),
			'add_or_remove_items'        => __( 'Add or remove authors', 'library-manager-locale' ),
			'choose_from_most_used'      => __( 'Choose from the most used authors', 'library-manager-locale' ),
			'popular_items'              => __( 'Popular Items', 'library-manager-locale' ),
			'search_items'               => __( 'Search Authors', 'library-manager-locale' ),
			'not_found'                  => __( 'Not Found', 'library-manager-locale' ),
			'no_terms'                   => __( 'No items', 'library-manager-locale' ),
			'items_list'                 => __( 'Items list', 'library-manager-locale' ),
			'items_list_navigation'      => __( 'Items list navigation', 'library-manager-locale' ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		register_taxonomy( 'author', array( 'books' ), $args );

	}
	
	function lbook_get_meta_box( $meta_boxes ) {
		$prefix = '';

		$meta_boxes[] = array(
			'id' => 'lbooks',
			'title' => esc_html__( 'library Metabox', 'library-manager-locale' ),
			'post_types' => array( 'books' ),
			'context' => 'advanced',
			'priority' => 'default',
			'autosave' => false,
			'fields' => array(
				array(
					'id' => $prefix . 'rating_1',
					'type' => 'rating',
					'name' => esc_html__( 'rating', 'library-manager-locale' ),
				),
				array(
					'id' => $prefix . 'slider_2',
					'type' => 'slider',
					'name' => esc_html__( 'Price', 'library-manager-locale' ),
					'js_options' => array(),
				),
			),
		);

		return $meta_boxes;
	}

	public function extra_get_meta( $value ) {
		global $post;

		$field = get_post_meta( $post->ID, $value, true );
		if ( ! empty( $field ) ) {
			return is_array( $field ) ? stripslashes_deep( $field ) : stripslashes( wp_kses_decode_entities( $field ) );
		} else {
			return false;
		}
	}

	public function lbm_extra_add_meta_box() {
		add_meta_box(
			'extra-extra',
			__( 'Extra', 'extra' ),
			array($this, 'extra_html'),
			'books',
			'normal',
			'default'
		);
	}
	public function extra_html( $post) {
		wp_nonce_field( '_extra_nonce', 'extra_nonce' ); ?>

		<p>
			<label for="extra_rating"><?php _e( 'Rating', 'extra' ); ?></label><br>
			<select name="extra_rating" id="extra_rating">
				<option <?php echo ($this->extra_get_meta( 'extra_rating' ) === '1' ) ? 'selected' : '' ?>>1</option>
				<option <?php echo ($this->extra_get_meta( 'extra_rating' ) === '2' ) ? 'selected' : '' ?>>2</option>
				<option <?php echo ($this->extra_get_meta( 'extra_rating' ) === '3' ) ? 'selected' : '' ?>>3</option>
				<option <?php echo ($this->extra_get_meta( 'extra_rating' ) === '4' ) ? 'selected' : '' ?>>4</option>
				<option <?php echo ($this->extra_get_meta( 'extra_rating' ) === '5' ) ? 'selected' : '' ?>>5</option>
			</select>
		</p>	<p>
			<label for="extra_price"><?php _e( 'price', 'extra' ); ?></label><br>
			<input type="text" name="extra_price" id="extra_price" value="<?php echo $this->extra_get_meta( 'extra_price' ); ?>">
		</p><?php
	}

	function lbm_extra_save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! isset( $_POST['extra_nonce'] ) || ! wp_verify_nonce( $_POST['extra_nonce'], '_extra_nonce' ) ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		if ( isset( $_POST['extra_rating'] ) )
			update_post_meta( $post_id, 'extra_rating', esc_attr( $_POST['extra_rating'] ) );
		if ( isset( $_POST['extra_price'] ) )
			update_post_meta( $post_id, 'extra_price', esc_attr( $_POST['extra_price'] ) );
	}
	/*
		Usage: extra_get_meta( 'extra_rating' )
		Usage: extra_get_meta( 'extra_price' )
	*/

}