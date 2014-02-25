<?php
if(EXEC != 1) {
	die('Invalid request');
}
?>
<div class="page-header">
    <h1>Meetings <small>Overview</small></h1>
</div>
<div id='calendar'></div>

<script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/libs/moment-2.5.1.min.js" type="text/javascript"></script>
<script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/libs/fullcalendar-2.0.0.beta2.min.js" type="text/javascript"></script>
