<?php

class C_CDN_Rotate_Image_Job extends \ReactrIO\Background\Job
{

    function run()
    {
        $cdn  = C_CDN_Providers::get_current();
        $data = $this->get_dataset();

        try {
            $cdn->download($data['id'], 'full');
        } catch (Exception $ex) {
            $cdn->download($data['id'], 'backup');
        }

        $this->rotate_local_image($data['id'], $data['direction']);
    }

    /**
     * @param int $id
     * @param string $direction 'clockwise' or 'counter-clockwise'
     * @throws RuntimeException
     */
    function rotate_local_image($id, $direction)
    {
        if (!in_array($direction, ['clockwise', 'counter-clockwise']))
            throw new RuntimeException(
                sprintf(__("Could not rotate image #%d with direction %s: direction must be clockwise or counter-clockwise", 'nggallery'),
                    $id,
                    $direction
                )
            );

        $storage = C_Gallery_Storage::get_instance();

        $params = [
            'watermark'  => FALSE,
            'reflection' => FALSE
        ];

        if ($direction === 'clockwise')
            $params['rotation'] = 90;
        if ($direction === 'counter-clockwise')
            $params['rotation'] = -90;

        // generate_image_size() will make a new _backup file if this setting is on
        $settings = C_NextGen_Settings::get_instance();
        $original = $settings->imgBackup;
        $settings->imgBackup = 0;

        $result_one = $storage->generate_image_size($id, 'full', $params);

        // Avoid rotating the thumbnail a second time
        unset($params['rotation']);

        $result_two = $storage->generate_thumbnail($id, $params);

        // Restore the original setting
        $settings->imgBackup = $original;

        if ($result_one === FALSE)
            throw new RuntimeException(
                sprintf(__("Could not rotate image #%d", 'nggallery'), $id)
            );

        if ($result_two === FALSE)
            throw new RuntimeException(
                sprintf(__("Could not generate thumbnail for image #%d", 'nggallery'), $id)
            );
    }

}