<?php 
if( !class_exists( 'vooMetaBox1' ) ){
	class vooMetaBox1{
		
		private $metabox_parameters = null;
		private $fields_parameters = null;
		private $data_html = null;
		
		function __construct( $metabox_parameters , $fields_parameters){
			$this->metabox_parameters = $metabox_parameters;
			$this->fields_parameters = $fields_parameters;
 
			add_action( 'add_meta_boxes', array( $this, 'add_custom_box' ) );
			add_action( 'save_post', array( $this, 'save_postdata' ) );
		}
		
		function get_original_url( $url ){
			$on_redirect_url = $url;
			$request_res = wp_remote_get( $url ) ;			
			if( !is_wp_error($request_res) ){
				$response_object =  $request_res["http_response"]->get_response_object() ;
	
				if(   is_array( $response_object->history ) && count( $response_object->history ) > 0  ){
					$location = $response_object->history[0]->headers->getValues('location');
					$on_redirect_url =  $location[0] ;
				}
				return $on_redirect_url;
			}else{
				return false;
			}
			
		}
		
		function add_custom_box(){
			add_meta_box( 
				'custom_meta_editor_'.rand( 100, 999 ),
				$this->metabox_parameters['title'],
				array( $this, 'custom_meta_editor' ),
				$this->metabox_parameters['post_type'] , 
				$this->metabox_parameters['position'], 
				$this->metabox_parameters['place']
			);
		}
		function custom_meta_editor(){
			global $post;
			
			$out = '

			<div class="boot_container">
				<div class="form-horizontal ">';
			
			foreach( $this->fields_parameters as $single_field){
			 
				switch( $single_field['type'] ){
					
					case "shortcode":
					$out .= '
					<div class="tw-bs4">
					<div class="form-group">  
						<label class="control-label" for="input01">'.$single_field['title'].'</label>  
						 
						  <input type="text" class="form-control input-xlarge" name="'.$single_field['name'].'" id="'.$single_field['name'].'" 
						  value="['.$single_field['name'].' id=\''.$post->ID.'\']"
						  
						  >  
						  
					  </div> 
					</div>';	
					break;
					
					
					case "textarea":
					$out .= '
					<div class="tw-bs4">
					<div class="form-group">  
						<label class="control-label" for="input01">'.$single_field['title'].'</label>  
						 
						  <textarea type="text" class="form-control input-xlarge" style="'.$single_field['style'].'" name="'.$single_field['name'].'" id="'.$single_field['name'].'" >'.htmlentities( get_post_meta( $post->ID, $single_field['name'], true ) ).'</textarea>  
						  
					  </div> 
					</div>';	
					break;
					case "text":
					$out .= '
					<div class="tw-bs4">
					<div class="form-group">  
						<label class="control-label" for="input01">'.$single_field['title'].'</label>  
						 
						  <input type="text" class="form-control input-xlarge" name="'.$single_field['name'].'" id="'.$single_field['name'].'" value="'.get_post_meta( $post->ID, $single_field['name'], true ).'">  
						  
					  </div> 
					</div>';	
					break;
					case "checkbox":
					$out .= '
					
					<div class="tw-bs4">
					<div class="form-group">  
						<label class="control-label" for="input01">'.$single_field['title'].'</label>  
					  
						  <input type="checkbox" class="form-control "  name="'.$single_field['name'].'" id="'.$single_field['name'].'" value="on" '.( get_post_meta( $post->ID, $single_field['name'], true ) == 'on' ? ' checked ' : '' ).' >  
						  
					  </div> 
					 </div>';	
					break;
					case "select":
					$out .= '
					<div class="tw-bs4">
					<div class="form-group">  
						<label class="control-label" for="input01">'.$single_field['title'].'</label>';

							$out .= '<select class="form-control " name="'.$single_field['name'].'">';
							foreach( $single_field['value'] as $key => $value ){
								$out .= '<option '.( get_post_meta( $post->ID, $single_field['name'], true ) == $key ? ' selected ' : '' ).' value="'.$key.'">'.$value;
							}
							$out .= '</select>';
						 
					$out .= '
						</div>
					 </div> ';	
					break;
					
					case "wide_editor":
					$out .= '
					<div class="form-group">  
						<label class="control-label" for="input01">'.$single_field['title'].'</label>
						<div class="form-control1">
						';  
						 
						ob_start();
						wp_editor( get_post_meta( $post->ID, $single_field['name'], true ), $single_field['name'] );
						$editor_contents = ob_get_clean();	
						
						$out .= $editor_contents;  
					$out .= '
						</div>
					  </div> ';	 
					 
					break;
				}
			}		
			
					
					
			$out .= '
					</div>	
				</div>
				';	
			$this->data_html = $out;
			 
			$this->echo_data();
		}
		
		function echo_data(){
			echo $this->data_html;
		}
		
		function save_postdata( $post_id ) {
			global $current_user; 
			 if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
				  return;

			  if ( 'page' == $_POST['post_type'] ) 
			  {
				if ( !current_user_can( 'edit_page', $post_id ) )
					return;
			  }
			  else
			  {
				if ( !current_user_can( 'edit_post', $post_id ) )
					return;
			  }
			  /// User editotions

				if( get_post_type($post_id) == $this->metabox_parameters['post_type'] ){
					foreach( $this->fields_parameters as $single_parameter ){						 
						update_post_meta( $post_id, $single_parameter['name'], $_POST[$single_parameter['name']] );
					}
					
				}
				 
				if( get_post_type($post_id) == 'cross_seo' ){
					$query_object = new vooProcessQuery();
					$fomr_micro_params = (array)$query_object->generate_micro_parameters( $post_id );
									 
					$out_str = array();

					$tax = array();
					$slug = array();
					if( count($fomr_micro_params) > 0 ){
						foreach ($fomr_micro_params as $key => $row) {
							$tax[$key]  = $row['tax'];
							$slug[$key] = $row['slug'];
						}
					}
		 
					array_multisort($tax, SORT_DESC, $slug, SORT_ASC, $fomr_micro_params);
						 
					// generate string to make hash
					$out_hash_arr = array();
					if( count($fomr_micro_params) > 0 ){
						$cnt = 0;
						$prev_item_link = get_term_link( $fomr_micro_params[0]['slug'], $fomr_micro_params[0]['tax'] );
						foreach ($fomr_micro_params as $single_row ){
							if( $cnt == 0 ){ $cnt++; continue; }
							$out_hash_arr[] = $single_row['tax'].'='.$single_row['slug'];
							
						}
					}
					$out_hash = implode('&', $out_hash_arr);
					$archive_url = $this->get_original_url( $prev_item_link.'?'.$out_hash );
					update_post_meta( $post_id, 'archive_url', $archive_url );
					
				}
				
			}
	}
}

 
?>