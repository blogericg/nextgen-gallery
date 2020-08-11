<?php

class A_Marketing_IGW_Display_Type_Upsells extends Mixin
{
    function index_action($return=FALSE)
    {
        return $this->call_parent('index_action', $return);
    }

    function new_pro_display_type_upsell($id, $name, $title='', $preview_mvc_path=NULL)
    {
        return [
                'ID'                => $id,
                'default_source'    => 'galleries',
                'entity_types'      => ['image'],
                'hidden_from_igw'   => false,
                'hidden_from_ui'    => false,
                'name'              => $name,
                'title'             => $title,
                'preview_image_url' => $preview_mvc_path ? $this->get_static_url($preview_mvc_path) : ''
        ];
    }

    function get_pro_display_types()
    {
        return [
            $this->new_pro_display_type_upsell(
                -1,
                'pro-tile',
                __("Pro Tile", 'nggallery'),
                'photocrati-marketing#pro-tile-preview.jpg'
            ),
            $this->new_pro_display_type_upsell(
                -2,
                'pro-mosiac',
                __("Pro Mosiac", 'nggallery'),
                'photocrati-marketing#pro-mosiac-preview.jpg'
            ),
            $this->new_pro_display_type_upsell(
                -3,
                'pro-masonry',
                __("Pro Masonry", 'nggallery'),
                'photocrati-marketing#pro-masonry-preview.jpg'
            ),
            $this->new_pro_display_type_upsell(
                -4,
                'igw-promo'
            )
        ];
    }

    function get_marketing_cards()
    {
        $pro_tile = new C_Marketing_Block_Popup(
            __('Pro Tiled Gallery', 'nggallery'),
            M_Marketing::get_i18n_fragment('feature_not_available', __('the Pro Tiled Gallery', 'nggallery')),
            M_Marketing::get_i18n_fragment('lite_coupon'),
            $this->get_static_url('photocrati-marketing#pro-tile-preview.jpg'),
            'igw',
            'tiledgallery'
        );

        $pro_masonry = new C_Marketing_Block_Popup(
            __('Pro Masonry Gallery', 'nggallery'),
            M_Marketing::get_i18n_fragment('feature_not_available', __('the Pro Masonry Gallery', 'nggallery')),
            M_Marketing::get_i18n_fragment('lite_coupon'),
            $this->get_static_url('photocrati-marketing#pro-masonry-preview.jpg'),
            'igw',
            'masonrygallery'
        );

        $pro_mosiac = new C_Marketing_Block_Popup(
            __('Pro Mosiac Gallery', 'nggallery'),
            M_Marketing::get_i18n_fragment('feature_not_available', __('the Pro Mosiac Gallery', 'nggallery')),
            M_Marketing::get_i18n_fragment('lite_coupon'),
            $this->get_static_url('photocrati-marketing#pro-mosiac-preview.jpg'),
            'igw',
            'mosaicgallery'
        );

        return [
            'pro-tile'      => '<div>'.$pro_tile->render().'</div>',
            'pro-masonry'   => '<div>'.$pro_masonry->render().'</div>',
            'pro-mosiac'    => '<div>'.$pro_mosiac->render().'</div>'
        ];
    }

    function enqueue_display_tab_js()
    {
        $this->call_parent('enqueue_display_tab_js');

        $data = [
            'display_types' => $this->get_pro_display_types(),
            'i18n'          => [
                'get_pro'   => __("Requires NextGEN Pro", 'nggallery')
            ],
            'templates'     => $this->get_marketing_cards(),
            'igw_promo'     => $this->render_partial('photocrati-marketing#igw_promo', [], TRUE)
        ];

        wp_enqueue_script(
			'igw_display_type_upsells',
			$this->get_static_url('photocrati-marketing#igw_display_type_upsells.min.js'),
			['ngg_display_tab', 'jquery-ui-dialog'],
			NGG_SCRIPT_VERSION
        );

        wp_localize_script(
            'igw_display_type_upsells',
            'igw_display_type_upsells',
            $data
        );

        M_Marketing::enqueue_blocks_style();

        wp_add_inline_style('ngg_attach_to_post', ".display_type_preview:nth-child(5) {clear: both;} .ngg-marketing-block-display-type-settings label {color: darkgray !important;}");
        $this->mark_script('igw_display_type_upsells');
    }
}