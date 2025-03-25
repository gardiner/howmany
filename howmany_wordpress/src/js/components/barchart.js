"use strict";

import $ from 'jquery';
import _ from 'lodash';

import api from 'api';
import charts from 'model/charts';
import measurementmodel from 'model/measurements';


export default {
    template: require('components/_barchart.pug').default,
    props: ['measurement'],
    data: function() {
        return {
            data: null,
        };
    },
    watch: {
        resolution: 'update',
    },
    methods: {
        update: function() {
            var self = this;
            api.measurements.get(_.get(self.measurement, 'key'), 'all', 0)
            .then(function(result) {
                self.data = measurementmodel.barchart_data(result, {
                    title: self.measurement.title,
                });
            });
        }
    },
    mounted: function() {
        this.update();
    }
};
