<?php

add_action('wp_ajax_ngg_ajax_operation', 'ngg_ajax_operation');

function ngg_ajax_operation()
{
	// if nonce is not correct it returns -1
	check_ajax_referer("ngg-ajax");

	// check for correct capability
	if (!is_user_logged_in())
		die('-1');

	// check for correct NextGEN capability
	if (!current_user_can('NextGEN Upload images') && !current_user_can('NextGEN Manage gallery'))
		die('-1');

	// include the ngg function
	include_once (dirname(__FILE__) . '/functions.php');

	// Get the image id
	if (isset($_POST['image']))
	{
        $id = (int) $_POST['image'];

		$picture = nggdb::find_image($id);

		switch ($_POST['operation']) {
			case 'create_thumbnail':
                $result = nggAdmin::create_thumbnail($picture);
                break;
            case 'resize_image':
                $result = nggAdmin::resize_image($picture);
                break;
            case 'rotate_cw':
                $result = nggAdmin::rotate_image($picture, 'CW');
                nggAdmin::create_thumbnail($picture);
                break;
            case 'rotate_ccw':
                $result = nggAdmin::rotate_image($picture, 'CCW');
                nggAdmin::create_thumbnail($picture);
                break;
            case 'set_watermark':
                $result = nggAdmin::set_watermark($picture);
                break;
            case 'recover_image':
                $result = nggAdmin::recover_image($id) ? '1': '0';
                break;
            case 'import_metadata':
                $result = C_Image_Mapper::get_instance()->reimport_metadata($id) ? '1' : '0';
                break;
            case 'get_image_ids':
                $result = nggAdmin::get_image_ids( $id );
                break;
            default:
                do_action( 'ngg_ajax_' . $_POST['operation'] );
                die('-1');
                break;
		}

		// A success should return a '1'
		die ($result);
	}

	// The script should never stop here
	die('0');
}

add_action('wp_ajax_createNewThumb', 'createNewThumb');

function createNewThumb()
{
	if (!is_user_logged_in())
		die('-1');
	if (!current_user_can('NextGEN Manage gallery'))
		die('-1');

	$id = (int) $_POST['id'];

	$x = round($_POST['x'] * $_POST['rr'], 0);
	$y = round($_POST['y'] * $_POST['rr'], 0);
	$w = round($_POST['w'] * $_POST['rr'], 0);
	$h = round($_POST['h'] * $_POST['rr'], 0);
	$crop_frame = array(
	    'x' => $x,
        'y' => $y,
        'width'  => $w,
        'height' => $h
    );

	$storage = C_Gallery_Storage::get_instance();

	// XXX NextGEN Legacy wasn't handling watermarks or reflections at this stage, so we're forcefully disabling them to maintain compatibility
	$params = array(
	    'watermark'  => false,
        'reflection' => false,
        'crop'       => true,
        'crop_frame' => $crop_frame
    );

	$result = $storage->generate_thumbnail($id, $params);

	if ($result) {
		echo "OK";
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		echo "KO";
	}

	C_NextGEN_Bootstrap::shutdown();
}

add_action('wp_ajax_rotateImage', 'ngg_rotateImage');

function ngg_rotateImage()
{
	if (!is_user_logged_in())
		die('-1');
	if (!current_user_can('NextGEN Manage gallery'))
		die('-1');

	require_once(dirname(dirname(__FILE__)) . '/ngg-config.php');
	require_once(dirname(__FILE__) . '/functions.php');

	$image_id  = (int) $_POST['id'];
	$direction = FALSE;
	$flip      = FALSE;
    $retval    = [];

	switch ($_POST['ra']) {
		case 'cw' :
		    $direction = 'CW';
		    break;
		case 'ccw' :
			$direction = 'CCW';
		    break;
		case 'fv' :
			// Note: H/V have been inverted here to make it more intuitive
            $direction = 0;
            $flip      = 'H';
		    break;
		case 'fh' :
			// Note: H/V have been inverted here to make it more intuitive
            $direction = 0;
            $flip      = 'V';
		break;
	}

    $result = nggAdmin::rotate_image($image_id, $direction, $flip);

    if ($result === '1')
    {
        nggAdmin::create_thumbnail($image_id);

        if (C_CDN_Providers::is_cdn_configured())
        {
            // TODO: new_image_url is known to be broken
            \ReactrIO\Background\Job::create(
                sprintf(__("Publishing rotated image #%d", 'nggallery'), $image_id),
                'cdn_publish_image',
                ['id' => $image_id, 'size' => 'all']
            )->save('cdn');
            $retval['cdn_enabled'] = TRUE;
            $retval['new_image_url'] = C_Gallery_Storage::get_instance()->get_cdn_url_for($image_id, 'full');
        }
        else {
            $retval['cdn_enabled'] = FALSE;
        }

        print json_encode($retval);
        die();
    }

	header('HTTP/1.1 500 Internal Server Error');
	die( $result );
}