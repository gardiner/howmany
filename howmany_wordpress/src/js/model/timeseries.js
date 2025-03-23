"use strict";

import $ from 'jquery';
import _ from 'lodash';


function chart_model(data, options) {
    return {
        label: _.get(options, 'title'),
        x: _.map(data, 'slot'),
        y: _.map(data, 'value'),
    };
}


export default {
    chart_model,
};
