<?php 

if( !class_exists('vooSettingsClassMain') ){
class vooSettingsClassMain{
	
	var $setttings_parameters;
	var $settings_prefix;
	
	function __construct( $prefix ){
		$this->setttings_prefix = $prefix;	
		
		if(  wp_verify_nonce($_POST['save_settings_field'], 'save_settings_action') ){
			$options = array();
			foreach( $_POST as $key=>$value ){
				$options[$key] = $value ;
			}
			update_option( $this->setttings_prefix.'_options', $options );
		}
	}
	
	function get_setting( $setting_name ){
		$inner_option = get_option( $this->setttings_prefix.'_options');
		return $inner_option[$setting_name];
	}
	
	function create_menu( $parameters ){
		$this->setttings_parameters = $parameters;		
			
		add_action('admin_menu', array( $this, 'add_menu_item') );
		
	}
	
	 
	
	
	function add_menu_item(){
		
		foreach( $this->setttings_parameters as $single_option ){
			
			if( $single_option['type'] == 'submenu' ){
				add_submenu_page(  
				$single_option['parent_slug'],  
				$single_option['page_title'], 
				$single_option['menu_title'], 
				$single_option['capability'], 
				$single_option['menu_slug'], 
				array( $this, 'show_settings' ) 
				);
			}
			if( $single_option['type'] == 'option' ){
				add_options_page(  				  
				$single_option['page_title'], 
				$single_option['menu_title'], 
				$single_option['capability'], 
				$single_option['menu_slug'], 
				array( $this, 'show_settings' ) 
				);
			}
		}
		 
	}
	
	function show_settings(){
		?>
		<div class="wrap tw-bs4">
		<h2><?php _e('Settings', 'sc'); ?></h2>
		<hr/>
		<form class="form-horizontal" method="post" action="">
		<?php 
		wp_nonce_field( 'save_settings_action', 'save_settings_field'  );  
		$config = get_option( $this->setttings_prefix.'_options'); 
		?>  
		<fieldset>

			<?php 
		foreach( $this->setttings_parameters as $single_page ){	
			foreach( $single_page['parameters'] as $key=>$value ){
				switch( $value['type'] ){
					case "separator":
						$out .= '
						<div class="lead">'.$value['title'].'</div> 
						';
					break;
					case "text":
						$out .= '
						<div class="form-group">  
							<label class="control-label" for="'.$value['id'].'">'.$value['title'].'</label>  
							
							  <input type="text"  class="form-control '.$value['class'].'"  name="'.$value['name'].'" id="'.$value['id'].'" placeholder="'.$value['placeholder'].'" value="'.esc_html( stripslashes( $config[$value['name']] ) ).'">  
							  <p class="help-block">'.$value['sub_text'].'</p>  
							
						  </div> 
						';
					break;
					case "select":
						$out .= '
						<div class="form-group">  
							<label class="control-label" for="'.$value['id'].'">'.$value['title'].'</label>  
							 
							  <select  style="'.$value['style'].'" class="form-control '.$value['class'].'" name="'.$value['name'].'" id="'.$value['id'].'">' ; 
							  if( count( $value['value'] ) > 0 )
							  foreach( $value['value'] as $k => $v ){
								  $out .= '<option value="'.$k.'" '.( $config[$value['name']]  == $k ? ' selected ' : ' ' ).' >'.$v.'</option> ';
							  }
						$out .= '		
							  </select>  
							  <p class="help-block">'.$value['sub_text'].'</p> 
							</div>  
						 
						';
					break;
					case "checkbox":
						$out .= '
						<div class="form-group">  
							<label class="control-label" for="'.$value['id'].'">'.$value['title'].'</label>  
						
							  <label class="checkbox">  
								<input  class="'.$value['class'].'" type="checkbox" name="'.$value['name'].'" id="'.$value['id'].'" value="on" '.( $config[$value['name']] == 'on' ? ' checked ' : '' ).' > &nbsp; 
								'.$value['text'].'  
								<p class="help-block">'.$value['sub_text'].'</p> 
							  </label>  
							 
						  </div>  
						';
					break;
					case "radio":
						$out .= '
						<div class="form-group">  
							<label class="control-label" for="'.$value['id'].'">'.$value['title'].'</label>';
								foreach( $value['value'] as $k => $v ){
									$out .= '
									<label class="radio">  
										<input  class="'.$value['class'].'" type="radio" name="'.$value['name'].'" id="'.$value['id'].'" value="'.$k.'" '.( $config[$value['name']] == $k ? ' checked ' : '' ).' >&nbsp;  
										'.$v.'  
										<p class="help-block">'.$value['sub_text'].'</p> 
									  </label> ';
								}
							$out .= '
							
						  </div>  
						';
					break;
					case "textarea":
						$out .= '
						<div class="form-group">  
							<label class="control-label" for="'.$value['id'].'">'.$value['title'].'</label>  
						
							  <textarea style="'.$value['style'].'" class="form-control '.$value['class'].'" name="'.$value['name'].'" id="'.$value['id'].'" rows="'.$value['rows'].'">'.esc_html( stripslashes( $config[$value['name']] ) ).'</textarea>  
							  <p class="help-block">'.$value['sub_text'].'</p> 
						 
						  </div> 
						';
					break;
					case "multiselect":
						$out .= '
						<div class="form-group">  
							<label class="control-label" for="'.$value['id'].'">'.$value['title'].'</label>  
							 
							  <select  multiple="multiple" style="'.$value['style'].'" class="form-control '.$value['class'].'" name="'.$value['name'].'[]" id="'.$value['id'].'">' ; 
							  foreach( $value['value'] as $k => $v ){
								  $out .= '<option value="'.$k.'" '.( @in_array( $k, $config[$value['name']] )   ? ' selected ' : ' ' ).' >'.$v.'</option> ';
							  }
						$out .= '		
							  </select>  
							  <p class="help-block">'.$value['sub_text'].'</p> 
							 
						  </div>  
						';
					break;
					case "wide_editor":
					$out .= '<div class="form-group">  
						<label class="control-label" for="input01">'.$value['title'].'</label>
						<div class="form-control1">
						';  
						 
						ob_start();
						wp_editor( $config[$value['name']], $value['name'] );
						$editor_contents = ob_get_clean();	
					 
						$out .= $editor_contents;  
					$out .= '
						</div>
					  </div> ';	 
					 
					break;
				}
			}
		}
			echo $out;
			?>

				
				  <div class="form-actions">  
					<button type="submit" class="btn btn-primary">Save Settings</button>  
				  </div>  
				</fieldset>  

		</form>

		</div>
		<?php
	}
}	
}	
	

