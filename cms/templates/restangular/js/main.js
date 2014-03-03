var partial_path = base_url +'/cms/templates/restangular/html/partials';
var controller_path = base_url +'/cms/templates/restangular/js/controllers';

/**
 *
 * @param {string} type [success, info, warning, danger]
 * @param {string} message
 * @returns {string} html
 */
function addAlert(type, message) {
    $('#alerts').append(
            '<div class="alert alert-'+ type +' alert-dismissable">'+
            '   <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'+
            '   '+ message +
            '</div>'
    );
}

/**
 * From: http://phpjs.org/functions
 * original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * bugfixed by: Onno Marsman
 * improved by: Brett Zamir (http://brett-zamir.me)
 *     example 1: ucfirst('kevin van zonneveld');
 *     returns 1: 'Kevin van zonneveld'
 * @param {string}  str
 * @returns {string}
 */
function ucfirst(str) {
    str += '';
    var f = str.charAt(0).toUpperCase();
    return f + str.substr(1);
}

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


	$('.container').on('click', '.panel-heading span.filter', function(e){
		var $this = $(this);
        var $panel = $this.parents('.panel');

		$panel.find('.panel-body').slideToggle();
		if($this.css('display') !== 'none') {
			$panel.find('.panel-body input').focus();
		}
	});
	$('[data-toggle="tooltip"]').tooltip();
})(jQuery);