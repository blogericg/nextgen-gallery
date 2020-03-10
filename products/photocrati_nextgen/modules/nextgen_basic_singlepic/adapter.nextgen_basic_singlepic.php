<?php

/**
 * Class A_NextGen_Basic_Singlepic
 * @mixin C_Display_Type
 * @adapts I_Display_Type
 */
class A_NextGen_Basic_Singlepic extends Mixin
{
    function validation()
    {
        if ($this->object->name == NGG_BASIC_SINGLEPIC)
        {

        }

        return $this->call_parent('validation');
    }
}