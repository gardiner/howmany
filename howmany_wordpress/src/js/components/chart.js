"use strict";

import $ from 'jquery';
import _ from 'lodash';

import chartmodel from 'model/charts';


export default {
    template: require('components/_chart.pug').default,
    props: ['label', 'values', 'type', 'is_updating'],
    data: function() {
        return {
            chart: null,
        };
    },
    watch: {
        values: 'init_chart',
        is_updating: 'update_chart',
    },
    methods: {
        init_chart: function() {
            var canvas = $(this.$el).find('canvas')[0];

            if (this.chart) {
                this.chart.destroy();
            }
            if (chartmodel.hasOwnProperty(this.type)) {
                this.chart = chartmodel[this.type](canvas, this.values);
            }
        },
        update_chart: function() {
            if (!this.is_updating) {
                return;
            }
            this.chart.update('hide');
        }
    },
    mounted: function() {
        this.init_chart();
    }
};
