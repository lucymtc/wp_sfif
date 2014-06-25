<?php
/** 
 * @package    SFIF
 * @author     Lucy Tomás
 * @since 	   1.0
 */
 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'Sfif_Core' )){
 
class Sfif_Core {

	/**
	 * vars
	 */
	 
	 public static $options = null;
	 
	/**
	 * contructor 
	 * @since 1.0
	 */
	
	private function __construct() {}
	
	/**
	 * init
	 * @since 1.0
	 */
	 
	 public static function init() {
	 	
		self::get_options();
		
	 }
	
	/**
	 * register_plugin_settings
	 * @since 1.0
	 */
	 
	 public static function register_plugin_settings() {
	 		
	 	register_setting('sfif_settings_group', 'sfif_settings');
		
	 }
	 
	/**
	 * set_settings_link_page
	 * @since 1.0
	 */
	 
	 public static function add_admin_menu_links() {
	 	
		$page = add_management_page( 'First images as featured', 'First images as featured', 'manage_options', 'sfif-options', array('Sfif_Core', 'options_page') );
		add_action('load-'. $page, array('Sfif_Core', 'enqueue_scripts'));	
	 	
	 }
	 
	 
	/**
	 * settings_page
	 * @since 1.0
	 */
	
	public static function options_page() {
		
		global $wpdb;
		
		$options = self::get_options();
		
		$query = "SELECT `" . $wpdb->posts . "`.`post_date` 
							FROM `" . $wpdb->posts . "` 
							WHERE `" . $wpdb->posts . "`.`post_type` <> 'attachment' 
							AND `" . $wpdb->posts . "`.`post_type` <> 'revision' 
							AND `" . $wpdb->posts . "`.`post_type` <> 'nav_menu_item' 
							AND `" . $wpdb->posts . "`.`post_status` = 'publish' 
							GROUP BY YEAR(`" . $wpdb->posts . "`.`post_date`), MONTH(`" . $wpdb->posts . "`.`post_date`);";
							
		$available_dates = $wpdb->get_results($query);
		
		require_once( SFIF_PLUGIN_DIR . 'includes/views/admin-options-page.php' );
	
	}
	
	
	/**
	 * enqueue_scripts
	 * @since 1.0
	 */
	 
	 public static function enqueue_scripts() {
	 	
			wp_enqueue_script('jquery');
			wp_enqueue_script( 'sfif-script', SFIF_PLUGIN_URL . 'includes/js/admin.js', array('jquery'), SFIF_PLUGIN_VERSION );

			wp_enqueue_style('sfif-admin-style', SFIF_PLUGIN_URL . 'includes/css/admin.css', array(), SFIF_PLUGIN_VERSION);
			
	 }
	 
	 /**
	  * get_options
	  * @since 1.0
	  */
	  
	 public static function get_options() {
	 		
	 	if( self::$options == null ) {
				
			// no settings saved at this point. Future release will save some settings
			//$options = get_option( 'sfif_settings' );
			
			//if( empty($options) ){
				$options = SFIF::instance()->default_options;
			//}
		
			 self::$options = $options;
		}
		
		return self::$options;
		
	 }

	/**
	 * search_and_update
	 * @since 1.0
	 */
	 
	 public static function search_and_update() {
	 	
		global $wpdb;	
			
	 	$response = new stdClass();
		
		//** check permissions
		
		if( !current_user_can( 'manage_options' ) ){
				
			$response->success = false;
			$response->alert = __('Insufficient privileges!', 'sfif_domain');
			echo json_encode($response);
			die();
		}
		
		check_admin_referer( 'update_featured', 'token' );
		
		//////**
		
		$sanitize  	 = self::sanitize_posted_data( $_POST );
		
		if( $sanitize->success == false ) {
				
			$response->success = false;
			$response->alert = __('Error: Please check the selected dates.', 'sfif_domain');
			echo json_encode($response);
			die();
		}
		
		$data = $sanitize->data;
		$total_count = wp_count_posts( $data['post_type'] )->publish;
		
		if( $data['limit'] > $total_count && $data['first_request'] == 0 ) {
				
			$response->success = true;
			$response->continue_request = false;
			echo json_encode($response);
			die();
		} 
		
		
		$items 	= self::get_posts( $data );
		$result = array();
		
		// for each item
		foreach ( $items as $item ) {
			
			$args = array('post_parent' => $item->ID,
            		  'post_type' => 'attachment',
            		  'numberposts' => 1,
            		  'post_mime_type' => 'image');
			
			// get the first image
			$attachment = get_children( $args );
			
			$date = new DateTime($item->post_date);
			
			$result[$item->ID]['title'] = $item->post_title;
			$result[$item->ID]['date'] = $date->format('Y-m');	
			
			//update the postmeta with the thumbnail value
			 if( !empty($attachment) ) {
			 	
				foreach ($attachment as $key => $image ) {
						
					$_meta_success = update_post_meta($item->ID, '_thumbnail_id', $key);
					
					$result[$item->ID]['image'] = $image->post_title;
					
					if( $_meta_success > 0 || $_meta_success == true ) {
						$result[$item->ID]['success'] = true;	
					} else {
						$result[$item->ID]['success'] = 'not_updated';
					}
					
				}// foreach attachment
				
			 } else {
			 	
				$result[$item->ID]['image'] = __('No image found', 'sfif_domain');
				$result[$item->ID]['success'] = false;
			 }
			 
		}// foreach items
		
		$response->success    = true;
		$response->result  	  = $result;
		$response->start   	  = $data['start'];
		$response->next_start = $data['next_start'];
		$response->next_limit = $data['next_limit'];
		
		$response->continue_request = true;
		
		echo json_encode($response);
		die();
	 	
	 }
	 
	/**
	 * validate_posted_data	 
	 * @since 1.0
	 */
	 
	 protected static function sanitize_posted_data( $data ) {
	 			
			$result = new stdClass();	
				
	 		if( isset($data['overwrite']) && $data['overwrite'] == 'checked') {
	 				
	 			$data['overwrite'] = true;
				
	 		} else{
	 			
	 			$data['overwrite'] = false;
	 		} 
	 		
			$data['post_type'] = sanitize_key($data['post_type']);
			$data['start'] 	   = absint($data['start']);
			$data['limit'] 	   = absint($data['limit']);
			
			$data['next_start'] = $data['limit'] + 1;
			$data['next_limit'] = $data['limit'] + 100;
			
			if( strtotime($data['post_date_from']) > strtotime($data['post_date_to']) ){
				
				$result->success = false;
				return $result;
			}
			
			$result->data 	 = $data;
			$result->success = true;
				
			return $result;
	 }
	 
	 /**
	  * get_posts
	  * @since 1.0
	  */
	  
	  protected static function get_posts( $data ) {
	  	
		global $wpdb;
		
		$where_statement = '';
		
		if( $data['post_date_from'] != '' && $data['post_date_to'] != '' ) {
				
			$where_statement .= " AND `". $wpdb->posts ."`.`post_date` BETWEEN '" . $data['post_date_from'] . "' AND '" . $data['post_date_to'] . "' ";
		}
		
		if( $data['overwrite'] === true ) {
			
			$query = "SELECT `". $wpdb->posts ."`.`ID`,
							 `". $wpdb->posts ."`.`post_title`,
							 `". $wpdb->posts ."`.`post_date`, 
								(SELECT `". $wpdb->postmeta ."`.`post_id`  
									FROM `". $wpdb->postmeta ."` 
									WHERE `". $wpdb->postmeta ."`.`meta_key` = '_thumbnail_id' 
									AND `". $wpdb->postmeta ."`.`post_id` = `". $wpdb->posts ."`.`ID`) AS `meta_post_thumbnail` 
					    FROM `". $wpdb->posts ."` 
						WHERE `". $wpdb->posts ."`.`post_type` = '" . $data['post_type'] . "' 
						AND `". $wpdb->posts ."`.`post_status` = 'publish' 
						" . $where_statement . "
						LIMIT " . $data['start'] . ", " . $data['limit'] . "";
						
		} else {
			
			$query = "SELECT `". $wpdb->posts ."`.`ID`,
							 `". $wpdb->posts ."`.`post_title`,
							 `". $wpdb->posts ."`.`post_date`, 
							 `meta_table`.`post_id` AS `meta_post_thumbnail` 
					  FROM `". $wpdb->posts ."` 
					  LEFT OUTER JOIN (SELECT `post_id` FROM `". $wpdb->postmeta ."` WHERE `meta_key` = '_thumbnail_id') AS `meta_table` 
						ON `". $wpdb->posts ."`.`ID` = `meta_table`.`post_id` 
					  WHERE `". $wpdb->posts ."`.`post_type` = '" . $data['post_type'] . "' 
					  AND `meta_table`.`post_id` IS NULL 
					  AND `". $wpdb->posts ."`.`post_status` = 'publish' 
					  " . $where_statement . "
					  LIMIT " . $data['start'] . ", " . $data['limit'] . "";
		}
		
		
		$results = $wpdb->get_results($query);
		return $results;
		
	  }
	  
	  /**
	   * add_action_links
	   * @since 1.1.1
	   */
	   
	   public static function add_action_links ( $links ) {
			
		 $links[] = '<a href="'. get_admin_url(null, 'tools.php?page=sfif-options') .'">' . __('Settings', 'sfif_domain') . '</a>';
   		 return $links;
		
		
		__('January', 'sfif_doamin');
		__('February', 'sfif_doamin');
		__('March', 'sfif_doamin');
		__('April', 'sfif_doamin');
		__('May', 'sfif_doamin');
		__('June', 'sfif_doamin');
		__('July', 'sfif_doamin');
		__('August', 'sfif_doamin');
		__('September', 'sfif_doamin');
		__('October', 'sfif_doamin');
		__('November', 'sfif_doamin');
		__('December', 'sfif_doamin');

			
	   }
	
	
}// class
}// if
	