(function($) {
"use strict";

//constants
var COLORS = ['#274060', '#335C81', '#65AFFF', '#1B2845', '#5899E2'],
    DATEFORMAT = 'DD.MM.YYYY',
    PIECHART_THRESHOLD = 0.01;

//setup charts
$.extend(Chart.defaults.global, {
    animation: false
});


function rel_color(index, total) {
    var rel = 1.0 * index / total,
        rel_mapped = 0.5 + 0.5 * rel; //only use opacity between 0.5 and 1
    return 'rgba(36, 43, 128, ' + rel_mapped + ')';
}

function pal_color(index, total) {
    var pal = COLORS;
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
                strokeColor: data.color || '#274060',
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
    var reduced = [],
        sum = _.sum(data.values, function(i) { return i.value; }),
        other = 0;

    _.each(data.values, function(i) {
        if ((1.0 * i.value / sum) >= PIECHART_THRESHOLD) {
            reduced.push(i);
        } else {
            other += i.value;
        }
    });

    if (other > 0) {
        reduced.push({label: 'Other', value: other});
    }

    _.each(reduced, function(i, n) {
        i.color = color(n, reduced.length);
    });

    return new Chart(canvasElement.getContext("2d")).Pie(reduced, {
        segmentStrokeWidth: 1,
        percentageInnerCutout: 50
    });
}

$(function() {
    $('.howmany').each(function() {
        var $container = $(this),
            $output = $container.find('.output'),
            $views = $('<div></div>').appendTo($output),
            $referers = $('<div></div>').appendTo($output),
            $useragents = $('<div></div>').appendTo($output),
            options = $.parseJSON($container.attr('data-options') || '{}');

        function api(params, success) {
            var url = options.apibase || '',
                data = $.extend({}, options.default_data || {}, params || {});
            $.get(url, data, success);
        }

        api({endpoint: 'views'}, function(response) {
            $views
            .append("<h3>Views</h3>")
            .linechart({
                label: "View History",
                x: _.map(response.timeline, function(i) { return moment.unix(i.day * 24 * 60 * 60).format(DATEFORMAT); }),
                y: _.map(response.timeline, function(i) { return i.views; })
            })
            .valuetable(response.views, [
                {label: '#', value: 'count'},
                {label: 'URL', value: 'url'}
            ]);
        });

        api({endpoint: 'useragents'}, function(response) {
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
                    value: parseInt(i.count),
                    label: label(i.useragent)
                };
            });

            $useragents
            .append("<h3>User agents</h3>")
            .piechart({
                label: "User agent ratio",
                values: values
            })
            .valuetable(values, [
                {label: '#', value: 'value'},
                {label: 'User Agent', value: 'label'}
            ]);
        });

        api({endpoint: 'referers'}, function(response) {
            var internal = [],
                external = [];

            _.each(response.referers, function(i, n) {
                var mapped = {
                        value: parseInt(i.count),
                        label: i.referer || 'Unknown'
                    };
                if (i.referer.indexOf('://' + options.servername) === -1) {
                    external.push(mapped);
                } else {
                    internal.push(mapped);
                }
            });

            $referers
            .append("<h3>Referrers</h3>")
            .piechart({
                label: "External Referrers",
                values: external,
                cssclass: 'half'
            })
            .piechart({
                label: "Internal Referrers",
                values: internal,
                cssclass: 'half'
            })
            .valuetable(external, [
                {label: '#', value: 'value'},
                {label: 'External Referrer', value: 'label'}
            ])
            .valuetable(internal, [
                {label: '#', value: 'value'},
                {label: 'Internal Referrer', value: 'label'}
            ]);
        });
    });
});

$.fn.valuetable = function(data, definition) {
    return this.each(function() {
        var $container = $(this),
            $table;

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
            $container = $('<div class="chart"></div>')
            .addClass(data.cssclass)
            .appendTo($container)
            .append("<h4>" + data.label + "</h4>");
            $chart = $('<canvas></canvas>').appendTo($container);
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
