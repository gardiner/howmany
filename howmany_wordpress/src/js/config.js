"use strict";

import $ from 'jquery';
import _ from 'lodash';


var config = {
        //constants
        DATEFORMAT: 'ddd, DD.MM.',
        ALWAYS_VISIBLE_ROWS: 10,
        COLORS: ['#274060', '#335C81', '#65AFFF', '#1B2845', '#5899E2'],
        PIECHART_THRESHOLD: 0.015,
        TIMELINE_DAYS: 20,

        //default configuration
        days_limit: 0
    };

$(function() {
    var $container = $('#howmany');

    console.log($container.attr('options'));
    _.assign(config, $.parseJSON($container.attr('options') || '{}'));
    console.log(config);
});

export default config;
