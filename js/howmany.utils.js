define(['jquery', 'lodash', 'q', 'howmany.config'], function($, _, q, config) {
    "use strict";

    return {
        api: function(params) {
            var deferred = q.defer(),
                url = config.api.base || '',
                data = $.extend({}, config.api.default_data || {}, params || {});
            $.get(url, data, function(result) { deferred.resolve(result); });
            return deferred.promise;
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