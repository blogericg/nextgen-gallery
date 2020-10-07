<?php
/**
 * @var array $galleries
 * @var string $nonce
 * @var int $max_size
 * @var C_NextGen_Settings $settings
 */
?>
<div id="gallery_selection">

    <label for="gallery_id">
        <?php _e('Gallery', 'nggallery'); ?>
    </label>

    <select id="gallery_id" autocomplete="off">

        <option value="0">
            <?php _e('Create a new gallery', 'nggallery'); ?>
        </option>

        <?php foreach ($galleries as $gallery) { ?>
            <option value="<?php echo esc_attr($gallery->{$gallery->id_field}) ?>"
                    data-original-value="<?php print esc_attr($gallery->title); ?>">
                <?php print esc_attr(apply_filters('ngg_gallery_title_select_field', $gallery->title, $gallery, FALSE)); ?>
            </option>
        <?php } ?>
    </select>

    <input type="text"
           id="gallery_name"
           name="gallery_name"
           placeholder="<?php _e('Gallery title', 'nggallery'); ?>"
           autocomplete="off"/>

    <button id="gallery_create" disabled="disabled">
        <span id="ngg-create-gallery-default-text">
            <?php print __('Create &amp; select', 'nggallery'); ?>
        </span>
        <span id="ngg-create-gallery-waiting-text" class="hidden">
            <i class="fas fa-spinner fa-spin"></i>
            <?php print __('Creating...', 'nggallery'); ?>
        </span>
    </button>
</div>

<div id="uploader"></div>

<?php
$display_zips = (!is_multisite() || (is_multisite() && $settings->get('wpmuZipUpload')));
if ($display_zips)
    $message = __('Drag image and ZIP files here or %{browse}', 'nggallery');
else
    $message = __('Drag image files here or %{browse}', 'nggallery');


$max_size_megabytes = round((int)$max_size / (1024 * 1024));
$max_size_message = sprintf(__('You may select files up to %dMB', 'nggallery'), $max_size_megabytes);
?>

