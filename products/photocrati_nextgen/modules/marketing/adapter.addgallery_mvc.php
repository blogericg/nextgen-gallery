<?php

class A_Marketing_AddGallery_MVC extends Mixin
{
    /**
     * @param string $medium
     * @return string
     */
    function get_base_addgallery_block($medium)
    {
        $base = M_Marketing::get_big_hitters_block_base('addgalleryimages');
        $block = new C_Marketing_Block_Two_Columns(
            $base['title'],
            $base['description'],
            $base['links'],
            $base['footer'],
            $medium,
            'upgradetonextgenpro'
        );

        return $block->render();
    }

    function render_object()
    {
        $root_element = $this->call_parent('render_object');

        M_Marketing::enqueue_blocks_style();

        foreach ($root_element->find('admin_page.content_main_form', TRUE) as $container) {
            /** @var C_MVC_View_Element $container */
            switch ($container->get_object()->context) {
                case 'upload_images':
                    $medium = 'addgalleryimages';
                    break;
                case 'import_media_library':
                    $medium = 'addgalleryimportmedia';
                    break;
                case 'import_folder':
                    $medium = 'addgalleryimportfolder';
                    break;
            }
            $container->append($this->get_base_addgallery_block($medium));
        }

        return $root_element;
    }
}