<?php

/**
 * @property C_NextGen_Admin_Page_Controller|A_NextGen_Pro_Upgrade_Controller $object
 */
class A_NextGen_Pro_Upgrade_Controller extends Mixin
{
    function enqueue_backend_resources()
    {
        $this->call_parent('enqueue_backend_resources');
        wp_enqueue_style(
            'nextgen_pro_upgrade_page',
            $this->get_static_url('photocrati-nextgen_pro_upgrade#style.css'),
            ['ngg_marketing_blocks_style'],
            NGG_SCRIPT_VERSION
        );
    }

    function get_page_title()
    {
        return __('Extensions', 'nggallery');
    }

    function get_required_permission()
    {
        return 'NextGEN Change options';
    }

    function get_i18n_strings()
    {
        $i18n = new stdClass();
        $i18n->page_title = $this->object->get_page_title();

        return $i18n;
    }

    /**
     * @return C_Marketing_Block_Card[]
     */
    public function get_marketing_blocks()
    {
        $retval = [];
        $retval[] = new C_Marketing_Block_Card(
            __('Ecommerce', 'nggallery'),
            __('Want to sell your images directly in your WordPress site? NextGEN Pro adds a true image ecommerce system to help you do just that. Accept PayPal, Stripe, and Checks with ease!', 'nggallery'),
            'upgradetopro',
            'ecommerce'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Proofing', 'nggallery'),
            __('Want to add a photo proofing process in your WordPress photo galleries? NextGEN Pro adds photo proofing into WordPress to help with your client workflow. The proofing option makes it easy for you and your clients from start to finish.', 'nggallery'),
            'upgradetopro',
            'proofing'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Print Fulfillment', 'nggallery'),
            __('Sell Photos on WordPress with Automated Print Fulfillment! NextGEN Pro is the only WordPress plugin with automatic print lab fulfillment. Ship prints direct to customers with industry-leading professional print labs with zero commissions off the top.', 'nggallery'),
            'upgradetopro',
            'printfulfillment'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Auto Tax Calculations', 'nggallery'),
            __('We have integrated NextGEN Pro with Taxjar to calculate accurate sales tax no matter where you and your clients are in the world.', 'nggallery'),
            'upgradetopro',
            'autotaxcalculations'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Pricelists', 'nggallery'),
            __('When selling images, the only way to do it is with pricelists. No more applying individual products t individual images. Do it the right way, and the easy way, by connecting pricelists to your galleries.', 'nggallery'),
            'upgradetopro',
            'pricelists'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Coupons', 'nggallery'),
            __('NextGEN Pro makes it easy to offer exclusive discounts to your clients.', 'nggallery'),
            'upgradetopro',
            'coupons'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Lightroom Plugin', 'nggallery'),
            __('The Lightroom plugin for NextGEN Gallery allows you to automatically create and sync photo galleries from your Adobe Lightroom collections in WordPress.', 'nggallery'),
            'upgradetopro',
            'lightroomplugin'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Tiled Gallery', 'nggallery'),
            __('With this stunning display type, you can present your images large with no trouble. Choose the maximum width of the gallery, or let it automate. It will adjust incredibly on all devices.', 'nggallery'),
            'upgradetopro',
            'tiledgallery'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Mosaic Gallery', 'nggallery'),
            __('With this stunning display type, you can present your images in a flexible grid. Choose the maximum height for your rows, and their margins, or use the default settings. It will adjust incredibly on all devices.', 'nggallery'),
            'upgradetopro',
            'mosaicgallery'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Masonry Gallery', 'nggallery'),
            __('With this stunning display type, you can present your images in a flexible grid. Choose the maximum width for your images, and their padding, or use the default settings. It will adjust incredibly on all devices.', 'nggallery'),
            'upgradetopro',
            'masonrygallery'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Filmstrip Gallery', 'nggallery'),
            __('With this stunning display type, you can present your images in a flexible grid. Choose the maximum height for your rows, and their margins, or use the default settings. It will adjust incredibly on all devices.', 'nggallery'),
            'upgradetopro',
            'filmstripgallery'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Film Gallery', 'nggallery'),
            __('Want to sell your images directly in your WordPress site? NextGEN Pro adds a true image ecommerce system to help you do just that. Accept PayPal, Stripe, and Checks with ease!', 'nggallery'),
            'upgradetopro',
            'filmgallery'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Blog Style Gallery', 'nggallery'),
            __('An entire gallery in a single vertical column. that requires scrolling vertically for engagement.', 'nggallery'),
            'upgradetopro',
            'blogstylegallery'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Sidescroll Gallery', 'nggallery'),
            __('An entire gallery in a single horizontal row, that requires scrolling sideways for engagement.', 'nggallery'),
            'upgradetopro',
            'sidescrollgallery'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Pro Lightbox', 'nggallery'),
            __('The Pro Lightbox allows you to display images at full scale when opened. Your visitors will enjoy breathtaking views of your photos on any device. It\'s customizable, from colors to padding and more. Offer social sharing, deep linking, and individual image commenting. Turn your gallery lightbox view into a slideshow for your visitors. You can customize settings such as auto-playing and slideshow speed.', 'nggallery'),
            'upgradetopro',
            'prolightbox'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Social Sharing', 'nggallery'),
            __('Your images automatically optimized for Open Graph and Twitter Cards and individually shareable from within the Pro Lightbox.', 'nggallery'),
            'upgradetopro',
            'socialsharing'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Image Commenting', 'nggallery'),
            __('Deep engagement for your images through individual image commenting within the Pro Lightbox', 'nggallery'),
            'upgradetopro',
            'imagecommenting'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Image Protection', 'nggallery'),
            __('Image protection disables the ability for visitors to right-click or drag to download your images in both the gallery display and Pro Lightbox views. It gives you complete freedom to display your work without worry. You can also choose to protect all images sitewide, even outside of NextGEN Gallery.', 'nggallery'),
            'upgradetopro',
            'imageprotection'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Deep Linking', 'nggallery'),
            __('Link directly to any image on your site with deep linking. Available exclusively in the Pro Lightbox.', 'nggallery'),
            'upgradetopro',
            'deeplinking'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Frontend Search', 'nggallery'),
            __('With this stunning display type, you can offer image searching capabilities with smart, powerful filtering based on the tags specified for images in your galleries.', 'nggallery'),
            'upgradetopro',
            'frontendsearch'
        );
        $retval[] = new C_Marketing_Block_Card(
            __('Hover Captions', 'nggallery'),
            __('Gain the ability to add hover capability to galleries. The hover feature includes social sharing, titles, and descriptions. Each can be controlled individually. The hover feature also includes multiple effects, like fade and slide up.', 'nggallery'),
            'upgradetopro',
            'hovercaptions'
        );

        // This block is used just to have an even number of cards so the two-column layout doesn't get
        // stretched at the very end of the block listing
        $retval[] = new C_Marketing_Block_Card(
            '',
            '',
            '',
            ''
        );

        return $retval;
    }

    function index_action()
    {
        $this->object->enqueue_backend_resources();

        $router   = C_Router::get_instance();
        $template = 'photocrati-nextgen_pro_upgrade#upgrade';

        print $this->object->render_view(
            $template,
            [
                'i18n'             => $this->get_i18n_strings(),
                'header_image_url' => $router->get_static_url('photocrati-nextgen_admin#imagely_icon.png'),
                'marketing_blocks' => $this->object->get_marketing_blocks()
            ],
            TRUE
        );
    }
}
