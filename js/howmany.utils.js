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
                return {time: timestamp, value: value};
            });
        },

        is_internal: function(url) {
            return url.indexOf('://' + config.servername) !== -1;
        },

        format_useragent: function(useragent) {
            var parsed,
                label = useragent;

            if (useragent && useragent.substr(0, 1) === '{') {
                parsed = $.parseJSON(useragent);
                label = parsed.browser;
                if (parsed.version) {
                    label += ' ' + parsed.version;
                }
                if (parsed.platform) {
                    label += ' (' + parsed.platform + ')';
                }
            }

            return label;
        }
    };
});