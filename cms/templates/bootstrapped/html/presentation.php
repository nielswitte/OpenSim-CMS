<?php
if (EXEC != 1) {
    die('Invalid request');
}
?>
<div class="page-header">
    <h1>Presentation <small></small></h1>
</div>
<?php
if (isset($pages[1]) && is_numeric($pages[1])) {
?>
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
            <input type="text" class="form-control" id="inputOwner" placeholder="Owner ID">
            @todo: make select2 with ajax search
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



<script type="text/javascript">
    jQuery(document).ready(function($) {
        var client = new $.RestClient('/OpenSim-CMS/api/', {
            cache: 5,
            cachableMethods: ["GET"]
        });

        client.add('user');

        client.add('presentation');
        client.presentation.read(<?php echo $pages[1]; ?>).done(function(data) {
            $('div.page-header h1 small').text(data.title);
            $('#inputId').val(data.presentationId);
            $('#inputType').val(data.type);
            $('#inputTitle').val(data.title);

            $('#inputId').val(data.presentationId);

            client.user.read(data.ownerId).done(function(user) {
                $('#inputOwner').val(data.ownerId +' - '+ user.userName);
            }).fail(function() {
                $('#inputOwner').val('Unknown');
            });

            $('#inputSlidesCount').val(data.slidesCount);
            $('#inputCreationDate').val(data.creationDate);
            $('#inputModificationDate').val(data.modificationDate);

        }).fail(function() {
            alert('request failed');
        });
    });
</script>
<?php
} else {
?>
<div class="alert alert-danger">
    <strong>Error!</strong><br>
    Something went wrong when loading this page. Did you manually enter this URL?
    If so, check the URL and its parameters, and try again.
</div>
<?php
}
