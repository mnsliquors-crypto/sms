<?php
// Enhanced Dashboard – KPI Cards + 5 Charts
require_once __DIR__ . '/../../classes/Cache.php';
$cache = new Cache(300); // 5-minute cache

// Low stock items (Keeping PHP for initial load as it's a small list)
$low_stock_data = $cache->get('dashboard_low_stock');
if(!$low_stock_data){
    $low_stock_qry = $conn->query("
        SELECT i.name,
         COALESCE((SELECT SUM(CASE 
                    WHEN t.type IN ('purchase', 'opening_stock', 'adjustment') THEN ti.quantity 
                    WHEN t.type = 'sale' THEN -ti.quantity 
                    WHEN t.type = 'return' THEN (CASE WHEN EXISTS(SELECT 1 FROM entity_list WHERE id = t.entity_id AND entity_type = 'Supplier') THEN -ti.quantity ELSE ti.quantity END)
                    ELSE 0 END) FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE ti.item_id=i.id),0) as available
        FROM item_list i WHERE i.status=1
        HAVING available < 10
        ORDER BY available ASC
        LIMIT 8
    ");
    $low_stock_list = [];
    while($lsr = $low_stock_qry->fetch_assoc()) $low_stock_list[] = $lsr;

    function kpi($conn, $sql) { return floatval($conn->query($sql)->fetch_array()[0] ?? 0); }
    $low_stock_count = kpi($conn, "SELECT COUNT(*) FROM (SELECT i.id, COALESCE((SELECT SUM(CASE WHEN t.type IN ('purchase', 'opening_stock', 'adjustment') THEN ti.quantity WHEN t.type = 'sale' THEN -ti.quantity WHEN t.type = 'return' THEN (CASE WHEN EXISTS(SELECT 1 FROM entity_list WHERE id = t.entity_id AND entity_type = 'Supplier') THEN -ti.quantity ELSE ti.quantity END) ELSE 0 END) FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE ti.item_id=i.id),0) as available FROM item_list i WHERE i.status=1 HAVING available < 10) as t");

    $low_stock_data = ['list' => $low_stock_list, 'count' => $low_stock_count];
    $cache->set('dashboard_low_stock', $low_stock_data);
}
$low_stock_list = $low_stock_data['list'];
$low_stock_count_init = $low_stock_data['count'];
?>
<style>
/* ── Dashboard Styles ──────────────────────────────────────────── */
.kpi-card {
    border-radius: 10px;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,.08);
    overflow: hidden;
    transition: transform .2s, box-shadow .2s;
    cursor: pointer;
    text-decoration: none !important;
    display: block;
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,.13); }
.kpi-card .card-body { padding: 1rem 1.25rem .8rem; }
.kpi-icon { font-size: 2rem; opacity: .25; position: absolute; right: 12px; top: 10px; }
.kpi-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; font-weight: 600; opacity: .8; }
.kpi-value { font-size: 1.55rem; font-weight: 700; line-height: 1.2; margin: .15rem 0; }
.kpi-badge { font-size: .72rem; border-radius: 20px; padding: 2px 8px; display: inline-block; }
.badge-up   { background: rgba(255,255,255,.25); }
.badge-down { background: rgba(0,0,0,.12); }
.chart-card { border-radius: 10px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,.08); }
.chart-card .card-header { border-bottom: 1px solid rgba(0,0,0,.06); background: transparent; padding: .75rem 1.1rem; }
.chart-card .card-title { font-size: .9rem; font-weight: 600; margin: 0; }
.chart-filter-btn { font-size: .72rem; padding: 2px 10px; border-radius: 20px; }
.low-stock-badge { border-radius: 20px; padding: 2px 10px; font-size: .75rem; }
#autoRefreshWrap { font-size:.82rem; }
.section-title { font-size: .7rem; text-transform: uppercase; letter-spacing: .1em; color: #888; margin: .5rem 0 .3rem; font-weight: 700; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="m-0 font-weight-bold">
        <i class="fas fa-tachometer-alt text-primary mr-2"></i>
        <?php echo $_settings->info('name') ?> &mdash; Dashboard
        <small class="ml-3 text-primary"><i class="far fa-clock mr-1"></i><span id="systemClock" style="font-weight: 600;"></span></small>
    </h4>
    <div id="autoRefreshWrap" class="d-flex align-items-center">
        <div class="icheck-primary mr-2">
            <input type="checkbox" id="autoRefreshToggle">
            <label for="autoRefreshToggle">Auto-Refresh (30s)</label>
        </div>
        <span class="badge badge-pill badge-info mr-2" id="refreshCountdown" style="display:none; font-size: 0.75rem;">30s</span>
        <span class="text-muted small" id="lastRefreshed">Updated: <?php echo date('h:i:s A') ?></span>
    </div>
</div>

<!-- Section: Today's Overview -->
<div class="section-title mb-2 mt-2 px-2">Today's Performance</div>
<div class="row mb-4">
<!-- Today POS Sales -> Green -->
    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <a href="<?php echo base_url ?>admin/?page=sales" class="card kpi-card kpi-green">
            <div class="card-body">
                <i class="fas fa-cash-register kpi-icon"></i>
                <div class="kpi-label">Today POS</div>
                <div class="kpi-value text-success">Rs. <span id="kpi_td_pos_sales">0.00</span></div>
            </div>
        </a>
    </div>

    <!-- Today Sales -> Blue -->
    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <a href="<?php echo base_url ?>admin/?page=reports/sales" class="card kpi-card kpi-blue">
            <div class="card-body">
                <i class="fas fa-chart-line kpi-icon"></i>
                <div class="kpi-label">Total Sales</div>
                <div class="kpi-value">Rs. <span id="kpi_td_sales">0.00</span></div>
                <span id="kpi_td_sales_pct_wrap" class="kpi-badge">
                    <i id="kpi_td_sales_pct_icon" class="fas"></i>
                    <span id="kpi_td_sales_pct">0%</span>
                </span>
            </div>
        </a>
    </div>
    
    <!-- Today Purchase -> Purple -->
    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <a href="<?php echo base_url ?>admin/?page=reports/purchase" class="card kpi-card kpi-purple">
            <div class="card-body">
                <i class="fas fa-shopping-basket kpi-icon"></i>
                <div class="kpi-label">Today Purchase</div>
                <div class="kpi-value">Rs. <span id="kpi_td_purchase">0.00</span></div>
                <span id="kpi_td_purchase_pct_wrap" class="kpi-badge">
                    <i id="kpi_td_purchase_pct_icon" class="fas"></i>
                    <span id="kpi_td_purchase_pct">0%</span>
                </span>
            </div>
        </a>
    </div>

    <!-- Today Expenses -> Red -->
    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <a href="<?php echo base_url ?>admin/?page=reports/daily_expense" class="card kpi-card kpi-red">
            <div class="card-body">
                <i class="fas fa-receipt kpi-icon"></i>
                <div class="kpi-label">Today Expenses</div>
                <div class="kpi-value">Rs. <span id="kpi_td_expense">0.00</span></div>
                <span id="kpi_td_expense_pct_wrap" class="kpi-badge">
                    <i id="kpi_td_expense_pct_icon" class="fas"></i>
                    <span id="kpi_td_expense_pct">0%</span>
                </span>
            </div>
        </a>
    </div>

</div>

<!-- Section: Monthly Overview -->
<div class="section-title mb-2 mt-2 px-2">This Month's Summary (<?php echo date('F Y') ?>)</div>
<div class="row mb-4">
    <!-- Monthly Sales -> Blue -->
    <div class="col-12 col-sm-6 col-md-4 mb-3">
        <a href="<?php echo base_url ?>admin/?page=reports/sales" class="card kpi-card kpi-blue">
            <div class="card-body">
                <i class="fas fa-chart-bar kpi-icon"></i>
                <div class="kpi-label">Monthly Sales</div>
                <div class="kpi-value">Rs. <span id="kpi_month_sales">0.00</span></div>
            </div>
        </a>
    </div>

    <!-- Monthly Purchase -> Purple -->
    <div class="col-12 col-sm-6 col-md-4 mb-3">
        <a href="<?php echo base_url ?>admin/?page=reports/purchase" class="card kpi-card kpi-purple">
            <div class="card-body">
                <i class="fas fa-shopping-cart kpi-icon"></i>
                <div class="kpi-label">Monthly Purchase</div>
                <div class="kpi-value">Rs. <span id="kpi_month_purchase">0.00</span></div>
            </div>
        </a>
    </div>

    <!-- Monthly Expenses -> Red -->
    <div class="col-12 col-sm-6 col-md-4 mb-3">
        <a href="<?php echo base_url ?>admin/?page=reports/daily_expense" class="card kpi-card kpi-red">
            <div class="card-body">
                <i class="fas fa-file-invoice-dollar kpi-icon"></i>
                <div class="kpi-label">Monthly Expenses</div>
                <div class="kpi-value">Rs. <span id="kpi_month_expense">0.00</span></div>
            </div>
        </a>
    </div>
</div>

<!-- Section: Account Balances -->
<div class="section-title mb-2 mt-2 px-2">Dynamic Account Balances</div>
<div class="row mb-4" id="accounts_container">
    <div class="col-12 text-center py-3"><i class="fas fa-spinner fa-spin"></i> Loading accounts...</div>
</div>

<div class="section-title mb-2 mt-2 px-2">Operational Alerts & Metrics</div>
<div class="row mb-4">
    <!-- Low Stock -> Red -->
    <div class="col-12 col-sm-6 col-md-4 mb-3">
        <a href="<?php echo base_url ?>admin/?page=reports/stock_low" class="card kpi-card kpi-red kpi-pulse">
            <div class="card-body">
                <i class="fas fa-exclamation-triangle kpi-icon"></i>
                <div class="kpi-label">Low Stock items</div>
                <div class="kpi-value"><span id="kpi_low_stock"><?php echo number_format($low_stock_count_init) ?></span> <small>Items</small></div>
            </div>
        </a>
    </div>
    
    <!-- Receivable -> Orange -->
    <div class="col-12 col-sm-6 col-md-4 mb-3">
        <a href="<?php echo base_url ?>admin/?page=reports/customer_outstanding" class="card kpi-card kpi-orange">
            <div class="card-body">
                <i class="fas fa-hand-holding-usd kpi-icon"></i>
                <div class="kpi-label">Total Receivables</div>
                <div class="kpi-value">Rs. <span id="kpi_receivable">0.00</span></div>
            </div>
        </a>
    </div>

    <!-- Total Payable -> Red -->
    <div class="col-12 col-sm-6 col-md-4 mb-3">
        <a href="<?php echo base_url ?>admin/?page=reports/vendor_outstanding" class="card kpi-card kpi-red">
            <div class="card-body">
                <i class="fas fa-file-invoice kpi-icon"></i>
                <div class="kpi-label">Total Payable</div>
                <div class="kpi-value">Rs. <span id="kpi_payable">0.00</span></div>
            </div>
        </a>
    </div>
</div>

<!-- Section: Profit & Discounts -->
<div class="section-title mb-2 mt-2 px-2">Profit & Discounts Analysis</div>
<div class="row mb-3">
    <!-- Today Profit -> Gold -->
    <div class="col-12 col-sm-6 col-md-2 mb-3" style="flex: 0 0 20%; max-width: 20%;">
        <a href="<?php echo base_url ?>admin/?page=reports/profit" class="card kpi-card kpi-gold">
            <div class="card-body">
                <i class="fas fa-coins kpi-icon"></i>
                <div class="kpi-label">Today Profit</div>
                <div class="kpi-value" style="font-size:1.2rem !important;">Rs. <span id="kpi_td_profit">0.00</span></div>
            </div>
        </a>
    </div>
    
    <!-- Today Discount -> Cyan -->
    <div class="col-12 col-sm-6 col-md-2 mb-3" style="flex: 0 0 20%; max-width: 20%;">
        <a href="<?php echo base_url ?>admin/?page=reports/sales" class="card kpi-card kpi-cyan">
            <div class="card-body">
                <i class="fas fa-percent kpi-icon"></i>
                <div class="kpi-label">Today Discount</div>
                <div class="kpi-value" style="font-size:1.2rem !important;">Rs. <span id="kpi_td_discount">0.00</span></div>
            </div>
        </a>
    </div>

    <!-- Monthly Profit -> Gold -->
    <div class="col-12 col-sm-6 col-md-2 mb-3" style="flex: 0 0 20%; max-width: 20%;">
        <a href="<?php echo base_url ?>admin/?page=reports/profit" class="card kpi-card kpi-gold">
            <div class="card-body">
                <i class="fas fa-calendar-check kpi-icon"></i>
                <div class="kpi-label">Monthly Profit</div>
                <div class="kpi-value" style="font-size:1.2rem !important;">Rs. <span id="kpi_month_profit">0.00</span></div>
            </div>
        </a>
    </div>

    <!-- Monthly Discount -> Cyan -->
    <div class="col-12 col-sm-6 col-md-2 mb-3" style="flex: 0 0 20%; max-width: 20%;">
        <a href="<?php echo base_url ?>admin/?page=reports/sales" class="card kpi-card kpi-cyan">
            <div class="card-body">
                <i class="fas fa-tags kpi-icon"></i>
                <div class="kpi-label">Monthly Discount</div>
                <div class="kpi-value" style="font-size:1.2rem !important;">Rs. <span id="kpi_month_discount">0.00</span></div>
            </div>
        </a>
    </div>

    <!-- Cumulative Profit -> Gold -->
    <div class="col-12 col-sm-6 col-md-2 mb-3" style="flex: 0 0 20%; max-width: 20%;">
        <a href="<?php echo base_url ?>admin/?page=reports/profit" class="card kpi-card kpi-gold">
            <div class="card-body">
                <i class="fas fa-piggy-bank kpi-icon"></i>
                <div class="kpi-label">Cumulative Profit</div>
                <div class="kpi-value" style="font-size:1.2rem !important;">Rs. <span id="kpi_total_profit">0.00</span></div>
            </div>
        </a>
    </div>
</div>

<!-- ── Row 2: Charts ─────────────────────────────────────────────────────── -->
<div class="row mb-3">
    <!-- Left col -->
    <div class="col-lg-8">
        <!-- Sales Trend -->
        <div class="card chart-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-chart-line text-success mr-1"></i>Daily Sales Trend</h3>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary chart-filter-btn trend-btn active" data-days="7">7 Days</button>
                    <button type="button" class="btn btn-outline-secondary chart-filter-btn trend-btn" data-days="30">30 Days</button>
                </div>
            </div>
            <div class="card-body p-2">
                <canvas id="salesTrendChart" style="height:200px;max-height:200px"></canvas>
            </div>
        </div>
        <!-- Monthly Sales vs Purchase -->
        <div class="card chart-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar text-primary mr-1"></i>Monthly Sales vs Purchase</h3>
            </div>
            <div class="card-body p-2">
                <canvas id="monthlyChart" style="height:200px;max-height:200px"></canvas>
            </div>
        </div>
    </div>

    <!-- Right col -->
    <div class="col-lg-4">
        <!-- Payment Mode Pie -->
        <div class="card chart-card mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie text-warning mr-1"></i>Payment Mode (This Month)</h3>
            </div>
            <div class="card-body p-2 text-center">
                <canvas id="payModeChart" style="height:180px;max-height:180px"></canvas>
            </div>
        </div>
        <!-- Top 10 Items -->
        <div class="card chart-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-trophy text-danger mr-1"></i>Top 10 Items (This Month)</h3>
            </div>
            <div class="card-body p-2">
                <canvas id="topItemsChart" style="height:190px;max-height:190px"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ── Row 3: Low Stock Alert + Quick Actions ────────────────────────────── -->
<div class="row">
    <div class="col-lg-5 mb-3">
        <div class="card chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title text-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Low Stock Alerts</h3>
                <a href="<?php echo base_url ?>admin/?page=reports/stock_low" class="btn btn-sm btn-outline-danger">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr><th class="pl-3">Item</th><th class="text-center">Stock</th><th class="text-center pr-3">Status</th></tr></thead>
                    <tbody>
                    <?php if (empty($low_stock_list)): ?>
                        <tr><td colspan="3" class="text-center text-success py-3"><i class="fas fa-check-circle"></i> All items are well stocked</td></tr>
                    <?php endif; ?>
                    <?php foreach ($low_stock_list as $row): ?>
                        <tr>
                            <td class="pl-3"><?php echo htmlspecialchars($row['name']) ?></td>
                            <td class="text-center"><strong><?php echo number_format($row['available'],2) ?></strong></td>
                            <td class="text-center pr-3">
                                <span class="badge low-stock-badge <?php echo $row['available'] <= 0 ? 'badge-danger' : 'badge-warning' ?>">
                                    <?php echo $row['available'] <= 0 ? 'Out of Stock' : 'Low' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-7 mb-3">
        <div class="card chart-card h-100">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-bolt text-warning mr-1"></i>Quick Actions</h3></div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $actions = [
                        ['POS Sale',         'admin/?page=pos',              'fa-cash-register',   'btn-success'],
                        ['New Sale',         'admin/?page=sales/manage_sale', 'fa-cart-plus',       'btn-primary'],
                        ['New Purchase',     'admin/?page=purchases/manage_purchase', 'fa-shopping-basket', 'btn-info'],
                        ['Fund Transfer',    'admin/?page=master/accounts/manage_transfer','fa-exchange-alt', 'btn-dark'],
                        ['Record Expense',   'admin/?page=expenses/manage_expense',         'fa-receipt',         'btn-danger'],
                        ['Opening Balance',  'admin/?page=transactions/opening_balances/create', 'fa-balance-scale', 'btn-secondary'],
                        ['Sales Report',     'admin/?page=reports/sales',    'fa-chart-bar',       'btn-warning'],
                        ['Stock Report',     'admin/?page=reports/stock_current','fa-cubes',       'btn-dark'],
                        ['P&L Report',       'admin/?page=reports/profit',   'fa-coins',           'btn-primary'],
                    ];
                    foreach ($actions as $a): ?>
                    <div class="col-6 col-md-3 mb-2">
                        <a href="<?php echo base_url.$a[1] ?>" class="btn btn-block <?php echo $a[3] ?> btn-sm">
                            <i class="fas <?php echo $a[2] ?> mr-1"></i> <?php echo $a[0] ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Mini summary table -->
                <div class="row mt-2">
                    <div class="col-12">
                        <p class="section-title">Today at a Glance</p>
                        <table class="table table-sm table-bordered mb-2">
                            <tr>
                                <td style="width: 30%;" id="glance_date"><?php echo date('l, d F') ?></td>
                                <td class="text-success"><strong>Sales: Rs. <span id="glance_td_sales">0.00</span></strong></td>
                                <td class="text-primary"><strong>Purch: Rs. <span id="glance_td_purchase">0.00</span></strong></td>
                                <td class="text-danger"><strong>Exp: Rs. <span id="glance_td_expense">0.00</span></strong></td>
                            </tr>
                        </table>

                        <p class="section-title">Monthly at a Glance (<?php echo date('F') ?>)</p>
                        <table class="table table-sm table-bordered mb-0">
                            <tr>
                                <td style="width: 30%;">MTD Summary</td>
                                <td class="text-success"><strong>Sales: Rs. <span id="glance_month_sales">0.00</span></strong></td>
                                <td class="text-primary"><strong>Purch: Rs. <span id="glance_month_purchase">0.00</span></strong></td>
                                <td class="text-danger"><strong>Exp: Rs. <span id="glance_month_expense">0.00</span></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Charts JS ─────────────────────────────────────────────────────────── -->
