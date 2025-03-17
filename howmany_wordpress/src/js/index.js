"use strict";

import $ from 'jquery';
import _ from 'lodash';

import app from 'app';


$(function() {
    $('#howmany').each(function() {
        app.init(this);
    });
});
