
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