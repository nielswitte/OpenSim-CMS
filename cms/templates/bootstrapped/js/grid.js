jQuery(document).ready(function($) {
    $('#loading').show();
    client.grid.read(pages[1], { token: api_token }).done(function(data) {
        $('div.page-header h1 small').text(data.name);
        $('#inputId').val(data.id);
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
                '<div class="col-sm-4 col-md-4 region">'+
                '   <div class="panel panel-default">'+
                '       <div class="panel-heading">'+
                '           <h3 class="panel-title">'+
                                (region.serverStatus === 1 ? '<span class="label label-success">Online</span>' : '<span class="label label-danger">Offline</span>') +
                '               '+ region.name +
                                (i === data.defaultRegionUuid ? ' <span class="glyphicon glyphicon-home" id="defaultRegion" title="Is the default region"></span>' : '') +
                '           </h3>'+
                '       </div>'+
                '       <div class="panel-body">'+
                '           <p class="text-center"><img src="'+ (region.serverStatus === 1 ? region.image +'?token='+ api_token : base_url +'/cms/templates/bootstrapped/img/img-placeholder.png')  +'" alt="'+ region.name +'" class="img-thumbnail img-responsive"></p>'+
                '           <ul>'+
                '               <li><strong>Uuid:</strong> '+ region.uuid +'</li>'+
                '               <li><strong>Users:</strong> '+ region.activeUsers +'/'+ region.totalUsers +'</li>'+
                '           </ul>'+
                '       </div>'+
                '       <div class="panel-footer text-center">'+
                '           <a href="opensim://'+ data.openSim.ip +':'+ data.openSim.port +'/'+ encodeURIComponent(region.name) +'/128/128/0/" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-send"></i> Visit '+ region.name +'</a>'+
                '       </div>'+
                '   </div>'+
                '</div>'
            );
        });

        $('#defaultRegion').tooltip({ placement: 'top' });
        $('#loading').hide();
    }).fail(function() {
        addAlert('danger', '<strong>Error!</strong> Did you manually entered this URL? If so, check the parameters and try again.');
        $('#loading').hide();
    });
});