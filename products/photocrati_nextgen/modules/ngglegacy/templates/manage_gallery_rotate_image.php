<?php
/**
 * @var int $id
 * @var string $preview_url
 */ ?>
<script>
    const message = jQuery('#ngg_legacy_manage_gallery_rotate_image_modal_footer_message');
    const image   = jQuery('#ngg_legacy_manage_gallery_rotate_image_modal_image');
    const figure  = jQuery('#ngg_legacy_manage_gallery_rotate_image_modal_image_wrapper figure');
    const button  = jQuery('#ngg_legacy_manage_gallery_rotate_image_modal_footer input');

    function rotateImage() {

        figure.addClass('refresh_in_progress');
        button.attr('disabled', true);

        jQuery.ajax({
            url: photocrati_ajax.url,
            type : "POST",
            data:  {
                action: 'ngglegacy_manage_gallery_rotate_image_backend',
                id: <?php echo $id ?>,
                ra: jQuery('input[name="ra"]:checked').val()
            },
            cache: false,
            success: function (response) {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                if (response.error) {
                    showMessage('<?php _e('Error rotating thumbnail', 'nggallery'); ?>', false)
                } else {
                    image.attr('src', response.image_url);
                    showMessage('<?php _e('Image rotated', 'nggallery'); ?>', true);
                }
            },
            error: function (msg, status, errorThrown) {
                showMessage('<?php _e('Error rotating thumbnail', 'nggallery'); ?>', false)
            }
        });
    }

    function showMessage(string, success) {
        message.html(string);
        message.css({'display': 'block'});

        figure.removeClass('refresh_in_progress');
        button.attr('disabled', false);

        setTimeout(function() {
            message.fadeOut('slow');
        }, 1500);
    }
</script>

<style>
    #ngg_legacy_manage_gallery_rotate_image_modal_wrapper,
    #ngg_legacy_manage_gallery_rotate_image_modal_wrapper * {
        box-sizing: border-box;
    }

    #ngg_legacy_manage_gallery_rotate_image_modal_wrapper {
        height: calc(100% - 100px);
        width: 100%;
        display: flex;
        flex-direction: row;
    }

    #ngg_legacy_manage_gallery_rotate_image_modal_image_wrapper {
        text-align: center;
        width: 100%;
        height: 100%;
    }

    #ngg_legacy_manage_gallery_rotate_image_modal_image_wrapper figure {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        position: relative;
    }

    #ngg_legacy_manage_gallery_rotate_image_modal_image_wrapper figure figcaption {
        opacity: 0;
        position: absolute;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, .7);
        color: white;
        justify-content: center;
        align-items: center;
        font-size: 6em;
        display: none;
    }

    #ngg_legacy_manage_gallery_rotate_image_modal_image_wrapper figure.refresh_in_progress figcaption {
        display: flex;
        opacity: 0.75;
        top: 0;
        left: 0;
    }

    #ngg_legacy_manage_gallery_rotate_image_modal_image {
        width: auto;
        height: auto;
        max-height: 100%;
        max-width: 100%;
    }

    #ngg_legacy_manage_gallery_rotate_image_modal_rotation_options_wrapper {
        width: 250px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        margin-left: 15px;
    }

    #ngg_legacy_manage_gallery_rotate_image_modal_footer {
        height: 100px;
        width: 100%;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        align-items: center;
    }

    #ngg_legacy_manage_gallery_rotate_image_modal_submit:disabled {
        color: black !important;
        background-color: lightgray !important;
    }

    #ngg_legacy_manage_gallery_rotate_image_modal_footer_message {
        text-align: center;
        color: #669933;
        font-size: 12px;
        margin-bottom: 15px;
    }
</style>

<div id="ngg_legacy_manage_gallery_rotate_image_modal_wrapper">

    <div id="ngg_legacy_manage_gallery_rotate_image_modal_image_wrapper">

        <figure>
            <img src="<?php echo nextgen_esc_url($preview_url); ?>"
                 alt=""
                 id="ngg_legacy_manage_gallery_rotate_image_modal_image"/>
            <figcaption><i class="fa fa-spin fa-spinner"></i></figcaption>
        </figure>
    </div>

    <div id="ngg_legacy_manage_gallery_rotate_image_modal_rotation_options_wrapper">
        <label>
            <input type="radio" name="ra" value="cw"/>
            <?php esc_html_e('90&deg; clockwise', 'nggallery'); ?>
        </label>

        <br/>
        <label>
            <input type="radio" name="ra" value="ccw"/>
            <?php esc_html_e('90&deg; counter-clockwise', 'nggallery'); ?>
        </label>

        <br/>
        <label>
            <input type="radio" name="ra" value="fv"/>
            <?php esc_html_e('Flip vertically', 'nggallery'); ?>
        </label>

        <br/>
        <label>
            <input type="radio" name="ra" value="fh"/>
            <?php esc_html_e('Flip horizontally', 'nggallery'); ?>
        </label>
    </div>

</div>

<div id="ngg_legacy_manage_gallery_rotate_image_modal_footer">
    <div id="ngg_legacy_manage_gallery_rotate_image_modal_footer_message"></div>
    <input id="ngg_legacy_manage_gallery_rotate_image_modal_submit"
           type="button"
           name="update"
           value="<?php esc_attr_e('Update', 'nggallery'); ?>"
           onclick="rotateImage()"
           class="button-primary"/>
</div>