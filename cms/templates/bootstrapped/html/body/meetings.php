<?php
if(EXEC != 1) {
	die('Invalid request');
}
?>
<div class="page-header">
    <h1>Meetings <small>Overview</small></h1>
</div>
<p>All planned meetings and those from two weeks ago are displayed in the schedule below. Click a meeting for more information.</p>
<div id='calendar'></div>

<script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/libs/moment-2.5.1.min.js" type="text/javascript"></script>
<script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/libs/fullcalendar-2.0.0.beta2.min.js" type="text/javascript"></script>
