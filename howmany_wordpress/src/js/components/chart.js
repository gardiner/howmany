"use strict";

import $ from 'jquery';
import _ from 'lodash';

import charts from 'model/charts';


export default {
    template: require('components/_chart.pug').default,
    props: ['label', 'values', 'type'],
    data: function() {
        return {
            chart: null,
        };
    },
    watch: {
        values: 'init_chart',
    },
    methods: {
        init_chart: function() {
            var canvas = $(this.$el).find('canvas')[0];

            if (this.chart) {
                this.chart.destroy();
            }
            if (charts.render.hasOwnProperty(this.type)) {
                this.chart = charts.render[this.type](canvas, this.values);
            }
        }
    },
    mounted: function() {
        this.init_chart();
    }
};
