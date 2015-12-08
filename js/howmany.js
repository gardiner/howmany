(function($) {
"use strict";

$(function() {
    $('.howmany').each(function() {
        var $container = $(this),
            $output = $container.find('.output'),
            options = $.parseJSON($container.attr('data-options') || '{}');

        function api(params, success) {
            var url = options.apibase || '',
                data = $.extend({}, options.default_data || {}, params || {});
            $.get(url, data, success);
        }

        api({}, function(response) {
            $output.empty();
            $.each(response, function(key, value) {
                $output.append("<h3>" + key + "</h3>");
                $.each(value, function() {
                    $output.append(this.count + " " + (this.url || this.referer || this.useragent) + "<br>");
                });
            });
        });
    });
});
}(jQuery));
