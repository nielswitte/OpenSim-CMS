jQuery(document).ready(function($) {
    $('#loading').show();
    client.users.read({ token: api_token }).done(function(data) {
        var counter = 0;
        $.each(data, function(i, item) {
            $('#userList tbody').append(
                    '<tr>'+
                    '   <td>'+ item.id +'</td>'+
                    '   <td><a href="'+ base_url +'/cms/user/'+ item.id +'/">'+ item.username +'</a></td>'+
                    '   <td>'+ item.firstName +'</td>'+
                    '   <td>'+ item.lastName +'</td>'+
                    '   <td>'+ item.email +'</td>'+
                    '</tr>');
            counter++;
        });
        $('#loading').hide();
    }).fail(function(data) {
        $('#userList tbody').append('<tr><td colspan="6">Request failed...</td></tr>');
        addAlert('danger', '<strong>Error!</strong> Failed to load the list with presentations. Your API key has probably expired.');
        $('#loading').hide();
    });

    // attach table filter plugin to inputs
	$('[data-action="filter"]').filterTable();
});
