<?php

class A_Marketing_Lightbox_Options_Form extends Mixin
{
    public function get_model()
    {
        return NULL;
    }

    public function is_valid()
    {
        return TRUE;
    }

    function render()
    {
        $retval = '<tr><td colspan="2">';
        $block = new C_Marketing_Block_Large(
            __('Go big with the Pro Lightbox', 'nggallery'),
            __("The Pro Lightbox allows you to display images at full scale when opened. Your visitors will enjoy breathtaking views of your photos on any device. It's customizable, from colors to padding and more. Offer social sharing, deep linking, and individual image commenting. Turn your gallery lightbox view into a slideshow for your visitors. You can customize settings such as auto-playing and slideshow speed.", 'nggallery'),
            __('<strong>Bonus:</strong> NextGEN Gallery users get a discount code for 30% off regular price', 'nggallery'),
            'fa-expand',
            'https://www.imagely.com/wordpress-gallery-plugin/pro-lightbox-demo?utm_medium=ngg&utm_source=otheroptions&utm_campaign=prolightbox',
            __('View the Pro Lightbox Demo', 'nggallery'),
            'otheroptions',
            'prolightbox'
        );
        $retval .= $block->render();
        $retval .= '</td></tr>';
        return $retval;
    }

}