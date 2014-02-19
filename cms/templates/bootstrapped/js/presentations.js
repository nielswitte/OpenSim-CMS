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

	$('.container').on('click', '.panel-heading span.filter', function(e){
		var $this = $(this);
        var $panel = $this.parents('.panel');

		$panel.find('.panel-body').slideToggle();
		if($this.css('display') !== 'none') {
			$panel.find('.panel-body input').focus();
		}
	});
	$('[data-toggle="tooltip"]').tooltip();
});


/**
*   I don't recommend using this plugin on large tables, I just wrote it to make the demo useable. It will work fine for smaller tables
*   but will likely encounter performance issues on larger tables.
*
*		<input type="text" class="form-control" id="dev-table-filter" data-action="filter" data-filters="#dev-table" placeholder="Filter Developers" />
*		$(input-element).filterTable()
*
*	The important attributes are 'data-action="filter"' and 'data-filters="#table-selector"'
*/
(function(){
    'use strict';
	var $ = jQuery;
	$.fn.extend({
		filterTable: function(){
			return this.each(function(){
				$(this).on('keyup', function(e){
					$('.filterTable_no_results').remove();
					var $this = $(this), search = $this.val().toLowerCase(), target = $this.attr('data-filters'), $target = $(target), $rows = $target.find('tbody tr');
					if(search === '') {
						$rows.show();
					} else {
						$rows.each(function(){
							var $this = $(this);
							$this.text().toLowerCase().indexOf(search) === -1 ? $this.hide() : $this.show();
						});
						if($target.find('tbody tr:visible').size() === 0) {
							var col_count = $target.find('tr').first().find('td').size();
							var no_results = $('<tr class="filterTable_no_results"><td colspan="'+col_count+'">No results found</td></tr>');
							$target.find('tbody').append(no_results);
						}
					}
				});
			});
		}
	});
	$('[data-action="filter"]').filterTable();
})(jQuery);