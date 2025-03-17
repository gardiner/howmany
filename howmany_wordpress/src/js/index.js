"use strict";

import $ from 'jquery';
import _ from 'lodash';

import app from 'app';


$(function() {
    $('#howmany .root').each(function() {
        app.init(this);
    });
});
