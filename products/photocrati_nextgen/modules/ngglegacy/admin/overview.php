<?php

if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

class C_NGG_Admin_Overview
{

    /**
     * Shows important server configuration details. 
     * @author GamerZ (http://www.lesterchan.net)
     *
     * @return void
     */

    public function server_info()
    {
        global $wpdb, $ngg;
        
        // Get MYSQL Version
        $sqlversion = $wpdb->get_var("SELECT VERSION() AS version");
        
        // GET SQL Mode
        $mysqlinfo = $wpdb->get_results("SHOW VARIABLES LIKE 'sql_mode'");
        if (is_array($mysqlinfo)) $sql_mode = $mysqlinfo[0]->Value;
        if (empty($sql_mode)) $sql_mode = __('Not set', 'nggallery');
        
        // Get PHP Safe Mode
        if(ini_get('safe_mode')) $safe_mode = __('On', 'nggallery');
        else $safe_mode = __('Off', 'nggallery');
        
        // Get PHP allow_url_fopen
        if(ini_get('allow_url_fopen')) $allow_url_fopen = __('On', 'nggallery');
        else $allow_url_fopen = __('Off', 'nggallery');
        
        // Get PHP Max Upload Size
        if (function_exists('wp_max_upload_size')) $upload_max = strval(round((int) wp_max_upload_size() / (1024 * 1024))) . 'M';
        else if(ini_get('upload_max_filesize')) $upload_max = ini_get('upload_max_filesize');
        else $upload_max = __('N/A', 'nggallery');
        
        // Get PHP Output buffer Size
        if(ini_get('pcre.backtrack_limit')) $backtrack_limit = ini_get('pcre.backtrack_limit');
        else $backtrack_limit = __('N/A', 'nggallery');
        
        // Get PHP Max Post Size
        if(ini_get('post_max_size')) $post_max = ini_get('post_max_size');
        else $post_max = __('N/A', 'nggallery');
        
        // Get PHP Max execution time
        if(ini_get('max_execution_time')) $max_execute = ini_get('max_execution_time');
        else $max_execute = __('N/A', 'nggallery');
        
        // Get PHP Memory Limit
        if(ini_get('memory_limit')) $memory_limit = $ngg->memory_limit;
        else $memory_limit = __('N/A', 'nggallery');
        
        // Get actual memory_get_usage
        if (function_exists('memory_get_usage')) $memory_usage = round(memory_get_usage() / 1024 / 1024, 2) . __(' MByte', 'nggallery');
        else $memory_usage = __('N/A', 'nggallery');
        
        // required for EXIF read
        if (is_callable('exif_read_data')) $exif = __('Yes', 'nggallery'). " (V" . substr(phpversion('exif'),0,4) . ")" ;
        else $exif = __('No', 'nggallery');
        
        // required for meta data
        if (is_callable('iptcparse')) $iptc = __('Yes', 'nggallery');
        else $iptc = __('No', 'nggallery');
        
        // required for meta data
        if (is_callable('xml_parser_create')) $xml = __('Yes', 'nggallery');
        else $xml = __('No', 'nggallery');

        ?>
        <li><?php _e('Operating System', 'nggallery'); ?> : <span><?php echo PHP_OS; ?>&nbsp;(<?php echo (PHP_INT_SIZE * 8) ?>&nbsp;Bit)</span></li>
        <li><?php _e('Server', 'nggallery'); ?> : <span><?php echo $_SERVER["SERVER_SOFTWARE"]; ?></span></li>
        <li><?php _e('Memory usage', 'nggallery'); ?> : <span><?php echo $memory_usage; ?></span></li>
        <li><?php _e('MYSQL Version', 'nggallery'); ?> : <span><?php echo $sqlversion; ?></span></li>
        <li><?php _e('SQL Mode', 'nggallery'); ?> : <span><?php echo $sql_mode; ?></span></li>
        <li><?php _e('PHP Version', 'nggallery'); ?> : <span><?php echo PHP_VERSION; ?></span></li>
        <li><?php _e('PHP Safe Mode', 'nggallery'); ?> : <span><?php echo $safe_mode; ?></span></li>
        <li><?php _e('PHP Allow URL fopen', 'nggallery'); ?> : <span><?php echo $allow_url_fopen; ?></span></li>
        <li><?php _e('PHP Memory Limit', 'nggallery'); ?> : <span><?php echo $memory_limit; ?></span></li>
        <li><?php _e('PHP Max Upload Size', 'nggallery'); ?> : <span><?php echo $upload_max; ?></span></li>
        <li><?php _e('PHP Max Post Size', 'nggallery'); ?> : <span><?php echo $post_max; ?></span></li>
        <li><?php _e('PCRE Backtracking Limit', 'nggallery'); ?> : <span><?php echo $backtrack_limit; ?></span></li>
        <li><?php _e('PHP Max Script Execute Time', 'nggallery'); ?> : <span><?php echo $max_execute; ?>s</span></li>
        <li><?php _e('PHP Exif support', 'nggallery'); ?> : <span><?php echo $exif; ?></span></li>
        <li><?php _e('PHP IPTC support', 'nggallery'); ?> : <span><?php echo $iptc; ?></span></li>
        <li><?php _e('PHP XML support', 'nggallery'); ?> : <span><?php echo $xml; ?></span></li>
        <?php
    }