<script type="text/javascript">
    // Listen for events emitted in other frames
    if (window.Frame_Event_Publisher) {
        // If a gallery has been deleted, reload the page ~~remove it from the drop-downs of available galleries~~
        Frame_Event_Publisher.listen_for('attach_to_post:manage_galleries', () => {
            window.location.href = window.location.href;
        });
    }

    window.urlencode = function(str) {
        str = (str + '').toString();
        // Tilde should be allowed unescaped in future versions of PHP (as reflected below), but if you want to reflect current
        // PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
        return encodeURIComponent(str).replace(/!/g, '%21')
                                      .replace(/'/g, '%27')
                                      .replace(/\(/g, '%28')
                                      .replace(/\)/g, '%29')
                                      .replace(/\*/g, '%2A')
                                      .replace(/%20/g, '+');
    };

    // Sets the plupload url with necessary parameters in the QS
    window.set_plupload_url = function(gallery_id, gallery_name) {
        var qs = "&action=upload_image&gallery_id=" + urlencode(gallery_id);
        qs += "&gallery_name=" + urlencode(gallery_name);
        qs += "&nonce=" + urlencode("<?php echo $nonce; ?>");
        return photocrati_ajax.url + qs;
    };

    (function($) {
        $(function() {
            // Show the page content
            $('#ngg_page_content').css('visibility', 'visible');

            // Only execute this code once!
            const flag = 'addgallery';
            if (typeof($(window).data(flag)) == 'undefined') {
                $(window).data(flag, true);
            } else {
                return;
            }

            function update_frame_event_publisher() {
                if (window.Frame_Event_Publisher) {
                    $.post(photocrati_ajax.url,
                        {'action': 'cookie_dump'},
                        (response) => {
                            if (typeof(response) != 'object')
                                response = JSON.parse(response);
                            const events = {};
                            for (const name in response.cookies) {
                                if (name.indexOf('X-Frame-Events') !== -1) {
                                    const event_data = JSON.parse(response.cookies[name]);
                                    events[name] = event_data;
                                }
                            }
                            window.Frame_Event_Publisher.broadcast(events)
                        }
                    );
                }
            }

            const gallery_select = document.getElementById('gallery_id');
            const gallery_name   = document.getElementById('gallery_name');
            const gallery_create = document.getElementById('gallery_create');

            const uppy = Uppy.Core({
                locale: Uppy.locales[NggUploadImages_i18n.locale],
                restrictions: {
                    maxFileSize: <?php print esc_attr($max_size); ?>,
                    allowedFileTypes: ['image/gif', 'image/jpg', 'image/jpeg', 'image/pjpeg', 'image/png']
                },
                onBeforeUpload: (files) => {
                    gallery_select.disabled = true;
                    gallery_name.disabled   = true;
                    gallery_create.disabled = true;
                    return true;
                }
            }).use(Uppy.Dashboard, {
                    inline: true,
                    target: '#uploader',
                    width: '100%',
                    proudlyDisplayPoweredByUppy: false,
                    hideRetryButton: true,
                    note: "<?php print esc_attr($max_size_message); ?>",
                    locale: {
                        strings: {
                            dropPaste: "<?php print esc_attr($message); ?>"
                        }
                    }
            }).use(Uppy.XHRUpload, {
                endpoint: set_plupload_url(0, ''),
                fieldName: 'file',
                limit: 6,
                getResponseError: (text, response) => {
                    if ('string' === typeof text) {
                        text = JSON.parse(text);
                    }
                    return text.error;
                }
            }).on('file-added', (file) => {
                // If this is run right away the upload button won't yet exist and can't be found to disable it
                setTimeout(() => {
                    adjust_upload_button();
                }, 250);
            }).on('file-removed', (file, reason) => {
                if (reason === 'removed-by-user') {
                    adjust_upload_button();
                }
            }).on('complete', (result) => {
                // There was at least one error: remove the successful images so users can find out what went wrong
                if (result.failed.length > 0) {
                    uppy.getFiles().forEach((file) => {
                        if ('undefined' === typeof file.error) {
                            uppy.removeFile(file.id);
                        }
                    });
                } else {
                    // All uploads were a success; clear the board and reset the game
                    setTimeout(() => {
                        uppy.reset();
                    }, 2000);
                }

                const gallery_url = '<?php echo admin_url("/admin.php?page=nggallery-manage-gallery&mode=edit&gid=")?>' + gallery_select.value;
                const chosen_name = String(gallery_select.selectedOptions[0].dataset.originalValue);

                /** @var NggUploadImages_i18n object */
                let message = NggUploadImages_i18n.x_images_uploaded;
                if (result.successful.length === 0) {
                    message = NggUploadImages_i18n.no_image_uploaded;
                } else if (result.successful.length === 1) {
                    message = NggUploadImages_i18n.one_image_uploaded;
                }

                message = message + ' ' + NggUploadImages_i18n.manage_gallery;
                message = message.replace('{count}', String(result.successful.length))
                                 .replace('{name}', chosen_name);

                Toastify({
                    text: message,
                    duration: 180000,
                    destination: gallery_url,
                    newWindow: true,
                    close: true,
                    gravity: 'bottom',
                    position: 'right',
                    backgroundColor: 'black',
                    stopOnFocus: true,
                    onClick: function() {
                    }
                }).showToast();

                gallery_select.value = 0;
                gallery_name.value   = '';

                gallery_name.disabled   = false;
                gallery_select.disabled = false;
                gallery_create.disabled = true;

                gallery_name.classList.remove('hidden');
                gallery_create.classList.remove('hidden');
            });

            function adjust_upload_button() {
                const upload_btn = document.getElementsByClassName('uppy-StatusBar-actionBtn--upload')[0];
                if ('undefined' === typeof upload_btn) {
                    return;
                }
                if (parseInt(gallery_select.value) === 0) {
                    upload_btn.disabled = true;
                } else if ('undefined' !== typeof upload_btn) {
                    upload_btn.disabled = false;
                }
            }

            function set_XHR_endpoint(chosen_name) {
                if (!chosen_name) {
                    chosen_name = parseInt(gallery_select.value) === 0 ? gallery_name.value : gallery_select.selectedOptions[0].dataset.originalValue;
                }
                const endpoint = set_plupload_url(gallery_select.value, chosen_name)
                uppy.getPlugin('XHRUpload').setOptions({
                    endpoint: endpoint
                });
            }

            gallery_select.addEventListener('change', function() {
                set_XHR_endpoint();
                adjust_upload_button();

                if (parseInt(this.value) === 0) {
                    gallery_create.classList.remove('hidden');
                    gallery_name.classList.remove('hidden');
                } else {
                    gallery_create.classList.add('hidden');
                    gallery_name.classList.add('hidden');
                }
            });

            gallery_create.addEventListener('click', (event) => {
                event.preventDefault();

                // Prevent attempts to change the gallery while uploading
                gallery_create.disabled = true;

                // Display "Waiting..." while the button is disabled
                const waiting_text = document.getElementById('ngg-create-gallery-waiting-text');
                const default_text = document.getElementById('ngg-create-gallery-default-text');
                waiting_text.classList.remove('hidden');
                default_text.classList.add('hidden');

                const postData = new FormData();
                postData.append('action', 'create_new_gallery');
                postData.append('gallery_name', gallery_name.value);

                fetch(photocrati_ajax.url, {
                    method: 'POST',
                    body: postData
                }).then((result) => {
                    return result.json();
                }).then((data) => {
                    const option = document.createElement('option');

                    option.value = data.gallery_id;
                    option.text  = data.gallery_name;
                    option.dataset.originalValue = data.gallery_name;
                    gallery_select.add(option);
                    gallery_select.value = option.value;

                    gallery_name.value = '';
                    gallery_create.classList.add('hidden');
                    gallery_name.classList.add('hidden');

                    waiting_text.classList.add('hidden');
                    default_text.classList.remove('hidden');

                    set_XHR_endpoint();
                    adjust_upload_button();
                    update_frame_event_publisher();
                }).catch((error) => {
                    alert(error);
                });

            });

            gallery_name.addEventListener('keyup', () => {
                set_XHR_endpoint();
                gallery_create.disabled = gallery_name.value.length <= 0;
            });

            window.Frame_Event_Publisher.broadcast();

            let evtJq = $;
            if (window.top.jQuery)
                evtJq = window.top.jQuery;
            evtJq(window.top.document).find('body').trigger('nextgen_event', [ 'uppy_init' ]);
        });
    })(jQuery);
</script>