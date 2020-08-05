<?php

/**
 * @mixin C_Form
 * @property C_MVC_Controller $object
 */
class A_Marketing_Other_Options_Form extends Mixin
{
    function get_title()
    {
        return __('Image Protection', 'nggallery');
    }

    function render()
    {
        $card = new C_Marketing_Block_Large(
            __('Protect your images', 'nggallery'),
            __('Image protection disables the ability for visitors to right-click or drag to download your images in both the gallery display and Pro Lightbox views. It gives you complete freedom to display your work without worry. You can also choose to protect all images sitewide, even outside of NextGEN Gallery.', 'nggallery'),
            __('<strong>Bonus:</strong> NextGEN Gallery users get a discount of 30% off regular price.', 'nggallery'),
            'fa-lock-open',
            'https://www.imagely.com/docs/turn-image-protection/?utm_medium=ngg&utm_source=otheroptions&utm_campaign=imageprotection',
            __('Learn more', 'nggallery'),
            'otheroptions',
            'imageprotection'

        );
        return $card->render();
    }

    function enqueue_static_resources()
    {
        M_Marketing::enqueue_blocks_style();
    }
}
