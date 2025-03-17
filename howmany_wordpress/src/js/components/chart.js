"use strict";

import $ from 'jquery';
import _ from 'lodash';

import charts from 'model/charts';


export default {
    template: require('components/_chart.pug').default,
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
};
