requirejs.config({
    baseUrl: requirejs.toUrl(''),
    paths: {
        "lodash": 'bower_components/lodash/lodash.min',
        "Vue": 'bower_components/vue/dist/vue.min',
        "moment": 'bower_components/moment/min/moment.min',
        "Chart": 'bower_components/Chart.js/Chart.min',
        "q": 'bower_components/q/q',

        "howmany.charts": 'js/howmany.charts',
        "howmany.utils": 'js/howmany.utils'
    }
});

requirejs(['jquery', 'lodash', 'Vue', 'moment', 'q', 'howmany.charts', 'howmany.utils'], function($, _, Vue, moment, q, charts, utils) {
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
        },
        referrers: {
            external: {
                definition: [
                    {label: '#', value: 'value'},
                    {label: 'External Referrer', value: 'label'}
                ],
                values: []
            },
            internal: {
                definition: [
                    {label: '#', value: 'value'},
                    {label: 'Internal Referrer', value: 'label'}
                ],
                values: []
            },
        },
        useragents: {
            definition: [
                {label: '#', value: 'value'},
                {label: 'User Agent', value: 'label'}
            ],
            values: []
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
                    template: partial('valuetable_html'),
                    props: ['definition', 'values']
                },
                'chart': {
                    template: partial('chart_html'),
                    props: ['label', 'values', 'type'],
                    ready: function() {
                        this.$watch('values', function() {
                            if (this.type === 'piechart') {
                                charts.piechart($(this.$el).find('canvas')[0], this.values);
                            } else {
                                charts.linechart($(this.$el).find('canvas')[0], this.values);
                            }
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

    api({endpoint: 'referers'})
    .then(function(response) {
        var mapped = _.map(response.referers, function(i) {
                return {
                    value: parseInt(i.count),
                    label: i.referer || 'Unknown'
                };
            }),
            split = _.partition(mapped, function(i) { return utils.is_internal(i.label, options.servername); });

        model.referrers.internal.values = split[0];
        model.referrers.external.values = split[1];
    });

    api({endpoint: 'useragents'})
    .then(function(response) {
        model.useragents.values = _.map(response.useragents, function(i, n) {
            return {
                value: parseInt(i.count),
                label: utils.format_useragent(i.useragent)
            };
        });
    });
});