class reprocessSeo extends vooSettingsClassMain{
	
	var $params_block;
	var $temp_var;
	
	function __construct(  ){
 
		if(  wp_verify_nonce($_POST['save_settings_field'], 'save_settings_action') ){
			$this->params_block = array(
				'taxonomy_picking_1' => $_POST[ 'taxonomy_picking_1' ],
				'taxonomy_picking_mask_1' => $_POST[ 'taxonomy_picking_mask_1' ],
				
				'taxonomy_picking_2' => $_POST[ 'taxonomy_picking_2' ],
				'taxonomy_picking_mask_2' => $_POST[ 'taxonomy_picking_mask_2' ],
				
				'taxonomy_picking_3' => $_POST[ 'taxonomy_picking_3' ],
				'taxonomy_picking_mask_3' => $_POST[ 'taxonomy_picking_mask_3' ],
				
				'page_title' => $_POST[ 'page_title' ],
				'page_description' => $_POST[ 'page_description' ],
				'archive_title' => $_POST[ 'archive_title' ],
				'archive_description' => $_POST[ 'archive_description' ],
				'extra_text' => $_POST['extra_text' ],
			);
	
			$loop_array = array(); 
			for( $i=1; $i<=3; $i++ ){
				if( $this->params_block['taxonomy_picking_mask_'.$i] != '0' ){
					$loop_array[] = $this->params_block['taxonomy_picking_mask_'.$i];
				}
			}
		
			if( count($loop_array) > 0 ){
				// making loop go thru
				foreach( $loop_array as $single_taxonomy ){
					
					$terms = get_terms( $single_taxonomy, array(
						'hide_empty' => false,
					));
			 
					if( count($terms) > 0 ){
						foreach( $terms as $single_term ){
							$this->temp_var['page_title'] = str_replace( '%'.$single_taxonomy.'%', $single_term->name, $this->params_block['page_title'] );
							$this->temp_var['page_description'] = str_replace( '%'.$single_taxonomy.'%', $single_term->name, $this->params_block['page_description'] );
							$this->temp_var['archive_title'] = str_replace( '%'.$single_taxonomy.'%', $single_term->name, $this->params_block['archive_title'] );
							$this->temp_var['archive_description'] = str_replace( '%'.$single_taxonomy.'%', $single_term->name, $this->params_block['archive_description'] );
							$this->temp_var['extra_text'] = str_replace( '%'.$single_taxonomy.'%', $single_term->name, $this->params_block['extra_text'] );
							
							$args = array(
								'post_title' => $this->temp_var['page_title'],
								'post_type' => 'cross_seo',
								'post_status' => 'publish'
							);
							$new_id = wp_insert_post( $args );
							
							foreach( $this->temp_var as $key => $value ){
								update_post_meta( $new_id, $key, $value );
							}
							
							// updating terms
							if( $this->params_block['taxonomy_picking_mask_1'] != '0' ){
								// process term 1 masking
								if( $this->params_block['taxonomy_picking_mask_1'] == $single_taxonomy ){
									update_post_meta( $new_id, 'taxonomy_picking_1', $single_term->term_id );
								}
							}else{
								if( $this->params_block['taxonomy_picking_1'] != '0' ){
									update_post_meta( $new_id, 'taxonomy_picking_1', $this->params_block['taxonomy_picking_1'] );
								}
							}
							
							// updating terms
							if( $this->params_block['taxonomy_picking_mask_2'] != '0' ){
								// process term 2 masking
								if( $this->params_block['taxonomy_picking_mask_2'] == $single_taxonomy ){
									update_post_meta( $new_id, 'taxonomy_picking_2', $single_term->term_id );
								}
							}else{
								if( $this->params_block['taxonomy_picking_2'] != '0' ){
									update_post_meta( $new_id, 'taxonomy_picking_2', $this->params_block['taxonomy_picking_2'] );
								}
							}
							
							// updating terms
							if( $this->params_block['taxonomy_picking_mask_3'] != '0' ){
								// process term 3 masking
								if( $this->params_block['taxonomy_picking_mask_3'] == $single_taxonomy ){
									update_post_meta( $new_id, 'taxonomy_picking_3', $single_term->term_id );
								}
							}else{
								if( $this->params_block['taxonomy_picking_3'] != '0' ){
									update_post_meta( $new_id, 'taxonomy_picking_3', $this->params_block['taxonomy_picking_3'] );
								}
							}
							
						}
					}
					
					
				}
				
			}
			
			
		}
		
		
		

	}
	
 
	 
}
	
	 
 

?>