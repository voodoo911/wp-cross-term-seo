jQuery(document).ready(function($){
	$('.taxonomy_picking').change(function(){
		var val = $(this).val();
		var parent = $(this).parents('.form-group');
		$('.help-block', parent).html( 'Use mask^ %'+val+'% for autogenerator' );
	})
});