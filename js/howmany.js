(function($) {
"use strict";

$(function() {
    $('.howmany').each(function() {
        var $container = $(this),
            $output = $('<pre></pre>').appendTo($container),
            options = $.parseJSON($container.attr('data-options') || '{}');

        function api(params, success) {
            var url = options.apibase || '',
                data = $.extend({}, options.default_data || {}, params || {});
            $.get(url, data, success);
        }

        $output.text(JSON.stringify(options));
        api({}, function(response) {
            $output.text('');
            $.each(response.result, function() {
                $output.append(JSON.stringify(this)).append('<br>');
            });
        });

    });
});
}(jQuery));
