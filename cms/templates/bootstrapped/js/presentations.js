jQuery(document).ready(function($) {
    client.presentations.read({ token: api_token }).done(function(data) {
        var counter = 0;
        $.each(data, function(i, item) {
            $('#presentationsList tbody').append(
                    '<tr>'+
                    '   <td>'+ item.presentationId +'</td>'+
                    '   <td><a href="'+ base_url +'/cms/presentation/'+ item.presentationId +'/">'+ item.title +'</a></td>'+
                    '   <td>'+ item.slidesCount +'</td>'+
                    '   <td>'+ item.ownerId +'</td>'+
                    '   <td>'+ item.creationDate +'</td>'+
                    '   <td>'+ item.modificationDate +'</td>'+
                    '</tr>');
            counter++;
        });
    }).fail(function(data) {
        $('#presentationsList tbody').append('<tr><td colspan="6">Request failed...</td></tr>');
        addAlert('danger', '<strong>Error!</strong> Failed to load the list with presentations. Your API key has probably expired.');
    });

    // attach table filter plugin to inputs
	$('[data-action="filter"]').filterTable();
});
