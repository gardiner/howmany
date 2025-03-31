"use strict";

import $ from 'jquery';
import _ from 'lodash';


function timeseries_data(data, options) {
    return {
        label: _.get(options, 'title'),
        x: _.map(data, 'label'),
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

function piechart_data(data, options) {
    return _.map(_.get(data, 'values'), function(item) {
        return {
            label: item.key,
            ...item,
        };
    });
}


export default {
    timeseries_data,
    barchart_data,
    piechart_data,
    list_data: piechart_data,
};
