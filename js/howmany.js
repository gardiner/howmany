requirejs.config({
    baseUrl: requirejs.toUrl(''),
    paths: {
        "lodash": 'bower_components/lodash/lodash.min',
        "moment": 'bower_components/moment/min/moment.min',
        "Chart": 'bower_components/Chart.js/Chart.min',
        "q": 'bower_components/q/q',

        "howmany.charts": 'js/howmany.charts'
    }
});

requirejs(['jquery', 'lodash', 'moment', 'howmany.charts'], function($, _, moment, howmany_charts) {
    "use strict";

    //constants
    var DATEFORMAT = 'DD.MM.YYYY',
        $ = jQuery;

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
        return this.chart(howmany_charts.linechart, data);
    };

    $.fn.piechart = function(data) {
        return this.chart(howmany_charts.piechart, data);
    };

});
