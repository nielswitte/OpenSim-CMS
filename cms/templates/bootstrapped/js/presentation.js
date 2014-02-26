jQuery(document).ready(function($) {
    $('#loading').show();
    client.presentation.read(pages[1], { token: api_token }).done(function(data) {
        $('div.page-header h1 small').text(data.title);
        $('#inputId').val(data.presentationId);
        $('#inputType').val(data.type);
        $('#inputTitle').val(data.title);
        $('#inputId').val(data.presentationId);
        $('#inputOwner').val(data.ownerId);
        $('#inputSlidesCount').val(data.slidesCount);
        $('#inputCreationDate').val(data.creationDate);
        $('#inputModificationDate').val(data.modificationDate);
        selectInit();
        $('#loading').hide();
    }).fail(function() {
        addAlert('danger', '<strong>Error!</strong> Did you manually entered this URL? If so, check the parameters and try again.');
        $('#loading').hide();
    });

    function selectInit() {
        $("#inputOwner").select2({
            placeholder: "Search for a user",
            minimumInputLength: 3,
            ajax: {
                url: function(term, page) {
                    return base_url +"/api/users/"+ term +"/?token="+ api_token;
                },
                dataType: 'json',
                results: function(data, page) {
                    var result = [];
                    $.each(data, function(i, item) {
                        var items = {id: i, text: item.userName};
                        result.push(items);
                    });

                    return {results: result};
                }
            },
            initSelection: function(element, callback) {
                var id = $(element).val();
                if (id !== "") {
                    $.ajax(base_url +"/api/user/"+ id +"/?token="+ api_token, {
                        dataType: "json"
                    }).done(function(data) {
                        callback({id: data.id, text: data.userName});
                    });
                }
            }
        });
    }
});