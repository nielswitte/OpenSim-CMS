jQuery(document).ready(function($) {
    $('#loading').show();
    var avatarCount = 0;

    client.user.read(pages[1], { token: api_token }).done(function(data) {
        $('div.page-header h1 small').text(data.username);
        $('#inputId').val(data.id);
        $('#inputUsername').val(data.username);
        $('#inputFirstName').val(data.firstName);
        $('#inputLastName').val(data.lastName);
        $('#inputEmail').val(data.email);


        $.each(data.avatars, function(i, avatar) {
            $('#avatars').append(
                '<div class="col-sm-6 col-md-6 avatar">'+
                '   <div class="panel panel-default">'+
                '       <div class="panel-heading"><h3 class="panel-title">'+ (avatar.online === 1 ? '<span class="label label-success">Online</span>' : '<span class="label label-danger">Offline</span>') +' '+ avatar.firstName +' '+ avatar.lastName +'</h3></div>'+
                '       <div class="panel-body">'+
                '           <ul>'+
                '               <li><strong>Uuid:</strong> '+ avatar.uuid +'</li>'+
                '               <li><strong>E-mail address:</strong> '+ avatar.email +'</li>'+
                '               <li><strong>Grid:</strong> <a href="'+ base_url +'/cms/grid/'+ avatar.gridId +'/">'+ avatar.gridName +'</a></li>'+
                '               <li><strong>Confirmed:</strong> <span class="confirmationStatus">'+ (avatar.confirmed === 1 ? 'Yes' : 'No') +'</span></li>'+
                '               <li><strong>Last Login:</strong> '+ avatar.lastLogin +'</li>'+
                '               <li><strong>Last Region:</strong> '+ avatar.lastRegion +'</li>'+
                '               <li><strong>Last Position:</strong> '+ avatar.lastPosition +'</li>'+
                '           </ul>'+
                '       </div>'+
                '       <div class="panel-footer text-center">'+
                '          '+ (avatar.confirmed === 0 ? '<button data-avataruuid="'+ avatar.uuid +'" data-gridid="'+ avatar.gridId +'" class="confirmAvatar btn btn-success btn-sm"><i class="glyphicon glyphicon-ok"></i> Confirm</button>' : '') +
                '           <button data-avataruuid="'+ avatar.uuid +'" data-gridid="'+ avatar.gridId +'" class="removeAvatar btn btn-danger btn-sm"><i class="glyphicon glyphicon-remove"></i> Remove</button>'+
                '       </div>'+
                '   </div>'+
                '</div>'
            );
            avatarCount++;
        });
        $('#avatarCount').text('('+ avatarCount +')');

        $('#loading').hide();
    }).fail(function() {
        addAlert('danger', '<strong>Error!</strong> Did you manually entered this URL? If so, check the parameters and try again.');
        $('#loading').hide();
    });

    // Confirm an avatar
    $('#avatars').on('click', 'button.confirmAvatar', function(e){
        e.preventDefault();
        var avatarUuid  = $(this).data('avataruuid');
        var gridId      = $(this).data('gridid');
        var button      = $(this);
        $('#loading').show();
        client.grid.avatar.update(gridId, avatarUuid, {}, { token: api_token }).done(function(data){
            button.parents('div.avatar').find('span.confirmationStatus').text('Yes');
            button.remove();
            addAlert('success', '<strong>Confirmed!</strong> Avatar successfully confirmed.');
            $('#loading').hide();
        }).fail(function(data){
            addAlert('danger', '<strong>Error!</strong> Confirming avatar failed.');
            $('#loading').hide();
        });
    });

    // Remove an avatar
    $('#avatars').on('click', 'button.removeAvatar', function(e){
        e.preventDefault();
        var avatarUuid  = $(this).data('avataruuid');
        var gridId      = $(this).data('gridid');
        var button      = $(this);
        $('#loading').show();
        client.grid.avatar.del(gridId, avatarUuid, {}, { token: api_token }).done(function(data){
            button.parents('div.avatar').remove();
            avatarCount--;
            $('#avatarCount').text('('+ avatarCount +')');
            addAlert('success', '<strong>Confirmed!</strong> Avatar successfully removed.');
            $('#loading').hide();
        }).fail(function(data){
            addAlert('danger', '<strong>Error!</strong> Confirming avatar failed.');
            $('#loading').hide();
        });
    });
});