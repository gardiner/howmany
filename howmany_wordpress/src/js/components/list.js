"use strict";

import $ from 'jquery';
import _ from 'lodash';

import api from 'api';
import charts from 'model/charts';
import measurementmodel from 'model/measurements';


export default {
    template: require('components/_list.pug').default,
    props: ['measurement'],
    data: function() {
        return {
            data: null,
        };
    },
    methods: {
        update: function() {
            var self = this;
            api.measurements.get(_.get(self.measurement, 'key'), 'all', null)
            .then(function(result) {
                self.data = measurementmodel.list_data(result);
            });
        },
    },
    mounted: function() {
        this.update();
    }
};
