(function ($) {
    'use strict';

    $(document).ready(function () {
        const startDate = $('#nx_start_date');
        const notificationx = $('#nx_notificationx');
        const endDate = $('#nx_end_date');
        const comparisonFactor = $('#nx_comparison_factor');
        const currentDateNow = Date.now();
        const query_vars = get_query_vars();

        if (notificationx.length > 0) {
            notificationx.select2();
        }
        if (comparisonFactor.length > 0) {
            comparisonFactor.select2();
        }

        if (startDate.length > 0) {
            startDate.datepicker({
                dateFormat: 'dd-mm-yy',
            });
            if (Object.keys(query_vars).indexOf('start_date') >= 0) {
                startDate.datepicker('setDate', query_vars['start_date']);
            } else {
                startDate.datepicker('setDate', new Date((currentDateNow - 604800000)));
            }
        }
        if (endDate.length > 0) {
            endDate.datepicker({
                dateFormat: 'dd-mm-yy',
            });
            if (Object.keys(query_vars).indexOf('end_date') >= 0) {
                endDate.datepicker('setDate', query_vars['end_date']);
            } else {
                endDate.datepicker('setDate', currentDateNow);
            }
        }

        const analyticsForm = $('#nx-analytics-form');

        renderChart();

        analyticsForm.submit(function (e) {
            e.preventDefault();
            var notificationx = $('#nx_notificationx').val(),
                nxStartDate = $('#nx_start_date').val(),
                nxEndDate = $('#nx_end_date').val(),
                nxComparisonFactor = $('#nx_comparison_factor').val();

            if ((nxStartDate == '' && nxEndDate != '') || (nxStartDate != '' && nxEndDate == '') || (nxStartDate == '' && nxEndDate == '')) {
                alert("Please select both start and end date");
                return false;
            }

            var params = '?page=nx-analytics';

            if (nxStartDate !== '' && nxEndDate !== '') {
                params += '&start_date=' + nxStartDate + '&end_date=' + nxEndDate;
            }
            if (nxComparisonFactor) {
                params += '&comparison_factor=' + nxComparisonFactor;
            }

            if (notificationx != null) {
                params += '&notificationx=' + notificationx;
            }
            window.history.pushState('/admin.php?page=nx-analytics', 'Connects', params);

            renderChart();
        });
    });

    function renderChart() {
        var query_vars = get_query_vars();

        var comparison_factor = decodeURIComponent(query_vars['comparison_factor']);
        var notificationx = decodeURIComponent(query_vars['notificationx']);
        delete query_vars['comparison_factor'];
        delete query_vars['notificationx'];
        if (comparison_factor != 'undefined') {
            query_vars['comparison_factor'] = comparison_factor;
        }
        if (notificationx != 'undefined') {
            query_vars['notificationx'] = notificationx;
        }

        var nonce = $('#nx-analytics-form #_wpnonce').val();
        var referer = $('#nx-analytics-form #_wpnonce + input').val();

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'nx_analytics_calc',
                query_vars: query_vars,
                nonce: nonce,
                referer: referer,
            },
            success: function (response) {
                response = JSON.parse(response);
                chart(response);
            },
        });
    }

    function chart(data) {
        var stepped_size = data.datasets.stepped_size;
        delete data.datasets.stepped_size;
        var config = {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: Object.values(data.datasets),
            },
            options: {
                maintainAspectRatio: !1,
                scaleShowHorizontalLines: !0,
                scaleShowVerticalLines: !1,
                bezierCurveTension: .3,
                responsive: true,
                spanGaps: false,
                tooltips: {
                    mode: 'nearest',
                    position: 'nearest',
                    intersect: false,
                },
                hover: {
                    position: 'nearest',
                    intersect: false
                },
                scales: {
                    xAxes: [{
                        display: true,
                    }],
                    yAxes: [{
                        display: true,
                        offsetGridLines: true,
                        ticks: {
                            stepSize: parseInt(Math.ceil(stepped_size) / 5),
                        },
                    }]
                }
            }
        };

        var ctx = document.getElementById('nx_canvas').getContext('2d');

        if (window.nxChart !== undefined) {
            window.nxChart.data.labels = data.labels;
            window.nxChart.data.datasets = Object.values(data.datasets);
            window.nxChart.config.options.scales.yAxes[0].ticks.stepSize = parseInt(Math.ceil(stepped_size) / 5);
            window.nxChart.update();
            return;
        }

        window.nxChart = new Chart(ctx, config);
    }

    function get_query_vars(name = '') {
        var vars = {};
        var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (m, key, value) {
            vars[key] = value;
        });

        if (name != '') {
            return vars[name];
        }

        return vars;
    }

})(jQuery);