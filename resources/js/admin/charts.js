import Chart from 'chart.js/auto';

let monthlySalesChart = null;
let topSellersChart = null;
let avgSellersChart = null;
let itemsChart = null;
let dayOfWeekChart = null;

let currentChartMode = 'monthly'; // 'monthly' or 'daily'
let selectedMonth = null;

const chartColors = {
    blue: '#36A2EB',
    red: '#FF6384',
    green: '#4BC0C0',
    orange: '#FF9F40',
    purple: '#9966FF',
    yellow: '#FFCE56',
};

/**
 * Get date filters from URL to pass to API
 */
function getFilterQueryParams(prefix = '?') {
    const urlParams = new URLSearchParams(window.location.search);
    const startDate = urlParams.get('start_date');
    const endDate = urlParams.get('end_date');
    if (startDate && endDate) {
        return `${prefix}start_date=${startDate}&end_date=${endDate}`;
    }
    return '';
}

/**
 * Initialize all charts on dashboard load
 */
document.addEventListener('DOMContentLoaded', function () {
    initializeCharts();
});

/**
 * Initialize all four charts
 */
async function initializeCharts() {
    try {
        await Promise.all([
            initMonthlySalesChart(),
            initTopSellersChart(),
            initAvgSellersChart(),
            initItemsChart(),
            initDayOfWeekChart(),
        ]);
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
}

/**
 * Monthly Sales Trend - Line Chart (with drill-down to daily)
 */
async function initMonthlySalesChart() {
    try {
        const queryParams = getFilterQueryParams();
        const response = await fetch(`/api/analytics/monthly-sales${queryParams}`, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,
                'Accept': 'application/json',
            },
        });

        if (!response.ok) throw new Error('Failed to fetch monthly sales data');

        const result = await response.json();
        const data = result.data;

        const ctx = document.getElementById('monthlySalesChart');
        if (!ctx) return;

        if (monthlySalesChart) monthlySalesChart.destroy();

        monthlySalesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Monthly Sales',
                        data: data.data,
                        borderColor: chartColors.blue,
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: chartColors.blue,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 7,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: async (event, activeElements) => {
                    if (currentChartMode === 'daily') return;
                    if (activeElements.length > 0) {
                        const index = activeElements[0].index;
                        if (data && data.months && data.months[index]) {
                            const monthKey = data.months[index].key;
                            await showDailySalesChart(monthKey, data.months[index].month);
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 12, weight: 'bold' },
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 12 },
                        callbacks: {
                            title: function () {
                                return '📊 Click to view daily breakdown';
                            },
                            label: function (context) {
                                return '₹ ' + formatNumber(context.parsed.y);
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '₹ ' + formatNumber(value);
                            },
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                        },
                    },
                    x: {
                        grid: {
                            display: false,
                        },
                    },
                },
            },
        });
        
        currentChartMode = 'monthly';
    } catch (error) {
        console.error('Error initializing monthly sales chart:', error);
    }
}

/**
 * Show daily sales breakdown for selected month
 */
async function showDailySalesChart(monthKey, monthLabel) {
    try {
        const response = await fetch(`/api/analytics/daily-sales?month=${monthKey}`, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            console.warn('Daily sales data not available, keeping monthly view');
            return;
        }

        const result = await response.json();
        const data = result.data;

        const ctx = document.getElementById('monthlySalesChart');
        if (!ctx) return;

        if (monthlySalesChart) monthlySalesChart.destroy();

        monthlySalesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: `Daily Sales - ${monthLabel}`,
                        data: data.data,
                        backgroundColor: chartColors.green,
                        borderRadius: 5,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 12, weight: 'bold' },
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function (context) {
                                return '₹ ' + formatNumber(context.parsed.y);
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '₹ ' + formatNumber(value);
                            },
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                        },
                    },
                },
            },
        });

        currentChartMode = 'daily';
        selectedMonth = monthKey;
        
        // Add back button
        const chartContainer = document.getElementById('monthlySalesChart').parentElement.parentElement;
        const existingBtn = chartContainer.querySelector('.btn-back-monthly');
        
        if (!existingBtn) {
            const backBtn = document.createElement('div');
            backBtn.className = 'alert alert-info mt-2 mb-0 p-2';
            backBtn.innerHTML = `
                <button type="button" class="btn btn-sm btn-primary btn-back-monthly">
                    ← Back to Monthly View
                </button>
            `;
            chartContainer.appendChild(backBtn);
            
            
            backBtn.querySelector('.btn-back-monthly').addEventListener('click', () => {
                backBtn.remove();
                initMonthlySalesChart();
            });
        }
    } catch (error) {
        console.error('Error showing daily sales:', error);
    }
}

