"use strict";

import $ from 'jquery';
import _ from 'lodash';

import config from 'config';


export default {
    template: require('components/_valuetable.pug').default,
    props: ['definition', 'values'],
    methods: {
        render: function(value_definition, row) {
            if (typeof value_definition === "string") {
                return row[value_definition]
            } else if (typeof value_definition === "function") {
                return value_definition.call(row, row);
            } else {
                return JSON.stringify(value_definition);
            }
        },
        click: function(click_definition, row) {
            if (click_definition) {
                return click_definition.call(row, row);
            }
        }
    },
    data: function() {
        return {
            show_hidden: false
        }
    },
    computed: {
        visible_values: function() {
            return _.slice(this.values, 0, config.ALWAYS_VISIBLE_ROWS);
        },
        hidden_values: function() {
            return _.slice(this.values, config.ALWAYS_VISIBLE_ROWS);
        }
    }
};
