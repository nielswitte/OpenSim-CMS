<?php
if (EXEC != 1) {
    die('Invalid request');
}
?>
<div class="page-header">
    <h1>Grid <small></small></h1>
</div>
<form class="form-horizontal" role="form" id="gridForm">
    <div class="form-group">
        <label for="inputId" class="col-sm-2 control-label">ID</label>
        <div class="col-sm-10">
            <input type="number" class="form-control" id="inputId" placeholder="ID" readonly="readonly">
        </div>
    </div>
    <div class="form-group">
        <label for="inputName" class="col-sm-2 control-label">Name</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="inputName" placeholder="Name">
        </div>
    </div>
    <fieldset>
        <legend>OpenSim settings</legend>
        <div class="form-group">
            <label for="inputOsProtocol" class="col-sm-2 control-label">Protocol</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" id="inputOsProtocol" placeholder="Protocol">
            </div>
        </div>
        <div class="form-group">
            <label for="inputOsIp" class="col-sm-2 control-label">IP</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" id="inputOsIp" placeholder="IP">
            </div>
        </div>
        <div class="form-group">
            <label for="inputOsPort" class="col-sm-2 control-label">Port</label>
            <div class="col-sm-10">
                <input type="number" class="form-control" id="inputOsPort" placeholder="Port">
            </div>
        </div>
    </fieldset>
        <fieldset>
        <legend>Remote Admin</legend>
        <div class="form-group">
            <label for="inputRaUrl" class="col-sm-2 control-label">URL</label>
            <div class="col-sm-10">
                <input type="url" class="form-control" id="inputRaUrl" placeholder="URL">
            </div>
        </div>
        <div class="form-group">
            <label for="inputRaPort" class="col-sm-2 control-label">Port</label>
            <div class="col-sm-10">
                <input type="number" class="form-control" id="inputRaPort" placeholder="Port">
            </div>
        </div>
    </fieldset>
</form>
