"use strict";

import $ from 'jquery';
import _ from 'lodash';

import api from 'api';
import charts from 'model/charts';
import config from 'config';
import measurementmodel from 'model/measurements';


export default {
    template: require('components/_timeseries.pug').default,
    props: ['measurement'],
    data: function() {
        return {
            timescales: config.timescales,
            timescale: config.timescales[0].key,
            page: 0,
            data: null,
        };
    },
    watch: {
        timescale: 'update',
        page: 'update',
    },
    methods: {
        update: function() {
            var self = this;
            api.measurements.get(_.get(self.measurement, 'key'), self.timescale, self.page)
            .then(function(result) {
                self.data = measurementmodel.timeseries_data(result, {
                    title: self.measurement.title,
                });
            });
        }
    },
    mounted: function() {
        this.update();
        console.log(this.timescales);
    }
};
