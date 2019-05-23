<?php 
if( !class_exists('vooAddStyles') ){
	class vooAddStyles{
		
		protected $plugin_prefix;
		protected $plugin_version;
		protected $files_list;
		
		public  function __construct( $prefix, $parameters ){
			
			$this->files_list = $parameters;
			$this->plugin_prefix = $prefix;
			$this->plugin_version = '1.0';
			
			add_action('wp_print_scripts', array( $this, 'add_script_fn') );
		}
		public function add_script_fn(){
			
			
			
			 foreach( $this->files_list as $key => $value ){
				 if( $key == 'common' ){
					foreach( $value as $single_line ) {
						$this->process_enq_line( $single_line );
					}
				 }
				 if( $key == 'admin' && is_admin() ){
					foreach( $value as $single_line ) {
						$this->process_enq_line( $single_line );
					}
				 }
				 if( $key == 'front' && !is_admin() ){
					foreach( $value as $single_line ) {
						$this->process_enq_line( $single_line );
					}
				 }
			 }
		}
		public function process_enq_line( $line ){
		 
			$custom_id  = rand( 1000, 9999).basename( $line['url'] );
			if( $line['type'] == 'style' ){
				wp_enqueue_style( $this->plugin_prefix.$custom_id, $line['url'] ) ;
			}
			if( $line['type'] == 'script' ){
				wp_enqueue_script( $this->plugin_prefix.$custom_id, $line['url'], $line['enq'] ) ;		
				if( $line['localization'] ){
					wp_localize_script( $this->plugin_prefix.$custom_id, $this->plugin_prefix.'_local_data', $line['localization'] );
				}
			}
		}
	}
}

 

?>