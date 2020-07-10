<?php

class A_Marketing_Admin_MVC_Injector extends Mixin
{
    protected static $block_one_cache = NULL;

    function render_object()
    {

        $root_element = $this->call_parent('render_object');

        // Unfortunately there just doesn't seem to be a better way at this moment to determine when to inject these
        // call-to-action blocks by context and we must rely on inspecting the URL for now.
        global $pagenow;

        if ($pagenow === 'admin.php' && !empty($_GET['page']) && $_GET['page'] === 'ngg_addgallery') {

            wp_enqueue_style('wp-block-library');
            wp_enqueue_style('ngg_marketing_blocks_style');

            foreach ($root_element->find('admin_page.content_main_form', TRUE) as $container) {
                if (!empty(self::$block_one_cache))
                {
                    $container->append(self::$block_one_cache);
                }
                else {
                    $title = "Want to make your gallery workflow and presentation even better?";
                    $description = "By upgrading to NextGEN Pro, you can get access to numerous other features, including:";
                    $links = [[
                        ['title' => 'Ecommerce',                   'href' => "https://www.imagely.com/wordpress-gallery-plugin/pro-ecommerce-demo"],
                        ['title' => 'Automated Print Fulfillment', 'href' => "https://www.imagely.com/sell-photos-wordpress/"],
                        ['title' => 'Automated Tax Calculation',   'href' => "https://www.imagely.com/sell-photos-wordpress/"],
                        ['title' => 'Additional Gallery Displays', 'href' => "https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/#features"],
                        ['title' => 'Additional Album Displays',   'href' => "https://www.imagely.com/wordpress-gallery-plugin/nextgen-pro/#features"]
                    ], [
                        ['title' => 'Image Proofing',    'href' => "https://www.imagely.com/wordpress-gallery-plugin/pro-proofing-demo"],
                        ['title' => 'Image Protection',  'href' => "https://www.imagely.com/docs/turn-image-protection/"],
                        ['title' => 'Pro Lightbox',      'href' => "https://www.imagely.com/wordpress-gallery-plugin/pro-lightbox-demo"],
                        ['title' => 'Digital Downloads', 'href' => "https://www.imagely.com/wordpress-gallery-plugin/digital-download-demo/"],
                        "Dedicated customer support and so much more!"
                    ]];
                    $footer   = "<strong>Bonus:</strong> NextGEN Gallery users get a discount code for 30% off regular price.";
                    $campaign = 'clickheretoupgrade';
                    $source   = 'galleryworkflow';

                    $block = new C_Marketing_Block_Two_Columns($title, $description, $links, $footer, $campaign, $source);
                    $container->append($block->render());
                }
            }
        }

        return $root_element;
    }
}