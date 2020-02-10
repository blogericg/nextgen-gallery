<?php

/**
 * Class C_Http_Response_Controller
 * @mixin Mixin_Http_Response_Actions
 * @implements I_Http_Response
 */
class C_Http_Response_Controller extends C_Component
{
    /** @var C_Http_Response_Controller */
	static $_instance = NULL;

    /**
     * @param bool|string $context
     * @return C_Http_Response_Controller
     */
	static function get_instance($context=FALSE)
	{
		if (!isset(self::$_instance))
		{
			$klass = get_class();
			self::$_instance = new $klass();
		}

		return self::$_instance;
	}

	function define($context = FALSE)
	{
		$this->add_mixin('Mixin_Http_Response_Actions');
		$this->implement('I_Http_Response');
	}
}


class Mixin_Http_Response_Actions extends Mixin
{
	function http_301_action()
	{
		header('HTTP/1.1 301 Permanent Redirect');
		header("Location: {$this->object->get_routed_url()}");
	}

	function http_302_action()
	{
		header('HTTP/1.1 302 Temporary Redirect');
		header("Location: {$this->object->get_routed_url()}");
	}

	function http_501_action()
	{
        header('HTTP/1.1 501 Not Implemented');
	}

	function http_404_action()
	{
        header('HTTP/1.1 404 Not found');
	}

    function http_202_action()
    {
        header('HTTP/1.1 202 Accepted');
    }
}
