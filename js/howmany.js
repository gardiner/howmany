(function($) {
"use strict";


Chart.defaults.global.animation = false;


function rel_color(index, total) {
    var rel = 1.0 * index / total,
        rel_mapped = 0.5 + 0.5 * rel; //only use opacity between 0.5 and 1
    return 'rgba(36, 43, 128, ' + rel_mapped + ')';
}

function pal_color(index, total) {
    var pal = ['#274060', '#335C81', '#65AFFF', '#1B2845', '#5899E2'];
    return pal[index % pal.length];
}

function color(index, total) {
    return pal_color(index, total);
}

function linechart(canvasElement, data) {
    return new Chart(canvasElement.getContext("2d")).Line({
        labels: data.x,
        datasets: [
            {
                label: data.label,
                strokeColor: data.color || 'rgb(31, 119, 180)',
                data: data.y
            }
        ]
    }, {
        pointDot: true,
        pointDotRadius: 2,

        bezierCurve : false,
        datasetFill: false,

        scaleFontSize: 10,
        scaleShowGridLines: true,
        scaleBeginAtZero: true,

        //this requires some calculating
        scaleOverride: false,
        scaleSteps: 7,
        scaleStepWidth: 50,
        scaleStartValue: 0
    });
}

function piechart(canvasElement, data) {
    return new Chart(canvasElement.getContext("2d")).Pie(data.values, {
        segmentStrokeWidth: 1,
        percentageInnerCutout: 50
    });
}

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

        api({endpoint: 'views'}, function(response) {
            $output
            .append("<h3>Views</h3>")
            .linechart({
                label: "Views",
                x: _.map(response.timeline, function(i) { return moment.unix(i.day * 24 * 60 * 60).format('DD.MM.YYYY'); }),
                y: _.map(response.timeline, function(i) { return i.views; })
            })
            .valuetable(response.views, [
                {label: '#', value: 'count'},
                {label: 'URL', value: 'url'}
            ]);
        });

        api({enpoint: 'useragents'}, function(response) {
            var values;

            function label(useragent) {
                var parsed,
                    label;
                if (useragent && useragent.substr(0, 1) === '{') {
                    parsed = $.parseJSON(useragent);
                    label = parsed.browser;
                    if (parsed.version) {
                        label += ' ' + parsed.version;
                    }
                    if (parsed.platform) {
                        label += ' (' + parsed.platform + ')';
                    }
                    return label;
                } else {
                    return useragent;
                }
            }

            values = _.map(response.useragents, function(i, n) {
                return {
                    value: i.count,
                    label: label(i.useragent),
                    color: color(n, response.useragents.length)
                };
            });

            $output
            .append("<h3>User agents</h3>")
            .piechart({
                label: "User agents",
                values: values
            })
            .valuetable(values, [
                {label: '#', value: 'value'},
                {label: 'User Agent', value: 'label'}
            ]);
        });

        api({enpoint: 'referers'}, function(response) {
            var values;

            values = _.map(response.referers, function(i, n) {
                return {
                    value: i.count,
                    label: i.referer || 'Unknown',
                    color: color(n, response.referers.length)
                };
            });

            $output
            .append("<h3>Referrers</h3>")
            .piechart({
                label: "Referrers",
                values: values
            })
            .valuetable(values, [
                {label: '#', value: 'value'},
                {label: 'URL', value: 'label'}
            ]);
        });
    });
});

$.fn.valuetable = function(data, definition) {
    return this.each(function() {
        var $container = $(this),
            $table = $('<table></table>').addClass('valuetable').appendTo($container);
        //table header
        $('<tr></tr>').appendTo($table).each(function() {
            var $row = $(this);
            $.each(definition, function() {
                $row.append('<th>' + this.label + '</th>');
            });
        });
        //table data
        $.each(data, function() {
            var value = this;
            $('<tr></tr>').appendTo($table).each(function() {
                var $row = $(this);
                $.each(definition, function() {
                    $row.append('<td>' + value[this.value] + '</td>');
                });
            });
        });
    });
};

$.fn.chart = function(func, data) {
    return this.each(function() {
        var $container = $(this),
            $chart;

        if ($container.is('canvas')) {
            $chart = $container;
        } else {
            $chart = $('<canvas class="chart"></canvas>').appendTo($container);
        }

        if (func) {
            func($chart[0], data);
        }
    });
};

$.fn.linechart = function(data) {
    return this.chart(linechart, data);
};

$.fn.piechart = function(data) {
    return this.chart(piechart, data);
};

}(jQuery));
