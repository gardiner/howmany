define(['jquery', 'lodash', 'howmany.charts', 'howmany.config'], function($, _, charts, config) {
    "use strict";

    return {
        valuetable: {
            template: '#valuetable_html',
            props: ['definition', 'values'],
            data: function() {
                return {
                    show_hidden: false
                }
            },
            computed: {
                visible_values: function() {
                    return _.slice(this.values, 0, config.ALWAYS_VISIBLE_ROWS);
                },
                hidden_values: function() {
                    return _.slice(this.values, config.ALWAYS_VISIBLE_ROWS);
                }
            }
        },

        chart: {
            template: '#chart_html',
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
    };
});