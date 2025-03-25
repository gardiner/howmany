"use strict";

import $ from 'jquery';
import _ from 'lodash';

import api from 'api';
import measurementmodel from 'model/measurements';


export default {
    template: require('components/_piechart.pug').default,
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
        update: function() {
            var self = this,
                scale = self.scale || {};

            api.measurements.get(_.get(self.measurement, 'key'), scale.timescale, scale.page)
            .then(function(result) {
                self.timespan = result.timespan;
                self.data = measurementmodel.piechart_data(result.values, {
                    title: self.measurement.title,
                });
            });
        },
    },
    mounted: function() {
        this.update();
    }
};
