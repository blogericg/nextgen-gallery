<?php

class C_Lightbox_Installer_Mapper
{
    function find_by_name()
    {
        return NULL;
    }
}

class C_Lightbox_Installer
{
    public function __construct()
    {
        $this->mapper = new C_Lightbox_Installer_Mapper();
    }

    function install_lightbox($name, $title, $code, $stylesheet_paths = array(), $script_paths = array(), $values = array(), $i18n = array())
    {
        if (!is_array($stylesheet_paths) && is_string($stylesheet_paths) && FALSE !== strpos($stylesheet_paths, "\n"))
            $stylesheet_paths = explode("\n", $stylesheet_paths);
        if (!is_array($script_paths) && is_string($script_paths) && FALSE !== strpos($script_paths, "\n"))
            $script_paths = explode("\n", $script_paths);

        $lightbox = new C_NGG_Lightbox($name, array(
            'title'             =>  $title,
            'code'              =>  $code,
            'styles'            =>  $stylesheet_paths,
            'scripts'           =>  $script_paths,
            'values'            =>  $values,
            'i18n'              =>  $i18n
        ));
        C_Lightbox_Library_Manager::get_instance()->register($name, $lightbox);
    }
}
