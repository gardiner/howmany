'use strict';

import $ from 'jquery';
import _ from 'lodash';

import config from 'config';


function request(endpoint, params) {
    var url = config.api.base || '',
        data = {
            endpoint,
            params: JSON.stringify(_.extend({}, config.api.default_data, params)),
        };

    return $.ajax(url, {data, method: 'post'})
    .then(function(result) {
        var status = _.get(result, 'status'),
            result = _.get(result, 'result'),
            error = _.get(result, 'error');
        if (status == 'ok') {
            return result;
        }
        throw new Error(error);
    }, function(response) {
        var status = _.get(response, 'status'),
            result = _.get(response, 'responseJSON') || _.get(response, 'responseText');

        if (result && result.error) {
            console.log("API Fehler: " + result.error);
            throw new Error(result.error);
        }

        throw new Error("Unbekannter API Fehler");
    });
}


export default {
    timescales: {
        list: function() {
            return request('timescales');
        },
    },
    measurements: {
        get: function(key, timescale, page) {
            return request('measurement', {key, timescale, page});
        },
        list: function() {
            return request('measurements');
        },
    }
};
