<?php
if (EXEC != 1) {
    die('Invalid request');
}
?>
<div class="page-header">
    <h1>Presentation <small></small></h1>
</div>
<form class="form-horizontal" role="form" id="presentationForm">
    <div class="form-group">
        <label for="inputId" class="col-sm-2 control-label">ID</label>
        <div class="col-sm-10">
            <input type="number" class="form-control" id="inputId" placeholder="ID" readonly="readonly">
        </div>
    </div>
    <div class="form-group">
        <label for="inputType" class="col-sm-2 control-label">Type</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="inputType" placeholder="Type" readonly="readonly">
        </div>
    </div>
    <div class="form-group">
        <label for="inputTitle" class="col-sm-2 control-label">Title</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="inputTitle" placeholder="Title">
        </div>
    </div>
    <div class="form-group">
        <label for="inputOwner" class="col-sm-2 control-label">Owner ID</label>
        <div class="col-sm-10">
            <input type="hidden" class="form-control" id="inputOwner" placeholder="Owner ID">
        </div>
    </div>
    <div class="form-group">
        <label for="inputSlidesCount" class="col-sm-2 control-label">Slides count</label>
        <div class="col-sm-10">
            <input type="number" class="form-control" id="inputSlidesCount" placeholder="Slides count" readonly="readonly">
        </div>
    </div>
    <div class="form-group">
        <label for="inputCreationDate" class="col-sm-2 control-label">Creation date</label>
        <div class="col-sm-10">
            <input type="datetime" class="form-control" id="inputCreationDate" placeholder="Creation date" readonly="readonly">
        </div>
    </div>
    <div class="form-group">
        <label for="inputModificationDate" class="col-sm-2 control-label">Modification date</label>
        <div class="col-sm-10">
            <input type="datetime" class="form-control" id="inputModificationDate" placeholder="Modification date" readonly="readonly">
        </div>
    </div>
</form>