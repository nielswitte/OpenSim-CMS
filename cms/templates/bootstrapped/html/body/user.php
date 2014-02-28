<?php
if (EXEC != 1) {
    die('Invalid request');
}
?>
<div class="page-header">
    <h1>User <small></small></h1>
</div>
<form class="form-horizontal" role="form" id="userForm">
    <div class="form-group">
        <label for="inputId" class="col-sm-2 control-label">ID</label>
        <div class="col-sm-10">
            <input type="number" class="form-control" id="inputId" placeholder="ID" readonly="readonly">
        </div>
    </div>
    <div class="form-group">
        <label for="inputUsername" class="col-sm-2 control-label">Username</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="inputUsername" placeholder="Username">
        </div>
    </div>
    <div class="form-group">
        <label for="inputFirstName" class="col-sm-2 control-label">First name</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="inputFirstName" placeholder="First name">
        </div>
    </div>
    <div class="form-group">
        <label for="inputLastName" class="col-sm-2 control-label">Last name</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="inputLastName" placeholder="Last name">
        </div>
    </div>
    <div class="form-group">
        <label for="inputEmail" class="col-sm-2 control-label">E-mail address</label>
        <div class="col-sm-10">
            <input type="email" class="form-control" id="inputEmail" placeholder="E-mail address">
        </div>
    </div>
    <fieldset id="avatars">
        <legend>Avatars <span id="avatarCount"></span></legend>
    </fieldset>
</form>