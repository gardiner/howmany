"use strict";

import $ from 'jquery';
import _ from 'lodash';

import api from 'api';
import config from 'config';
import measurementmodel from 'model/measurements';


export default {
    template: require('components/_timeselector.pug').default,
    props: ['value'],
    emits: ['input'],
    data: function() {
        return {
            timescales: config.timescales,
            data: null,
        };
    },
    computed: {
        proxy: {
            get: function() {
                return this.value;
            },
            set: function(value) {
                this.$emit('input', value);
            },
        },
        is_start: function() {
            return _.get(this.proxy, 'page') == 0;
        },
    },
    methods: {
        select_timescale: function(timescale) {
            this.proxy = {
                timescale,
                page: 0,
            };
        },
        is_selected_timescale: function(timescale) {
            return _.get(this.proxy, 'timescale') == timescale;
        },
        go_back: function() {
            this.proxy = {
                timescale: _.get(this.proxy, 'timescale'),
                page: _.get(this.proxy, 'page', 0) + 1,
            };
        },
        go_forward: function() {
            this.proxy = {
                timescale: _.get(this.proxy, 'timescale'),
                page: _.get(this.proxy, 'page', 0) - 1,
            };
        },
        reset_page: function() {
            this.proxy = {
                timescale: _.get(this.proxy, 'timescale'),
                page: 0,
            };
        },
    },
    created: function() {
        if (!this.proxy) {
            this.proxy = {
                timescale: this.timescales[0].key,
                page: 0,
            };
        }
    }
};
