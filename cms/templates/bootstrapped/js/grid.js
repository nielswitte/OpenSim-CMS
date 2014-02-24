jQuery(document).ready(function($) {
    client.grid.read(pages[1], { token: api_token }).done(function(data) {
        $('div.page-header h1 small').text(data.name);
        $('#inputId').val(data.id);
        $('#inputType').val(data.type);
        $('#inputName').val(data.name);
        $('#inputOsProtocol').val(data.openSim.protocol);
        $('#inputOsIp').val(data.openSim.ip);
        $('#inputOsPort').val(data.openSim.port);
        $('#inputRaUrl').val(data.remoteAdmin.url);
        $('#inputRaPort').val(data.remoteAdmin.port);


    }).fail(function() {
        addAlert('danger', '<strong>Error!</strong> Did you manually entered this URL? If so, check the parameters and try again.');
    });
});