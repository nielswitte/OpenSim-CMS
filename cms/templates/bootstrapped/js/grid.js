jQuery(document).ready(function($) {
    client.grid.read(pages[1], { token: api_token }).done(function(data) {
        $('div.page-header h1 small').text(data.name);
        $('#inputId').val(data.id);
        $('#inputType').val(data.type);
        $('#inputName').val(data.name);
        $('#inputCache').val(data.cacheTime);
        $('#inputOsProtocol').val(data.openSim.protocol);
        $('#inputOsIp').val(data.openSim.ip);
        $('#inputOsPort').val(data.openSim.port);
        $('#inputRaUrl').val(data.remoteAdmin.url);
        $('#inputRaPort').val(data.remoteAdmin.port);
        $('#regionCount').text('('+ data.regionCount +')');

        $.each(data.regions, function(i, region) {
            $('#regionThumbs').append(
                '<div class="col-sm-3 col-md-3">'+
                '    <h3>'+ region.name +' <small>'+ (i === data.defaultRegionUuid ? 'Default region' : '') +'</h3>'+
                '    <img src="'+ (region.serverStatus === 1 ? region.image +'?token='+ api_token : base_url +'/cms/templates/bootstrapped/img/img-placeholder.png')  +'" alt="'+ region.name +'" class="img-thumbnail img-responsive">'+
                '    <p><strong>Uuid:</strong> '+ region.uuid +'</p>'+
                '    <p><strong>Status:</strong> '+ (region.serverStatus === 1 ? 'Online' : 'Offline') +'</p>'+
                '    <p><strong>Users:</strong> '+ region.activeUsers +'/'+ region.totalUsers +'</p>'+
                '</div>'
            );
        });


    }).fail(function() {
        addAlert('danger', '<strong>Error!</strong> Did you manually entered this URL? If so, check the parameters and try again.');
    });
});