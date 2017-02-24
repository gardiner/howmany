define(['jquery'], function($) {
    "use strict";

    var $container = $('#howmany'),
        defaults = {
            //constants
            DATEFORMAT: 'ddd, DD.MM.',
            ALWAYS_VISIBLE_ROWS: 10,
            COLORS: ['#274060', '#335C81', '#65AFFF', '#1B2845', '#5899E2'],
            PIECHART_THRESHOLD: 0.015,
            TIMELINE_DAYS: 15,

            //default configuration
            root: $container.find('.root').get(0),
            days_limit: 0
        };

    return $.extend(defaults, $.parseJSON($container.attr('data-options') || '{}'));
});