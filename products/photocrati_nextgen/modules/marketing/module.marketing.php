<?php

class M_Marketing extends C_Base_Module
{
    function define($id = 'pope-module',
                    $name = 'Pope Module',
                    $description = '',
                    $version = '',
                    $uri = '',
                    $author = '',
                    $author_uri = '',
                    $context = FALSE)
    {
        parent::define(
            'photocrati-marketing',
            'Marketing',
            'Provides resources for encouraging users to upgrade to NextGen Plus/Pro',
            '3.3.6',
            'https://www.imagely.com/wordpress-gallery-plugin/nextgen-gallery/',
            'Imagely',
            'https://www.imagely.com'
        );
    }

    function _register_hooks()
    {
    }

    function _register_utilities()
    {
    }

    function _register_adapters()
    {
        if (!defined('NEXTGEN_GALLERY_PRO_PLUGIN_BASENAME')
        &&  !defined('NGG_PRO_PLUGIN_BASENAME')
        &&  !defined('NGG_PLUS_PLUGIN_BASENAME')
        &&  is_admin())
        {
            $registry = $this->get_registry();
            $registry->add_adapter('I_MVC_View', 'A_Marketing_Admin_MVC_Injector');
        }
    }

    function initialize()
    {
        wp_register_style(
            'ngg_marketing_cards_style',
            C_Router::get_instance()->get_static_url('photocrati-marketing#cards.css'),
            [],
            NGG_SCRIPT_VERSION
        );
        wp_register_style(
            'ngg_marketing_blocks_style',
            C_Router::get_instance()->get_static_url('photocrati-marketing#blocks.css'),
            [],
            NGG_SCRIPT_VERSION
        );
    }

    function get_type_list()
    {
        return [
            'C_Marketing_Card'               => 'class.marketing_card.php',
            'C_Marketing_Block_Two_Columns'  => 'class.marketing_block_two_columns.php',
            'A_Marketing_Admin_MVC_Injector' => 'adapter.marketing_admin_mvc_injector.php'
        ];
    }
}

new M_Marketing;
