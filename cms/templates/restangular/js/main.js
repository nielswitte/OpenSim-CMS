var partial_path    = base_url +'/cms/templates/restangular/html/partials';
var controller_path = base_url +'/cms/templates/restangular/js/controllers';

/** @const No (0) permissions */
var NONE    = 0;
/** @const Read (4) permissions */
var READ    = 4;
/** @const Execute (5) permissions */
var EXECUTE = 5;
/** @const Write (6) permissions */
var WRITE   = 6;
/** @const All (7) permissions */
var ALL     = 7;

/** @const HOLIDAYS List with all holidays */
var HOLIDAYS = {
    '01-01':     'Nieuwjaarsdag',
    '06-01':     'Drie koningen',
    'easter-2':  'Goede vrijdag',
    'easter':    '1e paasdag',
    'easter+1':  '2e paasdag',
    '26-04':     'Koningsdag',
    '05-05':     'Bevrijdingsdag',
    'easter+39': 'Hemelvaartsdag',
    'easter+49': '1e pinksterdag',
    'easter+50': '2e pinksterdag',
    '25-12':     '1e kerstdag',
    '26-12':     '2e kerstdag'
};

/** @const Start time of the day */
var TIME_START  = '06:00';
/** @const End time of the day */
var TIME_END    = '22:00';

/**
 * Prevent pressing backspace from going back to the previous page
 *
 * @source: http://stackoverflow.com/a/11112169
 * @param string key
 * @param string function
 */
jQuery(document).ready(function($) {
    jQuery(document).on('keydown', function (e) {
        if (e.which === 8 && !$(e.target).is('input, textarea')) {
            e.preventDefault();
        }
    });
});