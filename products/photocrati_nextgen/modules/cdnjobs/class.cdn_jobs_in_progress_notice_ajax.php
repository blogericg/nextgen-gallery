<?php
/**
 * @mixin C_Ajax_Controller
*/
class A_CDN_Jobs_In_Progress_Notice_Ajax extends Mixin
{
    public function cdnjobs_in_progress_check_status_action()
    {
        $queue = \ReactrIO\Background\Job::get_all_from_queue(
            'cdn',
            0,
            \ReactrIO\Background\Job::STATUS_QUEUED
        );

        $retval = [
            'count' => count($queue),
            'jobs'  => []
        ];

        foreach ($queue as $ndx => $job) {
            /** @var \ReactrIO\Background\Job $job */
            $retval['jobs'][$ndx]['label'] = $job->get_label();
        }

        return $retval;
    }
}