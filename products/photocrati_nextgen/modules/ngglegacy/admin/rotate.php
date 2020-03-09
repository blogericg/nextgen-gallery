<?php
/**

Custom thumbnail for NGG
Author : Simone Fumagalli | simone@iliveinperego.com
More info and update : http://www.iliveinperego.com/rotate_for_ngg/

Credits:
 NextGen Gallery : Alex Rabe | http://alexrabe.boelinger.com/wordpress-plugins/nextgen-gallery/
 
**/

require_once( dirname( dirname(__FILE__) ) . '/ngg-config.php');
require_once( NGGALLERY_ABSPATH . '/lib/image.php' );

if ( !is_user_logged_in() )
	die(__('Cheatin&#8217; uh?'));
	
if ( !current_user_can('NextGEN Manage gallery') ) 
	die(__('Cheatin&#8217; uh?'));

global $wpdb;

$id = (int) $_GET['id'];

// let's get the image data
$picture = nggdb::find_image($id);

include_once( nggGallery::graphic_library() );

// Generate a url to a preview image
$storage       = C_Gallery_Storage::get_instance();
$preview_image = $storage->get_image_url($id, 'full');
?>

<script type='text/javascript'>
    var cdn_enabled   = <?php echo (C_CDN_Providers::is_cdn_configured() ? 'true' : 'false') ?>;
    var selectedImage = "thumb<?php echo $id ?>";
	
    function rotateImage() {
		
        var rotate_angle = jQuery('input[name=ra]:checked').val();
		
        jQuery.ajax({
            url: ajaxurl,
            type : "POST",
            data:  {
                action: 'rotateImage',
                id: <?php echo $id ?>, ra: rotate_angle
            },
            cache: false,
            success: function (response) {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                showMessage('<?php _e('Image rotated', 'nggallery'); ?>', true)

                if (response.cdn_enabled) {
                    setTimeout(function() {
                        // TODO: this is known to be broken
                        var $image = jQuery("#imageToEdit");
                        $image.attr('src', response.new_image_url);
                    }, 1000);
                } else {
                    var $image = jQuery("#imageToEdit");
                    var d = new Date();
                    var url = $image.attr('src');
                    if (url.indexOf('?') > -1) {
                        url += '&i=';
                    } else {
                        url += '?i=';
                    }
                    url += d.getTime();
                    $image.attr("src" , url);
                }
            },
            error: function (msg, status, errorThrown) {
                showMessage('<?php _e('Error rotating thumbnail', 'nggallery'); ?>', false)
            }
        });
    }

	function showMessage(message, success) {
        var $message = jQuery('#thumbMsg');
		$message.html(message);
		$message.css({'display':'block'});
		setTimeout(function() {
		    $message.fadeOut('slow');
        }, 1500);
	}
</script>

<table align="center">
	<tr>
		<td valign="middle" align="center" id="ngg-overlay-dialog-main">
			<img src="<?php echo nextgen_esc_url( $preview_image ); ?>"
                 alt=""
                 id="imageToEdit"
                 style="max-width: 450px;
                        max-height: 350px;"/>
		</td>
		<td>
			<input type="radio" name="ra" value="cw" /><?php esc_html_e('90&deg; clockwise', 'nggallery'); ?><br />
			<input type="radio" name="ra" value="ccw" /><?php esc_html_e('90&deg; counter-clockwise', 'nggallery'); ?><br />
			<input type="radio" name="ra" value="fv" /><?php esc_html_e('Flip vertically', 'nggallery'); ?><br />
			<input type="radio" name="ra" value="fh" /><?php esc_html_e('Flip horizontally', 'nggallery'); ?>
		</td>		
	</tr>
</table>
<div id="ngg-overlay-dialog-bottom">
	<input type="button" name="update" value="<?php esc_attr_e('Update', 'nggallery'); ?>" onclick="rotateImage()" class="button-primary" />
	<div id="thumbMsg"></div>
</div>


