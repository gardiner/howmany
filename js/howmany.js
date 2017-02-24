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

requirejs(['jquery', 'lodash', 'Vue', 'moment', 'howmany.charts', 'howmany.components', 'howmany.utils', 'howmany.config'], function($, _, Vue, moment, charts, components, utils, config) {
    "use strict";

    var model,
        app;

    model = {
        route: null,
        config: config,
        views: {
            stats: {},
            definition: [
                {label: '#', value: 'count'},
                {label: '%', value: function(r) { return utils.format_percent(r.count / model.views.stats.total); }},
                {label: 'URL', value: 'url', class_: 'clickable', click: function(row) { model.route = { view: row.url }; }}
            ],
            values: [],
            timeline: {}
        },
        visits: {
            stats: {},
            entryurls: {
                definition: [
                    {label: '#', value: 'count'},
                    {label: 'Entry URL', value: 'entryurl'}
                ],
                values: []
            },
            exiturls: {
                definition: [
                    {label: '#', value: 'count'},
                    {label: 'Exit URL', value: 'exiturl'}
                ],
                values: []
            },
            timeline: {},
            views: {},
            durations: {}
        },
        referrers: {
            stats: {},
            external: {
                definition: [
                    {label: '#', value: 'value'},
                    {label: '%', value: function(r) { return utils.format_percent(r.value / model.referrers.stats.external); }},
                    {label: 'External Referrer', value: 'label', class_: 'clickable', click: function(row) { model.route = { referer: row.label }; }}
                ],
                values: []
            },
            internal: {
                definition: [
                    {label: '#', value: 'value'},
                    {label: '%', value: function(r) { return utils.format_percent(r.value / model.referrers.stats.internal); }},
                    {label: 'Internal Referrer', value: 'label', class_: 'clickable', click: function(row) { model.route = { referer: row.label }; }}
                ],
                values: []
            },
        },
        useragents: {
            stats: {},
            definition: [
                {label: '#', value: 'value'},
                {label: '%', value: function(r) { return utils.format_percent(r.value / model.useragents.stats.total); }},
                {label: 'User Agent', value: 'label'}
            ],
            values: []
        },
        platforms: {
            definition: [
                {label: '#', value: 'value'},
                {label: '%', value: function(r) { return utils.format_percent(r.value / model.useragents.stats.total); }},
                {label: 'Platform', value: 'label'}
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


    //visits are currently not adjusted by view/referer
    utils.api($.extend({endpoint: 'visits'}, app.route))
    .then(function(response) {
        var timeline = utils.prepare_timeline(response.timeline, 'day', 'count'),
            views = utils.prepare_histogram(response.views, 'viewcount', 'count'),
            durations = utils.prepare_histogram(response.durations, 'duration', 'count');

        model.visits.timeline = charts.values2xy(timeline, 'time', 'value');
        model.visits.views = charts.values2xy(views, 'bin', 'value');
        model.visits.durations = charts.values2xy(durations, function(i) { return utils.format_duration(parseInt(i.bin)); }, 'value');
        model.visits.entryurls.values = response.entryurls;
        model.visits.exiturls.values = response.exiturls;
        model.visits.stats = response.stats;
    });


    app.$watch('route', function() {
        utils.api($.extend({endpoint: 'views'}, app.route))
        .then(function(response) {
            var timeline = utils.prepare_timeline(response.timeline, 'day', 'views');

            model.views.timeline = charts.values2xy(timeline, 'time', 'value');
            model.views.values = response.views;
            model.views.stats = response.stats;
        });

        utils.api($.extend({endpoint: 'referers'}, app.route))
        .then(function(response) {
            var mapped = _.map(response.referers, function(i) {
                    return {
                        value: parseInt(i.count),
                        label: (i.referer && i.referer != '') ? i.referer : 'Unknown'
                    };
                }),
                split = _.partition(mapped, function(i) { return utils.is_internal(i.label); }),
                sum_internal = _.sum(split[0], 'value'),
                sum_external = _.sum(split[1], 'value');

            model.referrers.internal.values = split[0];
            model.referrers.external.values = split[1];
            model.referrers.stats = {total: sum_internal + sum_external, internal: sum_internal, external: sum_external};
        });

        utils.api($.extend({endpoint: 'useragents'}, app.route))
        .then(function(response) {
            var useragents = _.map(response.useragents, function(i) {
                    var parsed = utils.parse_useragent(i.useragent),
                        label = utils.format_useragent(parsed);
                    return { value: parseInt(i.count), useragent: parsed, label: label };
                }),
                platforms = _.groupBy(useragents, function(i) { return i.useragent.platform ? i.useragent.platform: 'Unknown'; }),
                counted_platforms = _.map(platforms, function(value, key) {
                    var sum = _.sum(value, 'value');
                    return { value: sum, label: key };
                });

            model.useragents.values = useragents;
            model.platforms.values = _.sortByOrder(counted_platforms, ['value'], ['desc']);
            model.useragents.stats = response.stats;
        });
    }, {immediate: true});
});
