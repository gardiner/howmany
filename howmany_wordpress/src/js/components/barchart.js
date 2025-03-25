"use strict";

import $ from 'jquery';
import _ from 'lodash';

import api from 'api';
import measurementmodel from 'model/measurements';


export default {
    template: require('components/_barchart.pug').default,
    props: ['measurement'],
    data: function() {
        return {
            scale: null,
            data: null,
            timespan: null,
        };
    },
    watch: {
        scale: 'update',
    },
    methods: {
        /**
         * First call to update happens when scale is set to its default values.
         */
        update: function() {
            var self = this,
                scale = self.scale || {};

            api.measurements.get(_.get(self.measurement, 'key'), scale.timescale, scale.page)
            .then(function(result) {
                self.timespan = result.timespan;
                self.data = measurementmodel.barchart_data(result.values, {
                    title: self.measurement.title,
                });
            });
        }
    },
};
