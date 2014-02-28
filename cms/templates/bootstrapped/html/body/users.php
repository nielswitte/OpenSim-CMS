<?php
if(EXEC != 1) {
	die('Invalid request');
}
?>
<div class="page-header">
  <h1>Users <small>Overview</small></h1>
</div>
<div class="panel panel-primary filter">
    <div class="panel-heading">
        <h3 class="panel-title">Users</h3>
        <div class="pull-right">
            <span class="clickable filter" data-toggle="tooltip" title="Toggle table filter" data-container="body">
                <i class="glyphicon glyphicon-filter"></i>
            </span>
        </div>
    </div>
    <div class="panel-body">
        <input type="text" class="form-control" id="user-table-filter" data-action="filter" data-filters="#userList" placeholder="Filter users">
    </div>
    <table class="table table-bordered table-striped table-responsive" id="userList">
        <thead>
            <tr>
                <th>#</th>
                <th>Username</th>
                <th>First name</th>
                <th>Last name</th>
                <th>E-mail address</th>
            </tr>
        </thead>
        <tbody>
            <!-- Load dynamic content -->
        </tbody>
    </table>
</div>