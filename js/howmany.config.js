define(['jquery'], function($) {
    "use strict";

    var $container = $('#howmany'),
        defaults = {
            //constants
            DATEFORMAT: 'DD.MM.YYYY',
            ALWAYS_VISIBLE_ROWS: 15,

            //default configuration
            root: $container.find('.root').get(0)
        };

    return $.extend(defaults, $.parseJSON($container.attr('data-options') || '{}'));
});