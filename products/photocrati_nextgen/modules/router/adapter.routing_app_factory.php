<?php

/**
 * Class A_Routing_App_Factory
 * @mixin C_Component_Factory
 * @adapts I_Component_Factory
 */
class A_Routing_App_Factory extends Mixin
{
    function routing_app($context = FALSE, $router = FALSE)
    {
        return new C_Routing_App($context, $router);
    }
}
