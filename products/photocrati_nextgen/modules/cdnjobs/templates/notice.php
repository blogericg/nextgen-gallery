<?php
/**
 * @var array $i18n
 * @var int $count
 */ ?>
<div id='ngg_cdn_jobs_in_progress_notice'>

    <span id="ngg_cdn_jobs_in_progress_notice_pending_label">
        <?php print sprintf($i18n['pending_label'], $count); ?>
    </span>

    <?php print $i18n['expand_link']; ?>

    <span id="ngg_cdn_jobs_in_progress_notice_countdown" class="hidden">
    </span>
    <span id="ngg_cdn_jobs_in_progress_notice_spinner" class="hidden">
        <?php print $i18n['refreshing_label']; ?>
        <i class="fa fa-spin fa-spinner"></i>
    </span>

    <div id="ngg_cdn_jobs_in_progress_notice_details" class="hidden">
        <ul>
        </ul>
        <a href="#" id="ngg_cdn_jobs_in_progress_notice_minimize_link"><?php print $i18n['minimize_label']; ?></a>
    </div>
</div>