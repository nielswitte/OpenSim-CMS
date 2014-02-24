<?php
if(EXEC != 1) {
	die('Invalid request');
}
?>
<div class="page-header">
  <h1>Grids <small>Overview</small></h1>
</div>
<div class="panel panel-primary filter">
    <div class="panel-heading">
        <h3 class="panel-title">Grids</h3>
        <div class="pull-right">
            <span class="clickable filter" data-toggle="tooltip" title="Toggle table filter" data-container="body">
                <i class="glyphicon glyphicon-filter"></i>
            </span>
        </div>
    </div>
    <div class="panel-body">
        <input type="text" class="form-control" id="presentation-table-filter" data-action="filter" data-filters="#gridList" placeholder="Filter Grids">
    </div>
    <table class="table table-bordered table-striped table-responsive" id="gridList">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Address</th>
                <th># Regions</th>
                <th>Status</th>
                <th>Users</th>
            </tr>
        </thead>
        <tbody>
            <!-- Load dynamic content -->
        </tbody>
    </table>
</div>