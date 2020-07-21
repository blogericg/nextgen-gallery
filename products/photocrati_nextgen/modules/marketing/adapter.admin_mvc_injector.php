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

        if ($pagenow === 'admin.php' && !empty($_GET['page']) && $_GET['page'] === 'ngg_addgallery')
        {
            M_Marketing::enqueue_blocks_style();

            if (empty(self::$block_one_cache))
            {
                $base = M_Marketing::get_big_hitters_block_base();
                $block = new C_Marketing_Block_Two_Columns(
                    $base['title'],
                    $base['description'],
                    $base['links'],
                    $base['footer'],
                    'addgalleryimages',
                    'upgradetonextgenpro'
                );
                self::$block_one_cache = $block->render();

            }

            foreach ($root_element->find('admin_page.content_main_form', TRUE) as $container) {
                $container->append(self::$block_one_cache);
            }
        }

        return $root_element;
    }
}