<script>
(function(){
    var _api = '<?php echo base_url ?>admin/reports/report_api.php';
    var salesTrendChart, monthlyChart, payModeChart, topItemsChart;

    var chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        legend: { position: 'bottom', labels: { boxWidth: 12, fontSize: 11 } }
    };

    // ── Sales Trend ──────────────────────────────────────────────────────────
    function loadSalesTrend(days) {
        $.getJSON(_api + '?action=daily_sales_trend&days=' + days, function(d) {
            if (salesTrendChart) salesTrendChart.destroy();
            var ctx = $('#salesTrendChart')[0].getContext('2d');
            salesTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: d.labels,
                    datasets: [{
                        label: 'Sales',
                        data: d.data,
                        backgroundColor: 'rgba(40,167,69,.15)',
                        borderColor: '#28a745',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#28a745',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: $.extend(true, {}, chartDefaults, {
                    legend: { display: false },
                    scales: {
                        xAxes: [{ gridLines: { display: false }, ticks: { fontSize: 10 } }],
                        yAxes: [{ gridLines: { color: 'rgba(0,0,0,.05)' }, ticks: { beginAtZero: true, fontSize: 10 } }]
                    },
                    tooltips: { callbacks: { label: function(t) { return ' ' + parseFloat(t.yLabel).toLocaleString('en-US',{minimumFractionDigits:2}); } } }
                })
            });
        });
    }

    // ── Monthly Sales vs Purchase ────────────────────────────────────────────
    function loadMonthly() {
        $.getJSON(_api + '?action=monthly_sales_purchase', function(d) {
            if (monthlyChart) monthlyChart.destroy();
            var ctx = $('#monthlyChart')[0].getContext('2d');
            monthlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: d.labels,
                    datasets: [
                        { label: 'Sales',    data: d.sales,     backgroundColor: 'rgba(40,167,69,.7)',  borderColor: '#28a745', borderWidth: 1 },
                        { label: 'Purchase', data: d.purchases, backgroundColor: 'rgba(0,123,255,.7)', borderColor: '#007bff', borderWidth: 1 }
                    ]
                },
                options: $.extend(true, {}, chartDefaults, {
                    scales: {
                        xAxes: [{ gridLines: { display: false }, ticks: { fontSize: 9 } }],
                        yAxes: [{ gridLines: { color: 'rgba(0,0,0,.05)' }, ticks: { beginAtZero: true, fontSize: 10 } }]
                    }
                })
            });
        });
    }

    // ── Payment Mode Pie ─────────────────────────────────────────────────────
    function loadPayMode() {
        var now = new Date();
        var from = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0,10);
        var to   = now.toISOString().slice(0,10);
        $.getJSON(_api + '?action=payment_mode_dist&from=' + from + '&to=' + to, function(d) {
            if (payModeChart) payModeChart.destroy();
            var ctx = $('#payModeChart')[0].getContext('2d');
            payModeChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: d.labels.length ? d.labels : ['No Data'],
                    datasets: [{ data: d.data.length ? d.data : [1], backgroundColor: ['#28a745','#ffc107','#007bff','#6f42c1','#dc3545'], borderWidth: 2 }]
                },
                options: $.extend(true, {}, chartDefaults, { cutoutPercentage: 60 })
            });
        });
    }

    // ── Top 10 Items ─────────────────────────────────────────────────────────
    function loadTopItems() {
        var now = new Date();
        var from = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0,10);
        var to   = now.toISOString().slice(0,10);
        $.getJSON(_api + '?action=top_items&from=' + from + '&to=' + to, function(d) {
            if (topItemsChart) topItemsChart.destroy();
            var ctx = $('#topItemsChart')[0].getContext('2d');
            topItemsChart = new Chart(ctx, {
                type: 'horizontalBar',
                data: {
                    labels: d.labels.length ? d.labels : ['No Data'],
                    datasets: [{ label: 'Qty Sold', data: d.data.length ? d.data : [0], backgroundColor: 'rgba(220,53,69,.7)', borderColor: '#dc3545', borderWidth: 1 }]
                },
                options: $.extend(true, {}, chartDefaults, {
                    legend: { display: false },
                    scales: {
                        xAxes: [{ ticks: { beginAtZero: true, fontSize: 9 }, gridLines: { color: 'rgba(0,0,0,.05)' } }],
                        yAxes: [{ ticks: { fontSize: 9 }, gridLines: { display: false } }]
                    }
                })
            });
        });
    }

    // ── Init ─────────────────────────────────────────────────────────────────
    $(function() {
        loadKPIs();
        loadSalesTrend(7);
        loadMonthly();
        loadPayMode();
        loadTopItems();

        function loadKPIs() {
            $.getJSON(_api + '?action=dashboard_kpis', function(d) {
                // Today
                $('#kpi_td_pos_sales').text(parseFloat(d.today_pos_sales || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#kpi_td_sales').text(parseFloat(d.today_sales).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#kpi_td_purchase').text(parseFloat(d.today_purchase).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#kpi_td_expense').text(parseFloat(d.today_expense).toLocaleString('en-US', {minimumFractionDigits: 2}));
                
                updatePctBadge('kpi_td_sales_pct', d.sales_pct);
                updatePctBadge('kpi_td_purchase_pct', d.purchase_pct);
                updatePctBadge('kpi_td_expense_pct', d.expense_pct);
                updatePctBadge('kpi_td_profit_pct', d.profit_pct);

                // Monthly
                $('#kpi_month_sales').text(parseFloat(d.month_sales).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#kpi_month_purchase').text(parseFloat(d.month_purchase).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#kpi_month_expense').text(parseFloat(d.month_expense).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#kpi_month_profit').text(parseFloat(d.month_profit).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#kpi_month_discount').text(parseFloat(d.month_discount).toLocaleString('en-US', {minimumFractionDigits: 2}));

                // Ops
                $('#kpi_receivable').text(parseFloat(d.receivable).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#kpi_payable').text(parseFloat(d.payable).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#kpi_low_stock').text(d.low_stock_count);

                // Profit
                $('#kpi_td_profit').text(parseFloat(d.today_profit).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#kpi_td_discount').text(parseFloat(d.today_discount).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#kpi_total_profit').text(parseFloat(d.total_profit).toLocaleString('en-US', {minimumFractionDigits: 2}));

                // Glance
                $('#glance_td_sales').text(parseFloat(d.today_sales).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#glance_td_purchase').text(parseFloat(d.today_purchase).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#glance_td_expense').text(parseFloat(d.today_expense).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#glance_month_sales').text(parseFloat(d.month_sales).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#glance_month_purchase').text(parseFloat(d.month_purchase).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#glance_month_expense').text(parseFloat(d.month_expense).toLocaleString('en-US', {minimumFractionDigits: 2}));

                // Accounts
                var acc_html = '';
                d.accounts.forEach(function(a) {
                    acc_html += '<div class="col-12 col-sm-6 col-md-3 mb-3">' +
                                '<a href="' + _base_url_ + 'admin/?page=reports/account_ledger&id=' + a.id + '" class="card kpi-card kpi-green">' +
                                '<div class="card-body">' +
                                '<i class="fas fa-university kpi-icon"></i>' +
                                '<div class="kpi-label">' + a.name + '</div>' +
                                '<div class="kpi-value">Rs. ' + parseFloat(a.balance).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</div>' +
                                '</div></a></div>';
                });
                $('#accounts_container').html(acc_html);
                $('#lastRefreshed').text('Updated: ' + d.updated_at);
            });
        }

        function updatePctBadge(id, pct) {
            var el = $('#' + id);
            var wrap = $('#' + id + '_wrap');
            var icon = $('#' + id + '_icon');
            el.text(pct.val + '%');
            wrap.removeClass('badge-up badge-down').addClass(pct.up ? 'badge-up' : 'badge-down');
            icon.removeClass('fa-arrow-up fa-arrow-down').addClass(pct.up ? 'fa-arrow-up' : 'fa-arrow-down');
        }

        // Trend day toggle
        $('.trend-btn').click(function() {
            $('.trend-btn').removeClass('active');
            $(this).addClass('active');
            loadSalesTrend($(this).data('days'));
        });

        // ── Auto-refresh & System Clock ─────────────────────────────────────────
        var refreshTimer;
        var countdown = 30;
        var countdownInterval = null;

        function triggerRefresh() {
            loadKPIs();
            loadSalesTrend($('.trend-btn.active').data('days') || 7);
            loadMonthly();
            loadPayMode();
            loadTopItems();
            countdown = 30;
        }

        function startRefresh() {
            localStorage.setItem('autoRefreshDashboard', 'true');
            $('#refreshCountdown').show().text(countdown + 's');
            
            if(countdownInterval) clearInterval(countdownInterval);
            countdownInterval = setInterval(function(){
                countdown--;
                $('#refreshCountdown').text(countdown + 's');
                if(countdown <= 0) {
                    triggerRefresh();
                }
            }, 1000);
        }

        function stopRefresh() {
            localStorage.setItem('autoRefreshDashboard', 'false');
            if(countdownInterval) clearInterval(countdownInterval);
            countdownInterval = null;
            $('#refreshCountdown').hide();
            countdown = 30;
        }

        // Initialize Auto-Refresh state
        var autoRefreshActive = localStorage.getItem('autoRefreshDashboard');
        // Default to ON if never set
        if(autoRefreshActive === 'true' || autoRefreshActive === null) {
            $('#autoRefreshToggle').prop('checked', true);
            startRefresh();
        }

        $('#autoRefreshToggle').change(function() {
            if ($(this).is(':checked')) {
                startRefresh();
            } else {
                stopRefresh();
            }
        });

        // Real-time System Clock (Sync with Server)
        var serverTime = <?php echo time() * 1000 ?>;
        function updateClock() {
            serverTime += 1000;
            var now = new Date(serverTime);
            var options = { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            };
            $('#systemClock').text(now.toLocaleString('en-US', options));
        }
        setInterval(updateClock, 1000);
        updateClock();
    });
})();
</script>