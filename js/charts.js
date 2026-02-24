// ============================================
// STOCKSATHI - APEXCHARTS CONFIGURATIONS
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    // ============================================
    // SALES CHART (Area Chart)
    // ============================================
    const salesChartCanvas = document.getElementById('salesChart');
    if (salesChartCanvas) {
        var salesOptions = {
            series: [{
                name: 'Sales (₹)',
                data: [65000, 78000, 82000, 71000, 89000, 95000, 102000, 98000, 115000, 122000, 128000, 135000]
            }],
            chart: {
                type: 'area',
                height: '100%',
                minHeight: 300,
                toolbar: { show: false }
            },
            colors: ['#4f82d5'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.05,
                    stops: [0, 100]
                }
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return '₹' + (value / 1000) + 'k';
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return '₹' + value.toLocaleString('en-IN');
                    }
                }
            }
        };
        new ApexCharts(salesChartCanvas, salesOptions).render();
    }

    // ============================================
    // STOCK DISTRIBUTION CHART (Donut Chart)
    // ============================================
    const stockChartCanvas = document.getElementById('stockChart');
    if (stockChartCanvas) {
        var stockOptions = {
            series: [785, 123, 45, 5],
            chart: {
                type: 'donut',
                height: '100%',
                minHeight: 300
            },
            labels: ['In Stock', 'Low Stock', 'Out of Stock', 'Expired'],
            colors: ['#10b981', '#f59e0b', '#ef4444', '#6b7280'],
            dataLabels: { enabled: false },
            legend: { position: 'bottom' },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val;
                    }
                }
            }
        };
        new ApexCharts(stockChartCanvas, stockOptions).render();
    }

    // ============================================
    // SALES DASHBOARD - DAILY SALES CHART
    // ============================================
    const dailySalesChartCanvas = document.getElementById('dailySalesChart');
    if (dailySalesChartCanvas) {
        var dailyOptions = {
            series: [{
                name: 'Daily Sales',
                data: [12500, 15800, 13200, 16700, 19200, 22100, 18900]
            }],
            chart: {
                type: 'bar',
                height: '100%',
                minHeight: 300,
                toolbar: { show: false }
            },
            colors: ['#4f82d5'],
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    horizontal: false
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return '₹' + (value / 1000) + 'k';
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return '₹' + value.toLocaleString('en-IN');
                    }
                }
            }
        };
        new ApexCharts(dailySalesChartCanvas, dailyOptions).render();
    }

    // ============================================
    // INCOME VS EXPENSE CHART
    // ============================================
    const incomeExpenseChartCanvas = document.getElementById('incomeExpenseChart');
    if (incomeExpenseChartCanvas) {
        var ieOptions = {
            series: [
                {
                    name: 'Income',
                    data: [85000, 92000, 78000, 95000, 102000, 115000]
                },
                {
                    name: 'Expense',
                    data: [45000, 52000, 48000, 55000, 58000, 62000]
                }
            ],
            chart: {
                type: 'bar',
                height: '100%',
                minHeight: 300,
                toolbar: { show: false }
            },
            colors: ['#10b981', '#ef4444'],
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    horizontal: false
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return '₹' + (value / 1000) + 'k';
                    }
                }
            },
            legend: { position: 'top' },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return '₹' + value.toLocaleString('en-IN');
                    }
                }
            }
        };
        new ApexCharts(incomeExpenseChartCanvas, ieOptions).render();
    }

    // ============================================
    // REPORTS - SALES TREND CHART
    // ============================================
    const salesTrendChartCanvas = document.getElementById('salesTrendChart');
    if (salesTrendChartCanvas && !window.reportsSalesChart) {
        var stOptions = {
            series: [{
                name: 'Daily Sales',
                data: Array.from({ length: 30 }, () => Math.floor(Math.random() * 20000) + 10000)
            }],
            chart: {
                type: 'line',
                height: '100%',
                minHeight: 300,
                toolbar: { show: false }
            },
            colors: ['#4f82d5'],
            stroke: { width: 2 },
            dataLabels: { enabled: false },
            xaxis: {
                categories: Array.from({ length: 30 }, (_, i) => i + 1),
                labels: {
                    hideOverlappingLabels: true,
                    style: { fontSize: '10px' }
                },
                tickAmount: 10
            },
            yaxis: {
                labels: {
                    formatter: function (value) { return value; }
                }
            }
        };
        new ApexCharts(salesTrendChartCanvas, stOptions).render();
    }
});
