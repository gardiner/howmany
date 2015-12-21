define(['jquery', 'lodash', 'moment', 'q', 'howmany.config'], function($, _, moment, q, config) {
    "use strict";

    return {
        api: function(params) {
            var deferred = q.defer(),
                url = config.api.base || '',
                data = $.extend({}, config.api.default_data || {}, params || {});
            $.get(url, data, function(result) { deferred.resolve(result); });
            return deferred.promise;
        },

        prepare_timeline: function(timeline, time_field, value_field) {
            var values = _.indexBy(timeline, function(i) { return moment.unix(i[time_field]).dayOfYear(); }),
                today = moment();

            return _.times(config.TIMELINE_DAYS, function(i) {
                var day = today.clone().subtract(config.TIMELINE_DAYS - i - 1, 'days'),
                    timestamp = day.unix(),
                    key = day.dayOfYear(),
                    value = values.hasOwnProperty(key.toString()) ? values[key.toString()][value_field] : 0;
                return {time: moment.unix(timestamp).format(config.DATEFORMAT), value: value};
            });
        },

        prepare_histogram: function(data, bin_field, value_field) {
            return _.map(data, function(i) { return {bin: i[bin_field], value: i[value_field]}; });
        },

        is_internal: function(url) {
            return url.indexOf('://' + config.servername) !== -1;
        },

        format_duration: function(seconds) {
            var duration = moment.duration(seconds, 'seconds');
            if (duration.asSeconds() > 59) {
                return Math.floor(duration.asMinutes()) + 'm';
            } else {
                return duration.seconds() + 's';
            }
        },

        /**
         * Parses the useragent value json format. If no json is detected, the
         * useragent is returned as the 'browser' field.
         */
        parse_useragent: function(useragent_repr) {
            if (useragent_repr && useragent_repr.substr(0, 1) === '{') {
                return $.parseJSON(useragent_repr);
            } else {
                return {browser: useragent_repr, platform: null, version: null};
            }
        },

        format_useragent: function(useragent) {
            var label = useragent.browser;
            if (useragent.version) {
                label += ' ' + useragent.version;
            }
            if (useragent.platform) {
                label += ' (' + useragent.platform + ')';
            }
            return label;
        }
    };
});