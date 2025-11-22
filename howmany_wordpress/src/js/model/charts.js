"use strict";

import $ from 'jquery';
import _ from 'lodash';
import Chart from 'chart.js/auto';

import config from 'config';


export const CHART_BG = '#ffffff77';
export const BLUE = '#274060';
export const GRAY = '#cccccc';


//setup charts
_.extend(Chart.defaults.global, {
    animation: false,
    tooltipFontSize: 10,
    tooltipCaretSize: 4
});

Chart.register({
    id: 'bg_plugin',
    beforeDraw: function (chart, args, options) {
        if (chart.chartArea && chart.config.options.chartBackgroundColor) {
            var { ctx, chartArea } = chart;

            ctx.save();
            ctx.fillStyle = chart.config.options.chartBackgroundColor;
            ctx.fillRect(chartArea.left, chartArea.top, chartArea.right - chartArea.left, chartArea.bottom - chartArea.top);
            ctx.restore();
        }
    }
});


function rel_color(index, total) {
    var rel = 1.0 * index / total,
        rel_mapped = 0.5 + 0.5 * rel; //only use opacity between 0.5 and 1
    return 'rgba(36, 43, 128, ' + rel_mapped + ')';
}

function pal_color(index, total) {
    var pal = config.COLORS;
    return pal[index % pal.length];
}

function color(index, total) {
    return pal_color(index, total);
}


function linechart(canvasElement, data) {
    return new Chart(canvasElement.getContext("2d"), {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            chartBackgroundColor: CHART_BG,
            scales: {
                y: {
                    min: 0,
                },
            },
        },
        data: {
            labels: data.x || [],
            datasets: [
                {
                    label: data.label || '',
                    strokeColor: data.color || BLUE,
                    data: data.y || [],
                }
            ]
        },
    });
}

function piechart(canvasElement, data) {
    var reduced = [],
        sum = _.sumBy(data, 'value'),
        other = 0;

    _.each(data, function(i) {
        if ((1.0 * i.value / sum) >= config.PIECHART_THRESHOLD) {
            reduced.push(i);
        } else {
            other += i.value;
        }
    });

    if (other > 0) {
        reduced.push({label: 'Andere', value: other});
    }

    _.each(reduced, function(i, n) {
        i.color = color(n, reduced.length);
    });

    return new Chart(canvasElement.getContext("2d"), {
        type: 'doughnut',
        options: {
            plugins: {
                legend: {
                    display: false,
                },
            },
        },
        data: {
            datasets: [
                {
                    data: reduced,
                },
            ],
            labels: _.map(reduced, 'label'),
        },
    });
}

function barchart(canvasElement, data) {
    return new Chart(canvasElement.getContext("2d"), {
        type: 'bar',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            chartBackgroundColor: CHART_BG,
        },
        data: {
            labels: data.x || [],
            datasets: [
                {
                    label: data.label || '',
                    fillColor: data.color || BLUE,
                    data: data.y || []
                }
            ]
        },
    });
}

export default {
    CHART_BG,
    BLUE,
    GRAY,

    linechart,
    piechart,
    barchart
};
