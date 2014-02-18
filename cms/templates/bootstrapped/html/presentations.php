<?php
if(EXEC != 1) {
	die('Invalid request');
}
?>
<div class="page-header">
  <h1>Presentations <small>Overview</small></h1>
</div>
<table class="table table-bordered table-striped table-responsive">
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th># Slides</th>
            <th>Owner</th>
            <th>Creation Date</th>
            <th>Modification Date</th>
        </tr>
    </thead>
    <tbody id="presentationsList">

    </tbody>
</table>



<script type="text/javascript">
    jQuery(document).ready(function($) {
        client.add('presentations');

        client.presentations.read().done(function(data) {
            var counter = 0;
            $.each(data, function(i, item) {
                $('#presentationsList').append('<tr><td>'+ item.presentationId +'</td><td><a href="<?php echo SERVER_ROOT; ?>/cms/presentation/'+ item.presentationId +'/">'+ item.title +'</a></td><td>'+ item.slidesCount +'</td><td>'+ item.ownerId +'</td><td>'+ item.creationDate +'</td><td>'+ item.modificationDate +'</td>');
                counter++;
            });
        }).fail(function() {
            alert('request failed');
        });
    });
</script>