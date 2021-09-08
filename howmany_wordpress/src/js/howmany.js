"use strict";

import $ from 'jquery';
import _ from 'lodash';
import Vue from 'vue';
import moment from 'moment';

import charts from './howmany.charts';
import components from './howmany.components';
import utils from './howmany.utils';
import config from './howmany.config';


$(function() {
    var app = {
            template: require('partials/_howmany.pug').default,
            data: function() {
                var self = this;

                return {
                    route: null,
                    config: config,
                    views: {
                        stats: {},
                        definition: [
                            {
                                label: '#',
                                value: 'count',
                            },
                            {
                                label: '%',
                                value: function(r) {
                                    return utils.format_percent(r.count / self.views.stats.total);
                                }
                            },
                            {
                                label: 'URL',
                                value: 'url',
                                class_: 'clickable',
                                click: function(row) {
                                    self.route = { view: row.url };
                                }
                            },
                        ],
                        values: [],
                        timeline: {}
                    },
                    visits: {
                        stats: {},
                        entryurls: {
                            definition: [
                                {
                                    label: '#',
                                    value: 'count'
                                },
                                {
                                    label: 'Entry URL',
                                    value: 'entryurl'
                                },
                            ],
                            values: []
                        },
                        exiturls: {
                            definition: [
                                {
                                    label: '#',
                                    value: 'count',
                                },
                                {
                                    label: 'Exit URL',
                                    value: 'exiturl',
                                },
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
                                {
                                    label: '#',
                                    value: 'value',
                                },
                                {
                                    label: '%',
                                    value: function(r) {
                                        return utils.format_percent(r.value / self.referrers.stats.external);
                                    }
                                },
                                {
                                    label: 'External Referrer',
                                    value: 'label',
                                    class_: 'clickable',
                                    click: function(row) {
                                        self.route = { referer: row.label };
                                    },
                                },
                            ],
                            values: []
                        },
                        internal: {
                            definition: [
                                {
                                    label: '#',
                                    value: 'value',
                                },
                                {
                                    label: '%',
                                    value: function(r) {
                                        return utils.format_percent(r.value / self.referrers.stats.internal);
                                    },
                                },
                                {
                                    label: 'Internal Referrer',
                                    value: 'label',
                                    class_: 'clickable',
                                    click: function(row) {
                                        self.route = { referer: row.label };
                                    },
                                },
                            ],
                            values: []
                        },
                    },
                    useragents: {
                        stats: {},
                        definition: [
                            {
                                label: '#',
                                value: 'value',
                            },
                            {
                                label: '%',
                                value: function(r) {
                                    return utils.format_percent(r.value / self.useragents.stats.total);
                                },
                            },
                            {
                                label: 'User Agent',
                                value: 'label',
                            },
                        ],
                        values: []
                    },
                    platforms: {
                        definition: [
                            {
                                label: '#',
                                value: 'value',
                            },
                            {
                                label: '%',
                                value: function(r) {
                                    return utils.format_percent(r.value / self.useragents.stats.total);
                                },
                            },
                            {
                                label: 'Platform',
                                value: 'label',
                            },
                        ],
                        values: []
                    }
                };
            },
            components: components,
            created: function() {
                var self = this;

                //visits are currently not adjusted by view/referer
                utils.api($.extend({endpoint: 'visits'}, self.route))
                .then(function(response) {
                    var timeline = utils.prepare_timeline(response.timeline, 'day', 'count'),
                        views = utils.prepare_histogram(response.views, 'viewcount', 'count'),
                        durations = utils.prepare_histogram(response.durations, 'duration', 'count');

                    self.visits.timeline = charts.values2xy(timeline, 'time', 'value');
                    self.visits.views = charts.values2xy(views, 'bin', 'value');
                    self.visits.durations = charts.values2xy(durations, function(i) { return utils.format_duration(parseInt(i.bin)); }, 'value');
                    self.visits.entryurls.values = response.entryurls;
                    self.visits.exiturls.values = response.exiturls;
                    self.visits.stats = response.stats;
                });


                self.$watch('route', function() {
                    utils.api($.extend({endpoint: 'views'}, self.route))
                    .then(function(response) {
                        var timeline = utils.prepare_timeline(response.timeline, 'day', 'views');

                        self.views.timeline = charts.values2xy(timeline, 'time', 'value');
                        self.views.values = response.views;
                        self.views.stats = response.stats;
                    });

                    utils.api($.extend({endpoint: 'referers'}, self.route))
                    .then(function(response) {
                        var mapped = _.map(response.referers, function(i) {
                                return {
                                    value: parseInt(i.count),
                                    label: (i.referer && i.referer != '') ? i.referer : 'Unknown'
                                };
                            }),
                            split = _.partition(mapped, function(i) { return utils.is_internal(i.label); }),
                            sum_internal = _.sumBy(split[0], 'value'),
                            sum_external = _.sumBy(split[1], 'value');

                        self.referrers.internal.values = split[0];
                        self.referrers.external.values = split[1];
                        self.referrers.stats = {total: sum_internal + sum_external, internal: sum_internal, external: sum_external};
                    });

                    utils.api($.extend({endpoint: 'useragents'}, self.route))
                    .then(function(response) {
                        var useragents = _.map(response.useragents, function(i) {
                                var parsed = utils.parse_useragent(i.useragent),
                                    label = utils.format_useragent(parsed);
                                return { value: parseInt(i.count), useragent: parsed, label: label };
                            }),
                            platforms = _.groupBy(useragents, function(i) { return i.useragent.platform ? i.useragent.platform: 'Unknown'; }),
                            counted_platforms = _.map(platforms, function(value, key) {
                                var sum = _.sumBy(value, 'value');
                                return { value: sum, label: key };
                            });

                        self.useragents.values = useragents;
                        self.platforms.values = _.orderBy(counted_platforms, ['value'], ['desc']);
                        self.useragents.stats = response.stats;
                    });
                }, {immediate: true});

            }
        };

    new Vue(app).$mount($('#howmany')[0]);
});
