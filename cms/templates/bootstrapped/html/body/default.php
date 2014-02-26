<?php
if (EXEC != 1) {
    die('Invalid request');
}
?>
<div class="page-header">
    <h1>OpenSim-CMS <small>RESTful client</small></h1>
    <p class="lead">This client uses JavaScript to access the OpenSim-CMS API. </p>
</div>
<p>
    For a full documentation of the used API see <a href="https://www.github.com/nielswitte/OpenSim-CMS/" target="_blank">https://www.github.com/nielswitte/OpenSim-CMS/</a>.
    This CMS allows you to use the functions of the API to set up a meeting in the virtual environment of OpenSim. This CMS is part of my Master Thesis with the subject of
    <i>"Develop a Content Management System to Increase the Usability of a 3D Virtual Environment"</i>.
</p>
<p>
    The system is based on a client server setting. The server provides a RESTful JSON API with a back-end build in <a href="http://www.php.net" target="_blank">PHP</a>
    and <a href="http://www.mysql.net" target="_blank">MySQL</a>. The client is front-end build in <a href="http://www.php.net" target="_blank">PHP</a>
    and <a href="http://getbootstrap.com/" target="_blank">Boostrap</a> with support from <a href="http://jquery.com/" target="_blank">jQuery</a> and
    <a href="http://lesscss.org/" target="_blank">LessCSS</a>.
</p>
<p>
    Additional credits for the people who made the following libraries available:
    <ul>
        <li>Jpillora for jQuery.rest (<a href="https://github.com/jpillora/jquery.rest" target="_blank">https://github.com/jpillora/jquery.rest</a>)</li>
        <li>Arshaw for Fullcalendar (<a href="https://github.com/arshaw/fullcalendar" target="_blank">https://github.com/arshaw/fullcalendar</a>)</li>
        <li>Tobiasahlin for SpinKit (<a href="https://github.com/tobiasahlin/SpinKit" target="_blank">https://github.com/tobiasahlin/SpinKit</a>)</li>
        <li>Andris9 for jStorage (<a href="https://github.com/andris9/jStorage" target="_blank">https://github.com/andris9/jStorage</a>)</li>
    </ul>
</p>