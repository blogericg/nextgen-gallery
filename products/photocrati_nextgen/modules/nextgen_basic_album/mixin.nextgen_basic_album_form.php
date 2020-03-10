<?php

/**
 * Class Mixin_NextGen_Basic_Album_Form
 * @mixin C_Form
 */
class Mixin_NextGen_Basic_Album_Form extends Mixin_Display_Type_Form
{
	function _get_field_names()
	{
		return array(
            'nextgen_basic_album_gallery_display_type',
            'nextgen_basic_album_galleries_per_page',
            'nextgen_basic_album_enable_breadcrumbs',
            'display_view',
            'nextgen_basic_templates_template',
            'nextgen_basic_album_enable_descriptions'
            
        );
	}

    /**
     * Renders the Gallery Display Type field
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_album_gallery_display_type_field($display_type)
    {
        $mapper = C_Display_Type_Mapper::get_instance();

        // Disallow hidden or inactive display types
        $types = $mapper->find_by_entity_type('image');
        foreach ($types as $ndx => $type) {
            if (!empty($type->hidden_from_ui) && $type->hidden_from_ui)
                unset($types[$ndx]);
        }

        return $this->render_partial(
            'photocrati-nextgen_basic_album#nextgen_basic_album_gallery_display_type',
            array(
                'display_type_name'             =>  $display_type->name,
                'gallery_display_type_label'    =>  __('Display galleries as', 'nggallery'),
                'gallery_display_type_help'     =>  __('How would you like galleries to be displayed?', 'nggallery'),
                'gallery_display_type'          =>  $display_type->settings['gallery_display_type'],
                'galleries_per_page_label'      =>  __('Galleries per page', 'nggallery'),
                'galleries_per_page'            =>  $display_type->settings['galleries_per_page'],
                'display_types'                 =>  $types
            ),
            TRUE
        );
    }


    /**
     * Renders the Galleries Per Page field
     * @param C_Display_Type $display_type
     * @return string
     */
    function _render_nextgen_basic_album_galleries_per_page_field($display_type)
    {
        return $this->_render_number_field(
            $display_type,
            'galleries_per_page',
            __('Items per page', 'nggallery'),
            $display_type->settings['galleries_per_page'],
            __('Maximum number of galleries or sub-albums to appear on a single page', 'nggallery'),
            FALSE,
            '',
            0
        );
    }

    function _render_nextgen_basic_album_enable_breadcrumbs_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'enable_breadcrumbs',
            __('Enable breadcrumbs', 'nggallery'),
            isset($display_type->settings['enable_breadcrumbs']) ? $display_type->settings['enable_breadcrumbs'] : FALSE
        );
    }

    function _render_nextgen_basic_album_enable_descriptions_field($display_type)
    {
        return $this->_render_radio_field(
            $display_type,
            'enable_descriptions',
            __('Display descriptions', 'nggallery'),
            $display_type->settings['enable_descriptions']
        );
    }
}