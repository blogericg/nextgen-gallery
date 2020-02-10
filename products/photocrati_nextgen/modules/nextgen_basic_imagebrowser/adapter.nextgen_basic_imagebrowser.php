<?php

/**
 * Adds validation for the NextGen Basic ImageBrowser display type
 * @mixin C_Display_Type
 * @adapts I_Display_Type
 */
class A_NextGen_Basic_ImageBrowser extends Mixin
{
    function validation()
    {
        return $this->call_parent('validation');
    }
}
