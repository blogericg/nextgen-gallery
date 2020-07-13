<?php

/**
 * @mixin C_Form
 * @property C_MVC_Controller $object
 */
class A_Marketing_Display_Settings_Form extends Mixin_Display_Type_Form
{
    function get_display_type_name()
    {
        return 'photocrati-marketing_fake_tile';
    }

    function get_title()
    {
        $context = $this->get_context();
        switch ($context) {
            case 'tile':
                return __('NextGEN Pro Tile', 'nggallery');
            case 'mosaic':
                return __('NextGEN Pro Mosaic', 'nggallery');
            case 'masonry':
                return __('NextGen Pro Masonry', 'nggallery');
            default:
                return '';
        }
    }

    function _get_field_names()
    {
        return [
            'marketing_block'
        ];
    }

    public function get_context()
    {
        return str_replace('photocrati-marketing_display_settings_', '', $this->object->context);
    }

    public function _render_marketing_block_field($thing)
    {
        $context = $this->get_context();
        $footer = __('<strong>Bonus:</strong> NextGEN Gallery users get a discount code for 30% off regular price.', 'nggallery');
        switch($context) {
            case 'tile':
                $card = new C_Marketing_Block_Large(
                    __('Use the Pro Tiled Gallery in NextGEN Pro', 'nggallery'),
                    __('With this stunning display type, you can present your images large with no trouble. Choose the maximum width of the gallery, or let it automate. It will adjust incredibly on all devices.', 'nggallery'),
                    $footer,
                    'https://www.imagely.com/wp-content/uploads/2020/06/tile.jpg',
                    'https://www.imagely.com/wordpress-gallery-plugin/pro-tiled-gallery/',
                    __('View the Pro Tiled Demo', 'nggallery'),
                    'TODO',
                    'TODO'
                );
                break;
            case 'mosaic':
                $card = new C_Marketing_Block_Large(
                    __('Use the Mosaic Gallery in NextGEN Pro', 'nggallery'),
                    __('With this stunning display type, you can present your images in a flexible grid. Choose the maximum height for your rows, and their margins, or use the default settings. It will adjust incredibly on all devices.', 'nggallery'),
                    $footer,
                    'https://www.imagely.com/wp-content/uploads/2020/06/mosaic.jpg',
                    'https://www.imagely.com/wordpress-gallery-plugin/pro-mosaic-gallery',
                    __('View the Mosaic Demo', 'nggallery'),
                    'TODO',
                    'TODO'
                );
                break;
            case 'masonry':
                $card = new C_Marketing_Block_Large(
                    __('Use the Masonry Gallery in NextGEN Pro', 'nggallery'),
                    __('With this stunning display type, you can present your images in a flexible grid. Choose the maximum width for your images, and their padding, or use the default settings. It will adjust incredibly on all devices.', 'nggallery'),
                    $footer,
                    'https://www.imagely.com/wp-content/uploads/2020/06/masonry.jpg',
                    'https://www.imagely.com/wordpress-gallery-plugin/pro-masonry-gallery',
                    __('View the Masonry Demo', 'nggallery'),
                    'TODO',
                    'TODO'
                );
                break;
            default:
                return '';
        }

        return $card->render();
    }

    function enqueue_static_resources()
    {
        M_Marketing::enqueue_blocks_style();
    }
}