"use strict";

import $ from 'jquery';
import _ from 'lodash';

import api from 'api';
import charts from 'model/charts';
import timeseriesmodel from 'model/timeseries';


const RESOLUTIONS = [
    {
        key: 'day',
        title: 'Tag',
    },
    {
        key: 'month',
        title: 'Monat',
    },
    {
        key: 'year',
        title: 'Jahr',
    },
];


export default {
    template: require('components/_timeseries.pug').default,
    props: ['measurement'],
    data: function() {
        return {
            resolutions: RESOLUTIONS,
            resolution: 'day',
            interval: null,
            data: null,
        };
    },
    watch: {
        resolution: 'update',
        interval: 'update',
    },
    methods: {
        update: function() {
            var self = this;
            api.measurements.get(_.get(self.measurement, 'key'), self.resolution, self.interval)
            .then(function(result) {
                self.data = timeseriesmodel.chart_model(result, {
                    title: self.measurement.title,
                    value_prop: 'value.views',
                });
            });
        }
    },
    mounted: function() {
        this.update();
    }
};
