"use strict";

import $ from 'jquery';
import _ from 'lodash';
import Vue from 'vue';

import api from 'api';
import autoloader from 'components/autoloader';
import components from 'components';
import config from 'config';


autoloader.init();


const app = {
    template: require('_app.pug').default,
    data: function() {
        return {
            config: config,
            measurements: null,
            filterinput: null,
            filtervalue: null,
        };
    },
    components: components,
    methods: {
        apply_filterinput: function() {
            this.filtervalue = this.filterinput;
        },
        reset_filtervalue: function() {
            this.filtervalue = this.filterinput = null;
        },
    },
    created: function() {
        var self = this;

        $.when(api.timescales.list(), api.measurements.list())
        .then(function(timescales, measurements) {
            config.timescales = timescales;
            self.measurements = measurements;
        });
    }
};


function init(element) {
    new Vue(app).$mount(element);
}

export default {
    init,
};

