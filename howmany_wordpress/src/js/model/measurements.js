"use strict";

import $ from 'jquery';
import _ from 'lodash';
import barchart from '../components/barchart';


function timeseries_data(data, options) {
    return {
        label: _.get(options, 'title'),
        x: _.map(data, 'slot'),
        y: _.map(data, 'value'),
    };
}

function barchart_data(data, options) {
    var values = _.get(data, 'values');
    return {
        label: _.get(options, 'title'),
        x: _.map(values, 'key'),
        y: _.map(values, 'value'),
    };
}


export default {
    timeseries_data,
    barchart_data,
};
