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
            ['ngg_marketing_cards_style'],
            NGG_SCRIPT_VERSION
        );
    }

    function get_page_title()
    {
        return __('Upgrade to Pro', 'nggallery');
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
     * @return C_Marketing_Card[]
     */
    public function get_marketing_blocks()
    {
        $retval = [];
        $retval[] = new C_Marketing_Card(
            'large',
            __('Automatic Print Lab', 'nggallery'),
            'https://enviragallery.com/wp-content/uploads/2015/10/dynamic-addon.png',
            __('Sell Photos on WordPress with Automated Print Fulfillment! NextGen Pro is the ONLY WordPress plugin with automatic print lab fulfillment. Ship prints directly to customers with WHCC, a leader pro print lab.', 'nggallery'),
            'addonspage',
            'printlab'
        );

        $retval[] = new C_Marketing_Card(
            'large',
            __('Pro Tiled Gallery', 'nggallery'),
            'https://enviragallery.com/wp-content/uploads/2015/10/dynamic-addon.png',
            __('A beautiful tiled gallery', 'nggallery'),
            'addonspage',
            'tiledgallery'
        );

        $retval[] = new C_Marketing_Card(
            'large',
            __('Pro Lightbox', 'nggallery'),
            'https://enviragallery.com/wp-content/uploads/2015/10/dynamic-addon.png',
            __('The NextGEN Pro Lightbox is the most powerful and flexible lightbox ever made for WordPress, with highly customizable design, image commenting, image social sharing, image deep linking, and more.', 'nggallery'),
            'addonspage',
            'prolightbox'
        );

        $retval[] = new C_Marketing_Card(
            'large',
            __('PayPal Checkout', 'nggallery'),
            'https://enviragallery.com/wp-content/uploads/2015/10/dynamic-addon.png',
            __('Complete ecommerce for NextGEN Gallery, including Stripe payments, PayPal payments, coupons, taxes, digital downloads, unlimited price lists, and more.', 'nggallery'),
            'addonspage',
            'paypal'
        );

        $retval[] = new C_Marketing_Card(
            'large',
            __('Coupons', 'nggallery'),
            'https://enviragallery.com/wp-content/uploads/2015/10/dynamic-addon.png',
            __('Complete ecommerce for NextGEN Gallery, including Stripe payments, PayPal payments, coupons, taxes, digital downloads, unlimited price lists, and more.', 'nggallery'),
            'addonspage',
            'coupons'
        );

        $retval[] = new C_Marketing_Card(
            'large',
            __('Automatic sales tax with TaxJar', 'nggallery'),
            'https://enviragallery.com/wp-content/uploads/2015/10/dynamic-addon.png',
            __('Complete ecommerce for NextGEN Gallery, including Stripe payments, PayPal payments, coupons, taxes, digital downloads, unlimited price lists, and more.', 'nggallery'),
            'addonspage',
            'salestax'
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
