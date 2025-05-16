"use strict";

import $ from 'jquery';
import _ from 'lodash';

import api from 'api';
import measurementmodel from 'model/measurements';


const CONFIGS = {
    timeseries: {
        chart_data: measurementmodel.timeseries_data,
        container_class: 'wide',
        is_chart_visible: true,
        chart_type: 'linechart',
    },
    discrete: {
        chart_data: measurementmodel.barchart_data,
        is_chart_visible: true,
        chart_type: 'barchart',
    },
    relative: {
        chart_data: measurementmodel.piechart_data,
        is_chart_visible: true,
        is_valuelist_visible: true,
        chart_type: 'piechart',
    },
    list: {
        chart_data: measurementmodel.list_data,
        container_class: 'wide',
        is_valuelist_visible: true,
    },
};


export default {
    template: require('components/_measurement.pug').default,
    props: ['measurement'],
    data: function() {
        return {
            scale: null,
            data: null,
            timespan: null,
            is_loading: false,
        };
    },
    computed: {
        config: function() {
            return _.get(CONFIGS, _.get(this.measurement, 'type')) || {};
        },
        chart_data: function() {
            var transform = _.get(this.config, 'chart_data');
            return transform(this.data, {
                title: this.measurement.title,
            });
        },
    },
    watch: {
        scale: 'update',
    },
    methods: {
        /**
         * First call to update happens when scale is set to its default values.
         */
        update: function() {
            this.load_data();
        },
        load_data: function(refresh) {
            var self = this,
                scale = self.scale || {};
            self.is_loading = true;
            api.measurements.get(_.get(self.measurement, 'key'), scale.timescale, scale.page, refresh)
            .then(function(result) {
                self.timespan = result.timespan;
                self.data = result.values;
                self.is_loading = false;
            }, function(error) {
                self.is_loading = false;
            });
        }
    },
};
