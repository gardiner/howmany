define(['jquery', 'lodash'], function($, _) {
    "use strict";

    return {
        is_internal: function(url, servername) {
            return url.indexOf('://' + servername) !== -1;
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