    /**
     * Show GD Library version information
     *
     * @return void
     */
    function gd_info()
    {
        if (function_exists("gd_info"))
        {
            $info = gd_info();
            $keys = array_keys($info);
            for ($i = 0; $i < count($keys); $i++) {
                if (is_bool($info[$keys[$i]]))
                    echo "<li> " . $keys[$i] . " : <span>" . ($info[$keys[$i]] ? __('Yes', 'nggallery') : __('No', 'nggallery')) . "</span></li>\n";
                else
                    echo "<li> " . $keys[$i] . " : <span>" . $info[$keys[$i]] . "</span></li>\n";
            }
        }
        else {
            echo '<h4>'.__('No GD support', 'nggallery').'!</h4>';
        }
    }

    // Display File upload quota on dashboard
    function dashboard_quota()
    {
        if (get_site_option('upload_space_check_disabled'))
            return;

        if (!wpmu_enable_function('wpmuQuotaCheck'))
            return;

        $settings = C_NextGen_Settings::get_instance();
        $fs = C_Fs::get_instance();
        $dir = $fs->join_paths($fs->get_document_root('content'), $settings->gallerypath);

        $quota = get_space_allowed();
        $used = get_dirsize($dir) / 1024 / 1024;

        if ($used > $quota)
            $percentused = '100';
        else
            $percentused = ($used / $quota) * 100;
        
        $used_color = ($percentused < 70) ? (($percentused >= 40) ? 'yellow' : 'green') : 'red';
        $used = round($used, 2);
        $percentused = number_format($percentused);

        ?>
        <p><?php print __('Storage Space'); ?></p>
        <ul>
            <li><?php printf(__('%1$sMB Allowed', 'nggallery'), $quota); ?></li>
            <li class="<?php print $used_color; ?>"><?php printf(__('%1$sMB (%2$s%%) Used', 'nggallery'), $used, $percentused); ?></li>
        </ul>
        <?php
    }

}

/**
 * nggallery_admin_overview()
 *
 * Add the admin overview the dashboard style
 * @return NULL
 */
