"use strict";

import $ from 'jquery';
import _ from 'lodash';

import charts from './howmany.charts';
import config from './howmany.config';


export default {
    valuetable: {
        template: require('partials/_valuetable.pug').default,
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
    },

    chart: {
        template: require('partials/_chart.pug').default,
        props: ['label', 'values', 'type'],
        mounted: function() {
            var chart;

            this.$watch('values', function() {
                if (chart) {
                    chart.destroy();
                }
                if (charts.render.hasOwnProperty(this.type)) {
                    chart = charts.render[this.type]($(this.$el).find('canvas')[0], this.values);
                }
            }, { immediate: true });
        }
    }
};
