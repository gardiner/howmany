"use strict";

import $ from 'jquery';
import _ from 'lodash';

import app from 'app';
import config from 'config';


$(function() {
    $('#howmany .root').each(function() {
        var $this = $(this);

        _.assign(config, JSON.parse($this.attr('options') || '{}'));
        app.init(this);
    });
});