function nggallery_admin_overview()
{
    $NGG_Admin_Overview = new C_NGG_Admin_Overview();

    global $wpdb;
    
    $action_status = array('message' => '', 'status' => 'ok');

    $images    = intval($wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggpictures"));
    $galleries = intval($wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggallery"));
    $albums    = intval($wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggalbum"));

    ?>

    <?php if ($action_status['message']!='') : ?>
        <div id="message" class="<?php echo ($action_status['status']=='ok' ? 'updated' : $action_status['status']); ?> fade">
            <p><strong><?php echo $action_status['message']; ?></strong></p>
        </div>
    <?php endif; ?>

    <div class="wrap about-wrap ngg_overview">

        <div class="ngg_page_content_header">
            <img src="<?php  echo(C_Router::get_instance()->get_static_url('photocrati-nextgen_admin#imagely_icon.png')); ?>"><h3><?php echo _e( 'Welcome to NextGEN Gallery', 'nggallery' ); ?></h3>
        </div>

        <div class="about-text" id="ngg-gallery-wizard"><span><?php echo __("Need help getting started? ", 'nggallery')?></span><?php echo ' <a data-ngg-wizard="nextgen.beginner.gallery_creation_igw" class="ngg-wizard-invoker button-primary" href="' . esc_url(add_query_arg('ngg_wizard', 'nextgen.beginner.gallery_creation_igw')) . '">' . __('Launch Gallery Wizard', 'nggallery') . '</a>'; ?>
        </div>

        <div class='ngg_page_content_menu'>
            <a href="javascript:void(0)" data-id="welcome-link"><?php _e( 'Welcome', 'nggallery' ); ?></a>
            <a href="javascript:void(0)" data-id="videos-link" style="display:none;"><?php _e( 'More Videos' ); ?></a>
            <?php
            $found = FALSE;
            if (defined('NEXTGEN_GALLERY_PRO_PLUGIN_BASENAME')
            ||  defined('NGG_PRO_PLUGIN_BASENAME')
            ||  defined('NGG_PLUS_PLUGIN_BASENAME'))
                $found = TRUE;
            if (!$found) { ?>
                <a href="javascript:void(0)" data-id="pro-link"><?php _e('Extensions'); ?></a>
                <a href="javascript:void(0)" data-id="ngg-vs-pro-link"><?php _e( 'NextGEN vs Pro'); ?></a>
            <?php } ?>
            <a href="javascript:void(0)" data-id="genesis-link"><?php _e( 'Imagely Themes'); ?></a>
            <a href="javascript:void(0)" data-id="ambassador-link"><?php _e( 'Ambassadors'); ?></a>
        </div>

        <div class='ngg_page_content_main'>

            <div data-id="welcome-link">

                <div class="about-text"><strong><?php printf( __( "Congrats! You're now running the most popular WordPress gallery plugin of all time.")) ?></strong><br><?php printf( __( "To get started, watch our two minute intro below or click the Gallery Wizard button above.")) ?>
                </div>

                <div class="headline-feature feature-video">
                    <iframe width="1050" height="590" src="https://www.youtube.com/embed/4Phvmm3etnw?rel=0" frameborder="0" allowfullscreen></iframe>
                </div>

            </div>

            <div data-id="videos-link" style="display:none;">

                <p class="about-text"><?php printf( __( 'We have a growing list of video tutorials to get you started. Watch some below or head over to <a href="%s" target="_blank">NextGEN Gallery University on YouTube</a> to see all available vidoes.', 'nggallery' ), esc_url( 'https://www.youtube.com/playlist?list=PL9cmsdHslD0vIcJjBggJ-XMjtwvqrRgoM' ) ); ?>
                </p>

                <div class="headline-feature feature-video">
                    <iframe width="1280" height="720" src="https://www.youtube.com/embed/4Phvmm3etnw?list=PL9cmsdHslD0vIcJjBggJ-XMjtwvqrRgoM" frameborder="0" allowfullscreen></iframe>
                </div>

                <div class="feature-section two-col">
                    <div class="col">
                        <div class="headline-feature feature-video">
                            <iframe width="1280" height="720" src="https://www.youtube.com/embed/h_HrKpkI90w?list=PL9cmsdHslD0vIcJjBggJ-XMjtwvqrRgoM" frameborder="0" allowfullscreen></iframe>
                        </div>
                    </div>
                    <div class="col">
                        <div class="headline-feature feature-video">
                            <iframe width="1280" height="720" src="https://www.youtube.com/embed/58u9AjMJqJM?list=PL9cmsdHslD0vIcJjBggJ-XMjtwvqrRgoM" frameborder="0" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>

                <div class="feature-section two-col">
                    <div class="col">
                        <div class="headline-feature feature-video">
                            <iframe width="1280" height="720" src="https://www.youtube.com/embed/OfxWM59fb_Y?list=PL9cmsdHslD0vIcJjBggJ-XMjtwvqrRgoM" frameborder="0" allowfullscreen></iframe>
                        </div>
                    </div>
                    <div class="col">
                        <div class="headline-feature feature-video">
                            <iframe width="1280" height="720" src="https://www.youtube.com/embed/BiFjDYIaZZw?list=PL9cmsdHslD0vIcJjBggJ-XMjtwvqrRgoM" frameborder="0" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>

                <p class="about-text"><?php printf( __( 'Want more? Head over to <a href="%s" target="_blank">NextGEN Gallery University on YouTube</a>.', 'nggallery' ), esc_url( 'https://www.youtube.com/playlist?list=PL9cmsdHslD0vIcJjBggJ-XMjtwvqrRgoM' ) ); ?>
                </p>

            </div>

            <div data-id="pro-link">
                <h2><?php _e( 'Upgrade to NextGEN Pro!' ); ?></h2>
                <p class="about-text"><span style="font-weight: bold;"><?php _e( 'The most powerful gallery system ever built for WordPress. ', 'nggallery' ); ?></span><br><?php _e( 'Gorgeous new gallery displays, image protection, full screen lightbox, commenting and social sharing for individual images, proofing, ecommerce, digital downloads, and more.', 'nggallery' ); ?></p>
                <p class="about-text"><a href='https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/?utm_source=ngg&utm_medium=ngguser&utm_campaign=ngpro' target='_blank' class="button-primary ngg-pro-upgrade"><?php _e( 'Get NextGEN Pro Now', 'nggallery' ); ?></a></p>
                <div class="feature-section">
                    <iframe src="https://www.youtube.com/embed/ePnYGQX0Lf8" frameborder="0" allowfullscreen></iframe>
                </div>
            </div>

            <div data-id="ngg-vs-pro-link" id="ngg-basic-vs-pro">
                <style>
                    #ngg-basic-vs-pro {
                        width: 100%;
                    }
                    #ngg_page_content #ngg-basic-vs-pro h2 {
                        margin-top: 0;
                    }
                    #ngg-basic-vs-pro table {
                        width: 100%;
                    }

                    #ngg-basic-vs-pro th {
                        text-align: left;
                        font-weight: 600;
                        font-size: 18px;
                        line-height: 36px;
                        padding: 20px 0 20px 30px;
                        border: 1px solid #DDDDDD;
                        border-bottom: none;
                        vertical-align: middle;
                        background-color: rgb(241,241,241);
                    }

                    #ngg-basic-vs-pro td {
                        border: 1px solid #DDDDDD;
                        padding: 30px;
                        vertical-align: top;
                        width: 33%;
                        font-size: 18px;
                        line-height: 24px;
                    }

                    #ngg-basic-vs-pro th:nth-of-type(2),
                    #ngg-basic-vs-pro td:nth-of-type(2) {
                        border-left: none;
                        border-right: none;
                    }

                    #ngg-basic-vs-pro td:nth-of-type(2) {
                        background-color: rgb(243, 249, 254);
                    }

                    #ngg-basic-vs-pro td p {
                        margin: 0;
                        padding: 0;
                        font-size: 18px;
                        line-height: 24px;
                    }

                    #ngg-basic-vs-pro td i {
                        margin-right: 3px;
                    }
                    #ngg-basic-vs-pro i.ngg-features-none {
                        color: gray;
                    }
                    #ngg-basic-vs-pro i.ngg-features-full {
                        color: rgb(158, 188, 27);
                    }

                    #ngg-basic-vs-pro table h1, #ngg-basic-vs-pro table h2,
                    #ngg-basic-vs-pro table h3, #ngg-basic-vs-pro table h4,
                    #ngg-basic-vs-pro table h5, #ngg-basic-vs-pro table h6 {
                        margin: 0;
                        padding: 0;
                    }
                </style>
                <h2><?php print __('NextGEN Basic vs Pro'); ?></h2>

                <p>
                    <?php print __('Get the most out of NextGEN Gallery by upgrading to Pro and unlocking all the powerful features.', 'nggallery'); ?>
                </p>

                <?php
                M_Marketing::enqueue_blocks_style();
                $stupid = [
                    __('Interface', 'nggallery') => [
                        __('WordPress Block',       'nggallery') => [TRUE,  TRUE, TRUE],
                        __('Gallery Management',    'nggallery') => [TRUE,  TRUE, TRUE],
                        __('Drag and Drop Sorting', 'nggallery') => [TRUE,  TRUE, TRUE],
                        __('Default Settings',      'nggallery') => [TRUE,  TRUE, TRUE],
                        __('Responsive Galleries',  'nggallery') => [TRUE,  TRUE, TRUE],
                        __('Hover Captions',        'nggallery') => [FALSE, TRUE, TRUE],
                        __('Pagination',            'nggallery') => [TRUE,  TRUE, TRUE],
                        __('Lazy Loading',          'nggallery') => [FALSE, TRUE, TRUE],
                        __('Infinite Scroll',       'nggallery') => [FALSE, TRUE, TRUE],
                        __('Tagging/Keywords',      'nggallery') => [TRUE,  TRUE, TRUE],
                        __('Dynamic Galleries',     'nggallery') => [TRUE,  TRUE, TRUE],
                        __('Custom CSS',            'nggallery') => [TRUE,  TRUE, TRUE],
                        __('Breadcrumbs',           'nggallery') => [TRUE,  TRUE, TRUE]
                    ],
                    __('Image Upload / Processing', 'nggallery') => [
                        __('Drag and Drop Uploader', 'nggallery') => [TRUE,  TRUE,  TRUE],
                        __('Zip Importer',           'nggallery') => [TRUE,  TRUE,  TRUE],
                        __('Folder Scanning',        'nggallery') => [TRUE,  TRUE,  TRUE],
                        __('Lightroom Plugin',       'nggallery') => [FALSE, FALSE, TRUE]
                    ],
                    __('Gallery Types', 'nggallery') => [
                        __('Thumbnail Gallery',     'nggallery') => [TRUE,  TRUE, TRUE],
                        __('Image Browser Gallery', 'nggallery') => [TRUE,  TRUE, TRUE],
                        __('Slideshow Gallery',     'nggallery') => [TRUE,  TRUE, TRUE],
                        __('Filmstrip Gallery',     'nggallery') => [FALSE, TRUE, TRUE],
                        __('Masonry Gallery',       'nggallery') => [FALSE, TRUE, TRUE],
                        __('Mosaic Gallery',        'nggallery') => [FALSE, TRUE, TRUE],
                        __('Tiled Gallery',         'nggallery') => [FALSE, TRUE, TRUE],
                        __('Film Gallery',          'nggallery') => [FALSE, TRUE, TRUE],
                        __('Blogstyle Gallery',     'nggallery') => [FALSE, TRUE, TRUE],
                        __('Grid Album',            'nggallery') => [TRUE,  TRUE, TRUE],
                        __('List Album',            'nggallery') => [TRUE,  TRUE, TRUE]
                    ],
                    __('Ecommerce', 'nggallery') => [
                        __('Ecommerce',                   'nggallery') => [FALSE, FALSE, TRUE],
                        __('Paid Digital Downloads',      'nggallery') => [FALSE, FALSE, TRUE],
                        __('Coupons',                     'nggallery') => [FALSE, FALSE, TRUE],
                        __('Price Lists',                 'nggallery') => [FALSE, FALSE, TRUE],
                        __('Automated Tax Calculations',  'nggallery') => [FALSE, FALSE, TRUE],
                        __('Automated Print Fulfillment', 'nggallery') => [FALSE, FALSE, TRUE],
                        __('Proofing',                    'nggallery') => [FALSE, FALSE, TRUE]
                    ],
                    __('Other', 'nggallery') => [
                        __('Digital Downloads',         'nggallery') => [FALSE, FALSE, TRUE],
                        __('Watermarking',              'nggallery') => [TRUE,  TRUE,  TRUE],
                        __('Image Protection',          'nggallery') => [FALSE, TRUE,  TRUE],
                        __('Multiple Lightbox Choices', 'nggallery') => [TRUE,  TRUE,  TRUE],
                        __('Image Commenting',          'nggallery') => [FALSE, TRUE,  TRUE],
                        __('Image Deeplinking',         'nggallery') => [FALSE, TRUE,  TRUE],
                        __('Full-Screen Lightbox',      'nggallery') => [FALSE, TRUE,  TRUE],
                        __('Image Social Sharing',      'nggallery') => [FALSE, TRUE,  TRUE],
                        __('Lightbox Slideshow',        'nggallery') => [FALSE, TRUE,  TRUE]
                    ]
                ]; ?>

                <table cellspacing="0" cellpadding="0" border="0">
                    <thead>
                        <tr>
                            <th>
                                <h2><?php print __('NextGEN Gallery', 'nggallery'); ?></h2>
                                <div class="wp-block-buttons">
                                    <div class="wp-block-button">
                                        <a class="wp-block-button__link has-background no-border-radius"
                                           href="https://wordpress.org/plugins/nextgen-gallery/"
                                           style="background-color:#9ebc1b; color: white"
                                           target="_blank"
                                           rel="noreferrer noopener">
                                            <?php print __('Download Now', 'nggallery'); ?>
                                        </a>
                                    </div>
                                </div>
                            </th>
                            <th>
                                <h2><?php print __('NextGEN Plus', 'nggallery'); ?></h2>

                                <div class="wp-block-buttons">
                                    <div class="wp-block-button">
                                        <a class="wp-block-button__link has-background no-border-radius"
                                           href="https://www.imagely.com/wordpress-gallery-plugin/nextgen-plus/#pricing"
                                           style="background-color:#9ebc1b; color: white"
                                           target="_blank"
                                           rel="noreferrer noopener">
                                            Buy NextGEN Plus
                                        </a>
                                    </div>
                                </div>
                            </th>
                            <th>
                                <h2><?php print __('NextGEN Pro', 'nggallery'); ?></h2>
                                <div class="wp-block-buttons">
                                    <div class="wp-block-button">
                                        <a class="wp-block-button__link has-background no-border-radius"
                                           href="https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/#pricing"
                                           target="_blank"
                                           rel="noreferrer noopener"
                                           style="background-color:#9ebc1b; color: white">
                                            <?php print __('Buy NextGEN Pro', 'nggallery'); ?>
                                        </a>
                                    </div>
                                </div>

                            </th>
                        </tr>
                    </thead>

                    <?php foreach ($stupid as $block_label => $block) { ?>
                        <tbody>
                            <tr>
                                <td><h3><?php print $block_label; ?></h3></td>
                                <td><h3><?php print $block_label; ?></h3></td>
                                <td><h3><?php print $block_label; ?></h3></td>
                            </tr>
                            <?php foreach ($block as $label => $supports) { ?>
                                <tr>
                                    <td>
                                        <?php if ($supports[0]) { ?>
                                            <i class="fa fa-check ngg-features-full"></i>
                                        <?php } else { ?>
                                            <i class="fa fa-times ngg-features-none"></i>
                                        <?php } ?>
                                        <?php print $label; ?>
                                    </td>
                                    <td>
                                        <?php if ($supports[1]) { ?>
                                            <i class="fa fa-check ngg-features-full"></i>
                                        <?php } else { ?>
                                            <i class="fa fa-times ngg-features-none"></i>
                                        <?php } ?>
                                        <?php print $label; ?>
                                    </td>
                                    <td>
                                        <?php if ($supports[2]) { ?>
                                            <i class="fa fa-check ngg-features-full"></i>
                                        <?php } else { ?>
                                            <i class="fa fa-times ngg-features-none"></i>
                                        <?php } ?>
                                        <?php print $label; ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    <?php } ?>

                    <tfoot>
                        <tr>
                            <th>
                                <div class="wp-block-buttons">
                                    <div class="wp-block-button">
                                        <a class="wp-block-button__link has-background no-border-radius"
                                           href="https://wordpress.org/plugins/nextgen-gallery/"
                                           style="background-color:#9ebc1b; color: white"
                                           target="_blank"
                                           rel="noreferrer noopener">
                                            <?php print __('Download Now', 'nggallery'); ?>
                                        </a>
                                    </div>
                                </div>
                            </th>
                            <th>
                                <div class="wp-block-buttons">
                                    <div class="wp-block-button">
                                        <a class="wp-block-button__link has-background no-border-radius"
                                           href="https://www.imagely.com/wordpress-gallery-plugin/nextgen-plus/#pricing"
                                           style="background-color:#9ebc1b; color: white"
                                           target="_blank"
                                           rel="noreferrer noopener">
                                            Buy NextGEN Plus
                                        </a>
                                    </div>
                                </div>
                            </th>
                            <th>
                                <div class="wp-block-buttons">
                                    <div class="wp-block-button">
                                        <a class="wp-block-button__link has-background no-border-radius"
                                           href="https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/#pricing"
                                           target="_blank"
                                           rel="noreferrer noopener"
                                           style="background-color:#9ebc1b; color: white">
                                            <?php print __('Buy NextGEN Pro', 'nggallery'); ?>
                                        </a>
                                    </div>
                                </div>

                            </th>
                        </tr>
                    </tfoot>
                </table>

            </div>

            <div data-id="genesis-link">

                <h2><?php _e( 'WordPress Themes for Photographers by Imagely' ); ?></h2>
                <p class="about-text"><?php _e( 'Meet the new series of Genesis child themes by Imagely: gorgeous, responsive image-centric themes for photographers or anyone with visually rich websites.', 'nggallery' ); ?></p>
                <h3 class="about-text"><?php _e( 'CLICK TO LEARN MORE:', 'nggallery' ); ?></h3>
                <div class="feature-section two-col">
                        <div class="col">
                            <a href="https://www.imagely.com/wordpress-photography-themes/ansel/?utm_source=ngg&utm_medium=ngguser&utm_campaign=imagelytheme" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/ansel-ngg.jpg" class="ngg-theme-image"></a>
                        </div>
                        <div class="col">
                            <a href="https://www.imagely.com/wordpress-photography-themes/fearless/?utm_source=ngg&utm_medium=ngguser&utm_campaign=imagelytheme" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/fearless-ngg.jpg" class="ngg-theme-image"></a>
                        </div>
                </div>
                <div class="feature-section two-col">
                        <div class="col">
                            <a href="https://www.imagely.com/wordpress-photography-themes/blush/?utm_source=ngg&utm_medium=ngguser&utm_campaign=imagelytheme" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/blush-ngg.jpg" class="ngg-theme-image"></a>
                        </div>
                        <div class="col">
                            <a href="https://www.imagely.com/wordpress-photography-themes/free-spirit/?utm_source=ngg&utm_medium=ngguser&utm_campaign=imagelytheme" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/free-spirit-ngg.jpg" class="ngg-theme-image"></a>
                        </div>
                </div>
                <div class="feature-section two-col">
                        <div class="col">
                            <a href="https://www.imagely.com/wordpress-photography-themes/reportage/?utm_source=ngg&utm_medium=ngguser&utm_campaign=imagelytheme" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/reportage-ngg.jpg" class="ngg-theme-image"></a>
                        </div>
                        <div class="col">
                            <a href="https://www.imagely.com/wordpress-photography-themes/lightly/?utm_source=ngg&utm_medium=ngguser&utm_campaign=imagelytheme" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/lightly-ngg.jpg" class="ngg-theme-image"></a>
                        </div>
                </div>
                <div class="feature-section two-col">
                        <div class="col">
                            <a href="https://www.imagely.com/wordpress-photography-themes/rebel/?utm_source=ngg&utm_medium=ngguser&utm_campaign=imagelytheme" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/rebel-ngg.jpg" class="ngg-theme-image"></a>
                        </div>
                        <div class="col">
                            <a href="https://www.imagely.com/wordpress-photography-themes/summerly/?utm_source=ngg&utm_medium=ngguser&utm_campaign=imagelytheme" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/summerly-ngg.jpg" class="ngg-theme-image"></a>
                        </div>
                </div>
                <div class="feature-section two-col">
                        <div class="col">
                            <a href="https://www.imagely.com/wordpress-photography-themes/expedition/?utm_source=ngg&utm_medium=ngguser&utm_campaign=imagelytheme" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/expedition-ngg.jpg" class="ngg-theme-image"></a>
                        </div>
                        <div class="col">
                            <a href="https://www.imagely.com/wordpress-photography-themes/punk-bride/?utm_source=ngg&utm_medium=ngguser&utm_campaign=imagelytheme" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/punk-bride-ngg.jpg" class="ngg-theme-image"></a>
                        </div>
                </div>
                <div class="feature-section two-col">
                        <div class="col">
                            <a href="https://www.imagely.com/wordpress-photography-themes/journal/?utm_source=ngg&utm_medium=ngguser&utm_campaign=imagelytheme" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/journal-ngg.jpg" class="ngg-theme-image"></a>
                        </div>
                        <div class="col">
                            <a href="https://www.imagely.com/wordpress-photography-themes/simplicity/?utm_source=ngg&utm_medium=ngguser&utm_campaign=imagelytheme" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/simplicity-ngg.jpg" class="ngg-theme-image"></a>
                        </div>
                </div>
                
            </div>

            <div data-id="ambassador-link">

                <div class="feature-section three-col">
                    <h2><?php _e( 'Meet the Imagely Product Ambassadors', 'nggallery' ); ?></h2>
                    <p class="about-text"><?php _e( "NextGEN Gallery and other Imagely products are used by some of the best photographers in the world. Meet some of the Imagely Ambassadors who are putting Imagely and NextGEN Gallery to work professionally.", 'nggallery' ); ?>
                    </p>
                    <div class="col">
                        <a href="https://www.imagely.com/team-member/the-youngrens/?utm_source=ngg&utm_medium=ngguser&utm_campaign=ambassador" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/theyoungrens-ngg.jpg" alt="The Youngrens"/></a>
                        <h3><?php _e( 'The Youngrens' ); ?></h3>
                        <p><?php _e( 'Jeff and Erin are a luxury husband and wife photography team who deeply love each other and their photography clients. They shoot weddings and engagements all over the U.S. and beyond. With three photography businesses that serve different clientele, they have unique insights into business strategies and are passionate about improving the day to day lives of other photographers.', 'nggallery' ); ?></p>
                    </div>
                    <div class="col">
                        <a href="https://www.imagely.com/team-member/tamara-lackey/?utm_source=ngg&utm_medium=ngguser&utm_campaign=ambassador" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/TamaraLackey-ngg.jpg" alt="Tamara Lackey" /></a>
                        <h3><?php _e( 'Tamara Lackey' ); ?></h3>
                        <p><?php _e( 'Tamara Lackey is a renowned professional photographer, speaker, and author. Her authentic lifestyle photography, from children’s portraits to celebrity portraits, is praised within her industry and published internationally. She is a Nikon USA Ambassador, the host of The reDefine Show web series, and the co-founder of the non-profit charitable organization, Beautiful Together, in support of children waiting for families.', 'nggallery' ); ?></p>
                    </div>
                    <div class="col">
                        <a href="https://www.imagely.com/team-member/colby-brown/?utm_source=ngg&utm_medium=ngguser&utm_campaign=ambassador" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/colby-brown-ngg.jpg" alt="Colby Brown" /></a>
                        <h3><?php _e( 'Colby Brown' ); ?></h3>
                        <p><?php _e( 'Colby is a photographer, photo educator, and author specializing in landscape, travel and humanitarian photography. With an audience reaching millions, Colby partners on social influencer marketing campaigns with some of the biggest companies and destinations in the world, including Sony, Samsung, Toshiba, Iceland Naturally, Jordan Tourism Board, Australia.com, Visit California and more.', 'nggallery' ); ?></p>
                    </div>
                </div>

                <div class="feature-section three-col">
                    <div class="col">
                        <a href="https://www.imagely.com/team-member/jared-platt/?utm_source=ngg&utm_medium=ngguser&utm_campaign=ambassador" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/jared-platt-ngg.jpg" alt="Jared Platt" /></a>
                        <h3><?php _e( 'Jared Platt' ); ?></h3>
                        <p><?php _e( 'Jared is a professional wedding and lifestyle photographer. He also travels the world giving lectures and workshops on photography, lighting, and post-production efficiency and workflow. His interactive style, and attention to detail and craft make him an entertaining and demanding photography instructor.', 'nggallery' ); ?></p>
                    </div>
                    <div class="col">
                        <a href="https://www.imagely.com/team-member/brian-matiash/?utm_source=ngg&utm_medium=ngguser&utm_campaign=ambassador" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/brian-matiash-ngg.jpeg" alt="" /></a>
                        <h3><?php _e( 'Brian Matiash' ); ?></h3>
                        <p><?php _e( 'Brian is a professional photographer, author, and educator. He fuses landscape & travel photography with experiential storytelling and practical instructing to help others grow creatively. He is also a Sony Artisan of Imagery, a Zeiss Lens Ambassador, a Formatt-Hitech Featured Photographer, and a member of G-Technology’s G-Team.', 'nggallery' ); ?></p>
                    </div>
                    <div class="col">
                        <a href="https://www.imagely.com/team-member/christine-tremoulet/?utm_source=ngg&utm_medium=ngguser&utm_campaign=ambassador" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/Christine-Tremoulet-ngg.jpg" alt="" /></a>
                        <h3><?php _e( 'Christine Tremoulet' ); ?></h3>
                        <p><?php _e( 'Christine famously coined the term WordPress. She is an author, speaker, business coach, and story strategist who specializes in helping creatives celebrate their story online through blogging and social media. When not offering actionable know-how to businesses, she can be found taking long road trips across North America in her Mini Cooper.', 'nggallery' ); ?></p>
                    </div>
                </div>

                <div class="feature-section three-col">
                    <div class="col">
                        <a href="https://www.imagely.com/team-member/david-beckstead/?utm_source=ngg&utm_medium=ngguser&utm_campaign=ambassador" target="_blank"><img src="https://f001.backblaze.com/file/nextgen-gallery/david-beckstead-ngg.jpg" alt="David Beckstead" /></a>
                        <h3><?php _e( 'David Beckstead' ); ?></h3>
                        <p><?php _e( 'Named one of the Top 10 Wedding Photographers in the World by American Photo magazine, David is a celebrated photographer and educator. He is also a mountain man with a enviable lifestyle: from his base in rural Washington, he travels all over the world teaching workshops, while sharing lessons with 16,000 photographers in the Abstract Canvas Facebook group.', 'nggallery' ); ?></p>
                    </div>
                    <div class="col"></div>
                    <div class="col"></div>
                </div>

            </div>

        </div> <!-- /.ngg_page_content_main -->
    
    </div> <!-- /.wrap -->

    <?php
    return NULL;
}