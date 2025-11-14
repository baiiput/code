/**
 * Dashboard JavaScript
 */

let salesChart = null;
let expensesChart = null;

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();

    // Period selector change
    const periodSelector = document.getElementById('periodSelector');
    if (periodSelector) {
        periodSelector.addEventListener('change', function() {
            loadDashboardData(this.value);
        });
    }

    // Refresh button
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            const period = periodSelector ? periodSelector.value : 'month';
            loadDashboardData(period);
        });
    }
});

async function loadDashboardData(period = 'month') {
    try {
        const response = await fetch(`api/dashboard.php?period=${period}`);
        const data = await response.json();

        if (data.success) {
            updateSummaryCards(data.summary);
            updateCharts(data.charts);
            updateTopProducts(data.top_products);
            updateLowStock(data.low_stock_products);
            updateRecentSales(data.recent_sales);
            updatePendingPayments(data.pending_payments);
        } else {
            console.error('Failed to load dashboard data:', data.message);
        }
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

function updateSummaryCards(summary) {
    // Sales
    document.getElementById('totalSales').textContent = formatCurrency(summary.sales.total_amount);
    document.getElementById('salesCount').textContent = `${summary.sales.total_transactions} transaksi`;

    // Purchases
    document.getElementById('totalPurchases').textContent = formatCurrency(summary.purchases.total_amount);
    document.getElementById('purchasesCount').textContent = `${summary.purchases.total_transactions} transaksi`;

    // Expenses
    document.getElementById('totalExpenses').textContent = formatCurrency(summary.expenses.total_amount);
    document.getElementById('expensesCount').textContent = `${summary.expenses.total_transactions} transaksi`;

    // Profit
    document.getElementById('netProfit').textContent = formatCurrency(summary.profit.net_profit);
    document.getElementById('profitMargin').textContent = `${summary.profit.profit_margin}% margin`;
}

function updateCharts(charts) {
    // Sales Trend Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        const labels = charts.sales_trend.map(item => {
            const [year, month] = item.month.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('id-ID', { month: 'short', year: '2-digit' });
        });
        const salesData = charts.sales_trend.map(item => parseFloat(item.total));

        if (salesChart) {
            salesChart.destroy();
        }

        salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Penjualan',
                    data: salesData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                            }
                        }
                    }
                }
            }
        });
    }

    // Expenses by Category Chart
    const expensesCtx = document.getElementById('expensesChart');
    if (expensesCtx) {
        const labels = charts.expenses_by_category.map(item => item.category);
        const expensesData = charts.expenses_by_category.map(item => parseFloat(item.total));

        const colors = [
            '#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6',
            '#ec4899', '#14b8a6', '#f97316', '#06b6d4', '#84cc16'
        ];

        if (expensesChart) {
            expensesChart.destroy();
        }

        expensesChart = new Chart(expensesCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: expensesData,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed);
                                return label + ': ' + value;
                            }
                        }
                    }
                }
            }
        });
    }
}

function updateTopProducts(products) {
    const tbody = document.getElementById('topProductsTable');
    if (!tbody) return;

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    tbody.innerHTML = products.map(product => `
        <tr>
            <td>${product.name}</td>
            <td>${product.category}</td>
            <td>${product.total_sold}</td>
            <td>${formatCurrency(product.revenue)}</td>
        </tr>
    `).join('');
}

function updateLowStock(products) {
    const tbody = document.getElementById('lowStockTable');
    if (!tbody) return;

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Semua stok aman</td></tr>';
        return;
    }

    tbody.innerHTML = products.map(product => `
        <tr>
            <td>${product.sku}</td>
            <td>${product.name}</td>
            <td class="text-danger">${product.stock}</td>
            <td>${product.min_stock}</td>
        </tr>
    `).join('');
}

function updateRecentSales(sales) {
    const tbody = document.getElementById('recentSalesTable');
    if (!tbody) return;

    if (sales.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    tbody.innerHTML = sales.map(sale => `
        <tr>
            <td>${sale.invoice_number}</td>
            <td>${formatDate(sale.sale_date)}</td>
            <td>${sale.customer_name}</td>
            <td>${formatCurrency(sale.total_amount)}</td>
            <td>${getPaymentStatusBadge(sale.payment_status)}</td>
        </tr>
    `).join('');
}

function updatePendingPayments(payments) {
    const tbody = document.getElementById('pendingPaymentsTable');
    if (!tbody) return;

    if (payments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Tidak ada piutang</td></tr>';
        return;
    }

    tbody.innerHTML = payments.map(payment => `
        <tr>
            <td>${payment.invoice_number}</td>
            <td>${payment.customer_name}</td>
            <td>${formatCurrency(payment.total_amount)}</td>
            <td class="text-danger">${formatCurrency(payment.remaining)}</td>
        </tr>
    `).join('');
}
