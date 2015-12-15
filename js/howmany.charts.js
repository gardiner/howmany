define(['jquery', 'Chart', 'howmany.config'], function($, Chart, config) {
    "use strict";


    //setup charts
    $.extend(Chart.defaults.global, {
        animation: false,
        tooltipFontSize: 10,
        tooltipCaretSize: 4
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
        return new Chart(canvasElement.getContext("2d")).Line({
            labels: data.x || [],
            datasets: [
                {
                    label: data.label || '',
                    strokeColor: data.color || '#274060',
                    data: data.y || []
                }
            ]
        }, {
            pointDot: true,
            pointDotRadius: 1.6,

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
            sum = _.sum(data, function(i) { return i.value; }),
            other = 0;

        _.each(data, function(i) {
            if ((1.0 * i.value / sum) >= config.PIECHART_THRESHOLD) {
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


    return {
        linechart: linechart,
        piechart: piechart
    };

});