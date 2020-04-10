<?php

class C_CDN_Delete_Gallery_Final_Job extends \ReactrIO\Background\Job
{
    function run()
    {
        $data = $this->get_dataset();
        $id   = $data['id'];

        $mapper = C_Gallery_Mapper::get_instance();

        /** @var $mapper Mixin_Gallery_Mapper */
        $mapper->destroy($id, TRUE);
    }
}