requirejs.config({
    baseUrl: requirejs.toUrl('js'),
    paths: {
        "lodash": '../bower_components/lodash/lodash.min',
        "Vue": '../bower_components/vue/dist/vue.min',
        "moment": '../bower_components/moment/min/moment.min',
        "Chart": '../bower_components/Chart.js/Chart.min',
        "q": '../bower_components/q/q'
    }
});

requirejs(['jquery', 'lodash', 'Vue', 'moment', 'howmany.components', 'howmany.utils', 'howmany.config'], function($, _, Vue, moment, components, utils, config) {
    "use strict";

    var model,
        app;

    model = {
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


    app = new Vue({
        el: config.root,
        template: '#howmany_html',
        data: model,
        components: components
    });


    utils.api({endpoint: 'views'})
    .then(function(response) {
        model.views.values = response.views;
        model.views.timeline = {
            x: _.map(response.timeline, function(i) { return moment.unix(i.day * 24 * 60 * 60).format(config.DATEFORMAT); }),
            y: _.map(response.timeline, function(i) { return i.views; })
        };
    });

    utils.api({endpoint: 'referers'})
    .then(function(response) {
        var mapped = _.map(response.referers, function(i) {
                return {
                    value: parseInt(i.count),
                    label: i.referer || 'Unknown'
                };
            }),
            split = _.partition(mapped, function(i) { return utils.is_internal(i.label); });

        model.referrers.internal.values = split[0];
        model.referrers.external.values = split[1];
    });

    utils.api({endpoint: 'useragents'})
    .then(function(response) {
        model.useragents.values = _.map(response.useragents, function(i, n) {
            return {
                value: parseInt(i.count),
                label: utils.format_useragent(i.useragent)
            };
        });
    });
});
