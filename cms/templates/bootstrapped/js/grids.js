jQuery(document).ready(function($) {
    $('#loading').show();
    client.grids.read({ token: api_token }).done(function(data) {
        var counter = 0;
        $.each(data, function(i, item) {
            $('#gridList tbody').append(
                    '<tr>'+
                    '   <td>'+ item.id +'</td>'+
                    '   <td><a href="'+ base_url +'/cms/grid/'+ item.id +'/">'+ item.name +'</a></td>'+
                    '   <td><a href="opensim://'+ item.openSim.ip +':'+ item.openSim.port +'/'+ encodeURIComponent(item.regions[item.defaultRegionUuid].name) +'/128/128/0/">'+ item.openSim.protocol +'://'+ item.openSim.ip +':'+ item.openSim.port +'</a></td>'+
                    '   <td>'+ item.regionCount +'</td>'+
                    '   <td>'+ (item.isOnline === 1 ? 'Online' : 'Offline') +'</td>'+
                    '   <td>'+ item.activeUsers +' / '+ item.totalUsers +'</td>'+
                    '</tr>');
            counter++;
        });
        $('#loading').hide();
    }).fail(function(data) {
        $('#gridList tbody').append('<tr><td colspan="6">Request failed...</td></tr>');
        addAlert('danger', '<strong>Error!</strong> Failed to load the list with grids. Your API key has probably expired.');
        $('#loading').hide();
    });

    // attach table filter plugin to inputs
	$('[data-action="filter"]').filterTable();
});