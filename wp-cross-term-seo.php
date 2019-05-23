<?php
/*
Plugin Name: Wordpress Cross Term SEO
Plugin URI: http://seo.voodoopress.net/plugin-description/
Description: With this plugin you can combine different terms, that have archive page in different combinations to get more SEO traffic for your site.
Version: 1.2
Author: Evgen "EvgenDob" Dobrzhanskiy
Author URI: http://voodoopress.net
Stable tag: 1.2
*/

//error_reporting(E_ALL);
//ini_set('display_errors', 'On');



if( !class_Exists('vooMainStart') ){
	class vooMainStart{
		var $locale;
		var $current_filtering_query;
		var $is_yoast_active;
		
		function __construct( $locale, $includes, $path ){
			$this->locale = $locale;
			$this->is_yoast_active = 0;
			
			// include required classes
			foreach( $includes as $single_path ){
				include( $path.$single_path );				
			}
			
			//check if yoast active
			$this->check_yoast_active();
			
			// init CPT
			$this->init_cpt();
			
			// init styles 
			$this->init_styles_scripts();
	 
			// initiate metabox
			add_action('init', array( $this, 'init_meta_box') );
			
			
			// Filter Query Action
			$this->filter_query();
			
			
			// add extra text output shortcode
			add_shortcode( 'extra_archive_text', array( $this, 'extra_archive_text' ) );
			
			// add extra text output shortcode
			add_shortcode( 'corss_terms_links', array( $this, 'corss_terms_links' ) );
			
			// add extra text output shortcode
			add_action('wp_head', array( $this, 'output_description' ) );
			
			//rewrite yoast title
			add_action('wpseo_opengraph_title', array( $this, 'rewrite_yoast_title' ) );
			add_action('wpseo_twitter_title', array( $this, 'rewrite_yoast_title' ) );
			
			//rewrite yoast descri[tion
			add_action('wpseo_metadesc', array( $this, 'rewrite_yoast_description' ) );
			add_action('wpseo_opengraph_desc', array( $this, 'rewrite_yoast_description' ) );
			add_action('wpseo_twitter_description', array( $this, 'rewrite_yoast_description' ) );
			
			//rewrite yoast descri[tion
			add_action('wpseo_desc', array( $this, 'rewrite_yoast_description' ) );
			
			// translaion
			add_action('plugins_loaded', array( $this, 'plugin_translation' ) );			
			
		}
		
		// plugin transaltion
		function plugin_translation(){
			$plugin_dir = basename(dirname(__FILE__));
			load_plugin_textdomain( $this->locale , false, $plugin_dir );
		}
		
		// uoutput existed links
		function corss_terms_links(){
			$args = array(
				'showposts' => -1,
				'post_type' => 'cross_seo',
				'orderby' => 'title',
				'order' => 'ASC'
			);
			$all_terms = get_posts( $args );
			if( count($all_terms) > 0 ){
				$out = '
				<div class="tw-bs4">
				<ul>';
				foreach( $all_terms as $single_term ){
					$out .= '<div class="col-lg-4 col-md-6 col-sm-6"><a href="'.get_post_meta( $single_term->ID, 'archive_url', true ).'">'.$single_term->post_title.'</a></div>';
				}
				$out .= '				
				</ul>
				</div>
				';
			}
			return $out;
		}
		
		// check if yoast is active
		function check_yoast_active(){
			if(in_array('wordpress-seo/wp-seo.php', apply_filters('active_plugins', get_option('active_plugins')))){ 
				$this->is_yoast_active = 1;
			}
		}
		
		// rewrite yoast title
		function rewrite_yoast_title( $title ){
			if( $this->current_filtering_query )
				$title = $this->current_filtering_query->main_parameters['page_title'];
		
			return $title;
		}
		
		//rewrite yoast description
		function rewrite_yoast_description( $description ){
			if( $this->current_filtering_query )
				$description = $this->current_filtering_query->main_parameters['page_description'];
			
			return $description;
		}
		
		// print description in header
		function output_description(){
			if( $this->is_yoast_active == 0 ){
				echo '<meta name="description" content="'.$this->current_filtering_query->main_parameters['page_description'].'" />';
			}
		}
		
		// output extra archive text
		function extra_archive_text(){
			
			$out = $this->current_filtering_query->return_custom_text();	
			return $out;
		}
		
		// filter query_var
		function filter_query(){
			$this->current_filtering_query = new vooProcessQuery(); 
		}
		
		//init metaboxes
		function init_meta_box(){
			$all_taxonomies = get_taxonomies( );
	
			 $out_categories = array();
			 
			 
			 if( count($all_taxonomies) > 0 ){
				foreach( $all_taxonomies as $key => $value ) {
					
					$current_taxonomy = get_taxonomy( $value );
				 
					if( in_array( $value, array( 'product_type', 'product_visibility' ) ) ){ continue; }
					$all_cats =  get_terms( array( 'taxonomy' => $key, 'hide_empty' => 0 ) ) ;
					
				 
					if( count($all_cats) > 0 ){
						$out_categories[0] = __('Select Term'); 
						foreach( $all_cats as $single_cat ){
							$out_categories[$single_cat->term_id] = $single_cat->name.' ('.$current_taxonomy->label.')';
						}
					}
				}
				 
			 }
			  
		  
			 $meta_box = array(
				'title' => __( 'Cross Term Seo Settings', $this->locale),
				'post_type' => 'cross_seo',
				'position' => 'advanced',
				'place' => 'high'
			);
			$fields_parameters = array(
				array(
					'type' => 'select',
					'title' => __( 'Main Taxonomy Term 1', $this->locale),
					'name' => 'taxonomy_picking_1',
					'value' => $out_categories,
					'style' => ''
				),
				array(
					'type' => 'select',
					'title' => __( 'Taxonomy Term 2', $this->locale),
					'name' => 'taxonomy_picking_2',
					'value' => $out_categories,
					'style' => ''
				),
				array(
					'type' => 'select',
					'title' => __( 'Taxonomy Term 3', $this->locale),
					'name' => 'taxonomy_picking_3',
					'value' => $out_categories,
					'style' => ''
				),
				array(
					'type' => 'text',
					'title' => __( 'Page Title', $this->locale),
					'name' => 'page_title',
					'style' => ''
				),
				array(
					'type' => 'textarea',
					'title' => __( 'Page Description', $this->locale),
					'name' => 'page_description',
					'style' => ''
				),
				array(
					'type' => 'text',
					'title' => __( 'Archive Title', $this->locale),
					'name' => 'archive_title',
					'style' => ''
				),
				array(
					'type' => 'wide_editor',
					'title' => __( 'Category Description', $this->locale),
					'name' => 'archive_description',
					'style' => ''
				),
				array(
					'type' => 'wide_editor',
					'title' => __( 'Extra Text', $this->locale),
					'name' => 'extra_text',
					'style' => ''
				),
			 
			);		
			$new_metabox = new vooMetaBox1( $meta_box, $fields_parameters); 
		}
		
		//initiate scripts and CSS
		function init_styles_scripts(){
			$scripts_list = array(
				'common' => array(
					array( 'type' => 'style', 'url' => plugins_url('/modules/inc/assets/css/boot-cont.css', __FILE__ ) ),
				 
				),
				'admin' => array(
					array( 'type' => 'script', 'url' => plugins_url('/modules/js/admin.js', __FILE__ ), 'enq' => array( 'jquery' ), 'localization' => array( 'add_url' => get_option('home').'/wp-admin/post-new.php?post_type=event' ) ),
					array( 'type' => 'style', 'url' => plugins_url('/modules/css/admin.css', __FILE__ ) ),
				),
				'front' => array(
					array( 'type' => 'script', 'url' => plugins_url('/modules/js/front.js', __FILE__ ), 'enq' => array( 'jquery' ), 'localization' => array( 'add_url' => get_option('home').'/wp-admin/post-new.php?post_type=event', 'ajaxurl' => admin_url('admin-ajax.php') ) ),
					array( 'type' => 'style', 'url' => plugins_url('/modules/css/front.css', __FILE__ ) ),
				)
			);

			$insert_script = new vooAddStyles( $this->locale , $scripts_list);
		}
		
		//initiate cpt
		function init_cpt(){
			$labels = array(
				'name' => __('Cross Term Seo', $this->locale),
				'singular_name' => __('Cross Term Seo', $this->locale),
				'add_new' => __('Add New', $this->locale),
				'add_new_item' => __('Add New Cross Term Seo', $this->locale),
				'edit_item' => __('Edit Cross Term Seo', $this->locale),
				'new_item' => __('New Cross Term Seo', $this->locale),
				'all_items' => __('All Cross Term Seo', $this->locale),
				'view_item' => __('View Cross Term Seo', $this->locale),
				'search_items' => __('Search Cross Term Seo', $this->locale),
				'not_found' =>  __('No Cross Term Seo found', $this->locale),
				'not_found_in_trash' => __('No Cross Term Seo found in Trash', $this->locale), 
				'parent_item_colon' => '',
				'menu_name' => __('Cross Term Seo', $this->locale)

			  );
			  $args = array(
				'labels' => $labels,
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true, 
				'show_in_menu' => true, 
				'query_var' => true,
				'rewrite' => true,
				'capability_type' => 'post',
				'has_archive' => true, 
				'hierarchical' => false,
				'menu_position' => null,
				'supports' => array( 'title',  /*'custom-fields''editor' , 'thumbnail', 'excerpt', 'custom-fields'   'custom-fields' 'custom-fields'  'editor', 'thumbnail', 'custom-fields'  'author', , 'custom-fields', 'editor'  */)
			  ); 

			 
			$new_pt = new vooCPT( $args, 'cross_seo' );
			
		}
		
		
	}
}




new vooMainStart('wms', array(
	'modules/class/scripts_class.php',
	'modules/class/cpt_class.php',
	'modules/class/meta_box_class.php',
	'modules/class/vooProcessQuery.php',
	'modules/class/settings_class.php',
), dirname(__FILE__).'/' );

?>