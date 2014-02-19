<?php
if(EXEC != 1) {
	die('Invalid request');
}
?>
<div class="page-header">
  <h1>Presentations <small>Overview</small></h1>
</div>
<div class="panel panel-primary filter">
    <div class="panel-heading">
        <h3 class="panel-title">Presentations</h3>
        <div class="pull-right">
            <span class="clickable filter" data-toggle="tooltip" title="Toggle table filter" data-container="body">
                <i class="glyphicon glyphicon-filter"></i>
            </span>
        </div>
    </div>
    <div class="panel-body">
        <input type="text" class="form-control" id="presentation-table-filter" data-action="filter" data-filters="#presentationsList" placeholder="Filter Presentations">
    </div>
    <table class="table table-bordered table-striped table-responsive" id="presentationsList">
        <thead>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Slides</th>
                <th>Owner</th>
                <th>Creation Date</th>
                <th>Modification Date</th>
            </tr>
        </thead>
        <tbody>
            <!-- Load dynamic content -->
        </tbody>
    </table>
</div>