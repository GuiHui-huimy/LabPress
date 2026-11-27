(function () {
    'use strict';

    var palette = ['#851f1f', '#448aff', '#b87333', '#2e7d32', '#6b7280', '#8e44ad'];
    var font = "'Roboto Mono', ui-monospace, Consolas, monospace";

    function readData() {
        var node = document.getElementById('lpDashboardChartData');
        if (!node) return null;
        try {
            return JSON.parse(node.textContent || '{}');
        } catch (e) {
            return null;
        }
    }

    function emptyOption(text) {
        return {
            title: {
                text: text,
                left: 'center',
                top: 'middle',
                textStyle: { color: '#999', fontSize: 12, fontWeight: 'normal', fontFamily: font }
            }
        };
    }

    function initContent(echarts, data, labels) {
        var el = document.getElementById('lpChartContent');
        if (!el) return null;
        var chart = echarts.init(el, null, { renderer: 'canvas' });
        var items = (data || []).filter(function (item) {
            return Number(item.value) > 0;
        });

        if (!items.length) {
            chart.setOption(emptyOption(labels.empty || '暂无数据'));
            return chart;
        }

        chart.setOption({
            color: palette,
            tooltip: {
                trigger: 'item',
                formatter: '{b}<br/>{c} ({d}%)',
                textStyle: { fontFamily: font, fontSize: 12 }
            },
            legend: {
                bottom: 0,
                left: 'center',
                icon: 'circle',
                itemWidth: 7,
                itemHeight: 7,
                textStyle: { color: '#666', fontSize: 11, fontFamily: font }
            },
            series: [{
                type: 'pie',
                radius: ['46%', '70%'],
                center: ['50%', '44%'],
                avoidLabelOverlap: true,
                itemStyle: {
                    borderColor: '#fff',
                    borderWidth: 2
                },
                label: {
                    show: true,
                    formatter: '{c}',
                    color: '#333',
                    fontSize: 11,
                    fontFamily: font
                },
                labelLine: { length: 8, length2: 8 },
                data: items
            }]
        });
        return chart;
    }

    function initCategories(echarts, data, labels) {
        var el = document.getElementById('lpChartCategories');
        if (!el) return null;
        var chart = echarts.init(el, null, { renderer: 'canvas' });
        var items = Array.isArray(data) ? data.slice() : [];
        items.reverse();

        if (!items.length) {
            chart.setOption(emptyOption(labels.empty || '暂无数据'));
            return chart;
        }

        chart.setOption({
            color: [palette[1]],
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                textStyle: { fontFamily: font, fontSize: 12 }
            },
            grid: { left: 12, right: 24, top: 16, bottom: 8, containLabel: true },
            xAxis: {
                type: 'value',
                minInterval: 1,
                axisLine: { show: false },
                axisTick: { show: false },
                splitLine: { lineStyle: { color: '#f0f0f0' } },
                axisLabel: { color: '#999', fontSize: 10, fontFamily: font }
            },
            yAxis: {
                type: 'category',
                data: items.map(function (item) { return item.name; }),
                axisLine: { show: false },
                axisTick: { show: false },
                axisLabel: { color: '#333', fontSize: 11, fontFamily: font }
            },
            series: [{
                type: 'bar',
                barMaxWidth: 18,
                data: items.map(function (item) { return Number(item.value) || 0; }),
                itemStyle: { borderRadius: 0 },
                label: {
                    show: true,
                    position: 'right',
                    color: '#666',
                    fontSize: 10,
                    fontFamily: font
                }
            }]
        });
        return chart;
    }

    function initTrend(echarts, data, labels) {
        var el = document.getElementById('lpChartTrend');
        if (!el) return null;
        var chart = echarts.init(el, null, { renderer: 'canvas' });
        var trend = data || {};
        var labelsAxis = trend.labels || [];
        var hasData = ['news', 'projects', 'users'].some(function (key) {
            return (trend[key] || []).some(function (value) { return Number(value) > 0; });
        });

        if (!labelsAxis.length || !hasData) {
            chart.setOption(emptyOption(labels.empty || '暂无数据'));
            return chart;
        }

        chart.setOption({
            color: [palette[0], palette[1], palette[2]],
            tooltip: {
                trigger: 'axis',
                textStyle: { fontFamily: font, fontSize: 12 }
            },
            legend: {
                top: 0,
                right: 0,
                icon: 'roundRect',
                itemWidth: 12,
                itemHeight: 6,
                textStyle: { color: '#666', fontSize: 10, fontFamily: font }
            },
            grid: { left: 8, right: 16, top: 34, bottom: 8, containLabel: true },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: labelsAxis,
                axisLine: { lineStyle: { color: '#e4e4e4' } },
                axisTick: { show: false },
                axisLabel: { color: '#999', fontSize: 10, fontFamily: font }
            },
            yAxis: {
                type: 'value',
                minInterval: 1,
                axisLine: { show: false },
                axisTick: { show: false },
                splitLine: { lineStyle: { color: '#f0f0f0' } },
                axisLabel: { color: '#999', fontSize: 10, fontFamily: font }
            },
            series: [
                {
                    name: labels.news || 'News',
                    type: 'line',
                    smooth: false,
                    symbol: 'circle',
                    symbolSize: 5,
                    data: trend.news || []
                },
                {
                    name: labels.projects || 'Projects',
                    type: 'line',
                    smooth: false,
                    symbol: 'circle',
                    symbolSize: 5,
                    data: trend.projects || []
                },
                {
                    name: labels.users || 'Users',
                    type: 'line',
                    smooth: false,
                    symbol: 'circle',
                    symbolSize: 5,
                    data: trend.users || []
                }
            ]
        });
        return chart;
    }

    function boot() {
        if (!window.echarts) {
            document.querySelectorAll('.chart-canvas').forEach(function (el) {
                el.innerHTML = '<p class="chart-fallback">图表组件未加载。</p>';
            });
            return;
        }

        var data = readData();
        if (!data) return;
        var labels = data.labels || {};

        var charts = [
            initContent(window.echarts, data.content, labels),
            initCategories(window.echarts, data.categories, labels),
            initTrend(window.echarts, data.trend, labels)
        ].filter(Boolean);

        var resizeTimer = null;
        window.addEventListener('resize', function () {
            if (resizeTimer) clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                charts.forEach(function (chart) { chart.resize(); });
            }, 150);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
