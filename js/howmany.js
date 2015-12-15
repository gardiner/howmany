requirejs.config({
    baseUrl: requirejs.toUrl(''),
    paths: {
        "lodash": 'bower_components/lodash/lodash.min',
        "Vue": 'bower_components/vue/dist/vue.min',
        "moment": 'bower_components/moment/min/moment.min',
        "Chart": 'bower_components/Chart.js/Chart.min',
        "q": 'bower_components/q/q',

        "howmany.charts": 'js/howmany.charts'
    }
});

requirejs(['jquery', 'lodash', 'Vue', 'moment', 'q', 'howmany.charts'], function($, _, Vue, moment, q, howmany_charts) {
    "use strict";


    var DATEFORMAT = 'DD.MM.YYYY',
        $container = $('#howmany'),
        options = $.parseJSON($container.attr('data-options') || '{}'),
        model,
        app;


    function api(params) {
        var deferred = q.defer(),
            url = options.apibase || '',
            data = $.extend({}, options.default_data || {}, params || {});
        $.get(url, data, function(result) { deferred.resolve(result); });
        return deferred.promise;
    }


    model = {
        options: options,
        views: {
            definition: [
                {label: '#', value: 'count'},
                {label: 'URL', value: 'url'}
            ],
            values: [],
            timeline: {}
        }
    };

    $.get(requirejs.toUrl('partials/howmany.html'), function(template) {
        var $template = $(template),
            partial = function(id) { return $template.filter('#' + id).html(); };

        new Vue({
            el: $container.find('.root').get(0),
            template: template,
            data: model,
            components: {
                'valuetable': {
                    props: ['definition', 'values'],
                    template: partial('valuetable_html')
                },
                'timeline': {
                    props: ['label', 'values'],
                    template: partial('chart_html'),
                    ready: function() {
                        this.$watch('values', function() {
                            howmany_charts.linechart($(this.$el).find('canvas')[0], this.values);
                        }, { immediate: true });
                    }
                }
            },
            ready: function() {
                app = this;
            }
        });
    });

    api({endpoint: 'views'})
    .then(function(response) {
        model.views.values = response.views;
        model.views.timeline = {
            x: _.map(response.timeline, function(i) { return moment.unix(i.day * 24 * 60 * 60).format(DATEFORMAT); }),
            y: _.map(response.timeline, function(i) { return i.views; })
        };
    });


    $(function() {
        var $output = $container.find('.output'),
            $referers = $('<div></div>').appendTo($output),
            $useragents = $('<div></div>').appendTo($output);

        api({endpoint: 'useragents'})
        .then(function(response) {
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

        api({endpoint: 'referers'})
        .then(function(response) {
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
