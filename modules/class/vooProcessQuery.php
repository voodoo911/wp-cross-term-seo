<?php 
if( !class_exists('vooProcessQuery') ){
	class vooProcessQuery{
		
		var $main_parameters;
		
		
		function __construct(){
			add_action('template_redirect', array( $this, 'rewrite_credentials') );
			add_action('pre_get_posts', array( $this, 'pre_posts_filtering') );
			 
		}
		
		//generate pair from taxonomy
		function generate_pair( $term ){
			if( substr_count( $term, 'attribute_' ) > 0 ){
				$inner_array = explode( '_', $term );
				$term_item = $inner_array[0].'_'.$inner_array[1] ;
				$term_item_slug = $inner_array[2];
			}else{
				$term_item = get_term( $term )->taxonomy ;								
				// pa fix
				if( substr( $term_item, 0, 3 ) == 'pa_' ){
					$term_item = str_replace( 'pa_', 'filter_', $term_item );
				}								
				$term_slug = get_term( $term )->slug ;
			}
			
			// patch for tags and cats
			/*
			if( $term_1 == 'category' ){
				$term_1 = 'cat';
			}
			if( $term_1 == 'post_tag' ){
				$term_1 = 'tag';
			}
			*/
			
			return array( 'tax' => $term_item, 'slug' => $term_slug );
		}
		
		// generate array for single post
		function generate_micro_parameters( $post_id ){
			$term_1_tmp = get_post_meta( $post_id, 'taxonomy_picking_1', true );				
			if( $term_1_tmp ){
				$fomr_micro_params[] = $this->generate_pair( $term_1_tmp );
			}
							
			$term_2_tmp = get_post_meta( $post_id, 'taxonomy_picking_2', true );
							
			if( $term_2_tmp ){
				$fomr_micro_params[] = $this->generate_pair( $term_2_tmp );
			}
			
			$term_3_tmp = get_post_meta( $post_id, 'taxonomy_picking_3', true );
			if( $term_3_tmp){
				$fomr_micro_params[] = $this->generate_pair( $term_3_tmp );
			}

			return $fomr_micro_params;
		}
		
		// ger current archive data
		function pre_posts_filtering( $query ){
			
			//if is archive
		 
			if( is_archive() ){
				 $queried_object = get_queried_object();
				 // if archive have slug
				 if( isset( $queried_object->slug ) ){
					
					$out_parameters = array();
					
					$all_parameters = $_GET;
					 
					 // getting main parameter
					$term_id = $queried_object->term_id;
					 
					$main_term = get_term( $term_id );
				
					$out_parameters[] = array( 'tax' => $main_term->taxonomy, 'slug' => $main_term->slug ); 
				 
					// process all gets
					if( count($_GET) > 0 ){
						foreach( $_GET as $key => $value){
							$out_parameters[] = array( 'tax' => $key, 'slug' => $value );
						}
					}
			
					if( count($out_parameters) > 0 ){
						foreach ($out_parameters as $key => $row) {
							$tax[$key]  = $row['tax'];
							$slug[$key] = $row['slug'];
						}
	 
						array_multisort($tax, SORT_DESC, $slug, SORT_ASC, $out_parameters);
						
						// generate string to make hash
						$out_hash_arr = array();
						foreach ($out_parameters as $single_row ){
							$out_hash_arr[] = $single_row['tax'].'='.$single_row['slug'];
						}
						$out_hash = implode('&', $out_hash_arr);
					}
					
				 
					// making array hash
				
					$current_query_hash = md5( $out_hash );
					
					
					// generating hashes from micro seo
					// gettting all seo variants
					$args = array(
						'post_type' => 'cross_seo',
						'showposts' => -1
					);
					remove_action('pre_get_posts', array($this, 'pre_posts_filtering') );
					$all_posts = get_posts( $args );

					if( count( $all_posts ) > 0 ){
						foreach( $all_posts as $single_post ){
							
							// generate string and ordering
							$fomr_micro_params = array();

							$fomr_micro_params = $this->generate_micro_parameters( $single_post->ID );
							
							$out_str = array();

							$tax = array();
							$slug = array();
							foreach ($fomr_micro_params as $key => $row) {
								$tax[$key]  = $row['tax'];
								$slug[$key] = $row['slug'];
							}
		 
							array_multisort($tax, SORT_DESC, $slug, SORT_ASC, $fomr_micro_params);
						 
							// generate string to make hash
							$out_hash_arr = array();
							foreach ($fomr_micro_params as $single_row ){
								$out_hash_arr[] = $single_row['tax'].'='.$single_row['slug'];
							}
							$out_hash = implode('&', $out_hash_arr);
						
						
							$from_array_query_hash = md5( $out_hash );
 
						
							if( $current_query_hash == $from_array_query_hash  ){
								$this->main_parameters = array(
									'post_id' => $single_post->ID,
									'hash' => $from_array_query_hash ,
									'taxonomy_picking_1' => get_post_meta( $single_post->ID, 'taxonomy_picking_1', true ),
									'taxonomy_picking_2' => get_post_meta( $single_post->ID, 'taxonomy_picking_2', true ),
									'taxonomy_picking_3' => get_post_meta( $single_post->ID, 'taxonomy_picking_3', true ),
									'page_title' => get_post_meta( $single_post->ID, 'page_title', true ),
									'page_description' => get_post_meta( $single_post->ID, 'page_description', true ),
									'archive_title' => get_post_meta( $single_post->ID, 'archive_title', true ),
									'archive_description' => get_post_meta( $single_post->ID, 'archive_description', true ),
									'extra_text' => get_post_meta( $single_post->ID, 'extra_text', true ),
								);
								
							}
						}
					}
					
				 }
			}
			
			
			$queried_object = get_queried_object();			
			// woocommerce patch
			if( is_array( $this->main_parameters ) ){
			 
				$taxObject = get_taxonomy( $queried_object->taxonomy );
				$related_post_types =  $taxObject->object_type;
				$queried_object->description = $this->main_parameters['archive_description'];
				
				
				//overwrite 
				$out_parameters = $this->generate_micro_parameters( $this->main_parameters['post_id'] ) ;

				$param_array = array();
				if( count($out_parameters) > 0 ){
					foreach( $out_parameters as $single_param ){
						
						// brand fix
						if( substr_count( $single_param['tax'], 'filter_' ) > 0 ){
							$single_param['tax'] = str_replace( 'filter_', 'pa_',  $single_param['tax']);
						}
						
						$param_array[] = array(
							'taxonomy' => $single_param['tax'],
							'field' => 'slug',
							'terms' => $single_param['slug'],
						);
					}
					
			 
					 
					$query->set( 'tax_query', $param_array );

				}
				
			}
			 
		}
		
		function rewrite_credentials(){
 
			// process things
			if( is_array( $this->main_parameters ) ){

			 
				// document meta title
				add_filter('pre_get_document_title', array( $this, 'change_the_title'), 1000000 );
				add_filter('wp_title', array( $this, 'wms_wp_title'), 1000000 );
				
				// archive titlte
				add_filter('the_archive_title',  array( $this, 'wms_the_archive_title'), 1000000 );
				add_filter('get_the_archive_title',  array( $this, 'wms_the_archive_title'), 1000000 );
				add_filter('woocommerce_page_title',  array( $this, 'wms_the_archive_title'), 1000000 );
				
				// archive description
				add_filter('the_archive_description',  array( $this, 'wms_the_archive_description'), 1000000 );
				add_filter('get_the_archive_description',  array( $this, 'wms_the_archive_description'), 1000000 );
				//add_filter('woocommerce_archive_description',  array( $this, 'wms_the_archive_description'), 1000000 );
				
				
				
			}
		}
		
		 
		
		//page title tag
		function change_the_title() {
	
			global $wp_query; 
			return $this->main_parameters['page_title'];
		}
		
		function wms_wp_title() {
			return $this->main_parameters['page_title'];
		}
		function wms_the_archive_title( $title ) {
			$title = $this->main_parameters['archive_title'];			
			return $title;
		}
		function wms_the_archive_description( $content ) {
			$content = '<div class="term-description"><p>'.$this->main_parameters['archive_description'].'</p></div>';
			echo $content;
		}
		
		
		function return_custom_text() {
			return $this->main_parameters['extra_text'];
		}

	}	
}
?>