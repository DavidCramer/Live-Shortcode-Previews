<?php
/*
  Plugin Name: Live Shortcode Previews
  Plugin URI: http://cramer.co.za/
  Description: Creates a live preview for any registerd shortcode.
  Author: David Cramer
  Version: 0.0.1
  Author URI: http://digilab.co.za
 */

//initilize plugin
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


if(!is_admin()){
	// no access without admin
	return;
}

add_action('admin_footer-edit.php', 'lsp_render_editor_template'); // Fired on the page with the posts table
add_action('admin_footer-post.php', 'lsp_render_editor_template'); // Fired on post edit page
add_action('admin_footer-post-new.php', 'lsp_render_editor_template'); // Fired on add new post page		
add_action("wp_ajax_lsp_render_shortcode_preview", 'lsp_render_shortcode_preview');

add_action("admin_print_scripts", "lsp_render_register_scripts", 100);

function lsp_render_shortcode_preview(){
	global $post;
	$post = get_post( (int) $_POST['post_id'] );
	ob_start();
	echo do_shortcode( urldecode( $_POST['raw'] ) );
	$out['html'] = ob_get_clean();
	wp_send_json_success( $out );
}

function lsp_render_register_scripts(){
?>
<script type="text/javascript">


jQuery(function($){
var media = wp.media;
if( typeof wp.mce === 'undefined'){
	return;
}
	<?php
	
	global $shortcode_tags; // Load All of them
	// OR comment out the above line add an array of valid shortcodes
	//$shortcode_tags = array(
	//	'shortcode_slug' => 1
	//);


	foreach ($shortcode_tags as $shortcode => $config) {
		?>
		wp.mce.views.register( '<?php echo $shortcode; ?>', {
			View: {
				template: media.template( 'live-shortcode-preview' ),

				initialize: function( options ) {
					this.shortcode = options.shortcode;
					this.fetch();
				},
				loadingPlaceholder: function() {
					return '' +
						'<div class="loading-placeholder">' +
							'<div class="dashicons dashicons-update"></div>' +
							'<div class="wpview-loading"><ins></ins></div>' +
						'</div>';
				},
				fetch: function() {
					var self = this;


					options = {};
					options.context = this;
					options.data = {
						action:  'lsp_render_shortcode_preview',
						post_id: $('#post_ID').val(),
						atts: this.shortcode.attrs,
						raw: this.encodedText
					};

					this.html = media.ajax( options );
					this.dfd = this.html.done( function(form) {
						this.html.data = form;
						self.render( true );
					} );
				},
				getHtml: function() {
					var attrs = this.shortcode.attrs.named,
						attachments = false,
						options;

					// Don't render errors while still fetching content
					if ( this.dfd && 'pending' === this.dfd.state() && ! this.html.length ) {
						return '';
					}

					return this.template( this.html.data );
				}
			}
		});	
	<?php
	}
	?>
});

</script>
<?php
}

function lsp_render_editor_template(){
?>
<script type="text/html" id="tmpl-live-shortcode-preview">
<# if ( data.html ) { #>
	{{{ data.html }}}
<# } else { #>
	<div class="wpview-error">
		<div class="dashicons dashicons-no"></div><p style="font-size: 13px;"><?php _e( 'Invalid or No Content', 'caldera-forms' ); ?></p>
	</div>
<# } #>
</script>
<?php

}