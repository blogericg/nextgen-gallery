<?php

/**
 * Class A_NextGen_Pro_Upgrade_Page
 * @mixin C_NextGen_Admin_Page_Controller
 * @adapts I_NextGen_Admin_Page_Controller
 * @todo merge with A_NextGen_Pro_Plus_Upgrade_Page class
 */
class A_NextGen_Pro_Upgrade_Page extends Mixin
{
    function setup()
    {
        // Using include() to retrieve the is_plugin_active() is apparently The WordPress Way(tm)..
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        // We shouldn't show the upgrade page if they already have the plugin and it's active
        $found = false;
        if (defined('NEXTGEN_GALLERY_PRO_PLUGIN_BASENAME'))
            $found = 'NEXTGEN_GALLERY_PRO_PLUGIN_BASENAME';
        if (defined('NGG_PRO_PLUGIN_BASENAME'))
            $found = 'NGG_PRO_PLUGIN_BASENAME';

        if (!(($found && is_plugin_active(constant($found))))) {
            $this->object->add('ngg_pro_upgrade', array(
                'adapter'	=>		'A_NextGen_Pro_Upgrade_Controller',
                'parent'	=>		NGGFOLDER
            ));
        }

        return $this->call_parent('setup');
    }
}