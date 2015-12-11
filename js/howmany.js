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
            return;
            $.each(response, function(key, value) {
                $output.append("<h3>" + key + "</h3>");
                $.each(value, function() {
                    $output.append(this.count + " " + (this.url || this.referer || this.useragent) + "<br>");
                });
            });
        });

        api({endpoint: 'views'}, function(response) {
            var $chart,
                chart;

            $output.append("<h3>Views</h3>")
            /*
            $.each(response.views, function() {
                $output.append(JSON.stringify(this) + "<br>");
            });
            */

            $chart = $('<canvas class="chart"></canvas>').appendTo($output);
            chart = new Chart($chart[0].getContext("2d")).Line({
                labels: _.map(response.views, function(i) { return i.day; }),
                datasets: [
                    {
                        label: "Views",
                        strokeColor: 'rgb(31, 119, 180)',
                        data: _.map(response.views, function(i) { return i.views; })
                    }
                ]
            }, {
                pointDot: true,
                pointDotRadius: 2,

                bezierCurve : false,
                datasetFill: false,

                scaleFontSize: 10,
                scaleShowGridLines: true,
                scaleOverride: true,
                scaleSteps: 4,
                scaleStepWidth: 50,
                scaleStartValue: 0
            });

            $chart = $('<div class="chart"></div>').appendTo($output);
            Plotly.plot($chart[0], [
                {
                    x: _.map(response.views, function(i) { return "" + i.day; }),
                    y: _.map(response.views, function(i) { return i.views; })
                }
            ], {
                margin: {
                    t: 0,
                    l: 30,
                    r: 0,
                    b: 20
                },
                paper_bgcolor: 'none',
                plot_bgcolor: 'none'
            });
        });
    });
});
}(jQuery));