/**
 * Top Sellers by Total Sales - Horizontal Bar Chart
 */
async function initTopSellersChart() {
    try {
        const queryParams = getFilterQueryParams();
        const response = await fetch(`/api/analytics/top-sellers${queryParams}`, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,
                'Accept': 'application/json',
            },
        });

        if (!response.ok) throw new Error('Failed to fetch top sellers data');

        const result = await response.json();
        const data = result.data;

        const ctx = document.getElementById('topSellersChart');
        if (!ctx) return;

        if (topSellersChart) topSellersChart.destroy();

        topSellersChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Total Sales (₹)',
                        data: data.data,
                        backgroundColor: [
                            chartColors.blue,
                            chartColors.green,
                            chartColors.orange,
                            chartColors.purple,
                            chartColors.red,
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                        ].slice(0, data.labels.length),
                        borderRadius: 5,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 12, weight: 'bold' },
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function (context) {
                                const transactions = data.transactions[context.dataIndex];
                                return [
                                    'Sales: ₹ ' + formatNumber(context.parsed.x),
                                    'Transactions: ' + transactions,
                                ];
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '₹ ' + formatNumber(value);
                            },
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                        },
                    },
                    y: {
                        grid: {
                            display: false,
                        },
                    },
                },
            },
        });
    } catch (error) {
        console.error('Error initializing top sellers chart:', error);
    }
}

/**
 * Best Sellers by Average Sale - Horizontal Bar Chart
 */
async function initAvgSellersChart() {
    try {
        const queryParams = getFilterQueryParams();
        const response = await fetch(`/api/analytics/avg-sellers${queryParams}`, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,
                'Accept': 'application/json',
            },
        });

        if (!response.ok) throw new Error('Failed to fetch avg sellers data');

        const result = await response.json();
        const data = result.data;

        const ctx = document.getElementById('avgSellersChart');
        if (!ctx) return;

        if (avgSellersChart) avgSellersChart.destroy();

        avgSellersChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Performance Score (%)',
                        data: data.data,
                        backgroundColor: [
                            chartColors.green,
                            chartColors.blue,
                            chartColors.purple,
                            chartColors.orange,
                            chartColors.red,
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                        ].slice(0, data.labels.length),
                        borderRadius: 5,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 12, weight: 'bold' },
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function (context) {
                                const seller = data.sellers[context.dataIndex];
                                return [
                                    'Score: ' + Number(context.parsed.x).toFixed(1) + '%',
                                    'Total Sales: ₹ ' + formatNumber(seller.total_sales),
                                    'Active Days: ' + seller.transactions,
                                ];
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '₹ ' + formatNumber(value);
                            },
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                        },
                    },
                    y: {
                        grid: {
                            display: false,
                        },
                    },
                },
            },
        });
    } catch (error) {
        console.error('Error initializing avg sellers chart:', error);
    }
}

/**
 * Item Popularity - Doughnut Chart
 */
async function initItemsChart() {
    try {
        const queryParams = getFilterQueryParams();
        const response = await fetch(`/api/analytics/items${queryParams}`, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,
                'Accept': 'application/json',
            },
        });

        if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);

        const result = await response.json();
        
        if (!result.data || !result.data.labels || result.data.labels.length === 0) {
            console.warn('No item data available', result);
            showItemsChartPlaceholder('No item sales data available yet');
            return;
        }

        const data = result.data;
        console.log('Items chart data:', data);

        const ctx = document.getElementById('itemsChart');
        if (!ctx) return;

        if (itemsChart) itemsChart.destroy();
        ctx.style.display = '';
        const parent = ctx.parentElement;
        const existingPlaceholder = parent.querySelector('.items-chart-placeholder');
        if (existingPlaceholder) {
            existingPlaceholder.remove();
        }

        itemsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        data: data.data,
                        backgroundColor: data.colors && data.colors.length > 0 
                            ? data.colors 
                            : [
                                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                            ].slice(0, data.labels.length),
                        borderColor: '#fff',
                        borderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12 },
                            usePointStyle: true,
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function (context) {
                                if (!data.items || !data.items[context.dataIndex]) {
                                    return 'Qty: ' + context.parsed + ' units';
                                }
                                const item = data.items[context.dataIndex];
                                const total = data.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((item.qty_sold / total) * 100).toFixed(1) : 0;
                                return [
                                    'Qty: ' + item.qty_sold + ' units',
                                    'Revenue: ₹ ' + formatNumber(item.revenue),
                                    'Percentage: ' + percentage + '%',
                                ];
                            },
                        },
                    },
                },
            },
        });
    } catch (error) {
        console.error('Error initializing items chart:', error);
        showItemsChartPlaceholder('Error loading items data: ' + error.message);
    }
}

/**
 * Show placeholder message for items chart
 */
function showItemsChartPlaceholder(message) {
    const ctx = document.getElementById('itemsChart');
    if (!ctx) return;

    if (itemsChart) {
        itemsChart.destroy();
        itemsChart = null;
    }

    const parent = ctx.parentElement;
    let placeholder = parent.querySelector('.items-chart-placeholder');
    if (!placeholder) {
        placeholder = document.createElement('div');
        placeholder.className = 'items-chart-placeholder alert alert-warning';
        placeholder.style.cssText = 'height: 400px; display: flex; align-items: center; justify-content: center; position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 10; margin: 0;';
        parent.style.position = 'relative';
        parent.appendChild(placeholder);
    }
    placeholder.innerHTML = `
        <div class="text-center">
            <p class="mb-2">📦 ${message}</p>
            <small class="text-muted">Make some sales to see item popularity</small>
        </div>
    `;
    ctx.style.display = 'none';
}

/**
 * Day of the Week Sales - Polar Area Chart
 */
async function initDayOfWeekChart() {
    try {
        const queryParams = getFilterQueryParams();
        const response = await fetch(`/api/analytics/day-of-week${queryParams}`, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,
                'Accept': 'application/json',
            },
        });

        if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);

        const result = await response.json();
        
        if (!result.data || !result.data.labels || result.data.labels.length === 0) {
            return;
        }

        const data = result.data;
        const ctx = document.getElementById('dayOfWeekChart');
        if (!ctx) return;

        if (dayOfWeekChart) dayOfWeekChart.destroy();

        dayOfWeekChart = new Chart(ctx, {
            type: 'polarArea',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Sales Vol (₹)',
                        data: data.data,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(199, 199, 199, 0.7)'
                        ],
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: { size: 12 },
                            usePointStyle: true,
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        callbacks: {
                            label: function (context) {
                                return 'Sales: ₹ ' + formatNumber(context.raw);
                            },
                        },
                    },
                },
                scales: {
                    r: {
                        ticks: {
                            display: false
                        }
                    }
                }
            },
        });
    } catch (error) {
        console.error('Error initializing day of week chart:', error);
    }
}

/**
 * Get auth token from localStorage
 */
function getAuthToken() {
    return localStorage.getItem('auth_token') || document.querySelector('meta[name="api-token"]')?.content || '';
}

/**
 * Format number with commas
 */
function formatNumber(num) {
    return Math.round(num).toLocaleString('en-IN');
}

// Make functions globally accessible for manual chart refresh if needed
window.initializeCharts = initializeCharts;
window.refreshCharts = initializeCharts;
