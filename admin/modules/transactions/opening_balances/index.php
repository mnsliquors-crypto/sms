<?php
require_once __DIR__ . '/../../../../config.php';
if(!isset($_SESSION['userdata'])){
    header('Location: ../../login.php');
    exit;
}

// ── Fetch Opening Balance Accounts ──────────────────────────────────────────
$acct_rows = $conn->query("
    SELECT tl.account_id, a.name as account_name,
           SUM(CASE WHEN tl.type = 1 THEN tl.amount ELSE -tl.amount END) as balance,
           MAX(t.transaction_date) as last_date,
           COUNT(t.id) as entry_count
    FROM transactions t
    JOIN transaction_list tl ON tl.ref_id = t.id AND tl.ref_table = 'transactions'
    JOIN account_list a ON a.id = tl.account_id
    WHERE t.type = 'opening_balance'
    GROUP BY tl.account_id, a.name
    ORDER BY a.name ASC
");

// ── Fetch Opening Stock ──────────────────────────────────────────────────────
$stock_rows = $conn->query("
    SELECT il.id as item_id, il.name as item_name, il.unit,
           SUM(ti.quantity) as total_qty,
           AVG(ti.unit_price) as avg_cost,
           SUM(ti.total_price) as total_value,
           MAX(t.transaction_date) as last_date
    FROM transactions t
    JOIN transaction_items ti ON ti.transaction_id = t.id
    JOIN item_list il ON il.id = ti.item_id
    WHERE t.type = 'opening_stock'
    GROUP BY il.id, il.name, il.unit
    ORDER BY il.name ASC
");

// ── Fetch Vendor Opening Balances ────────────────────────────────────────────
$vendor_rows = $conn->query("
    SELECT v.id as vendor_id, v.display_name as vendor_name,
           SUM(t.total_amount) as balance,
           MAX(t.transaction_date) as last_date,
           COUNT(t.id) as entry_count
    FROM transactions t
    JOIN entity_list v ON v.id = t.entity_id AND v.entity_type = 'Supplier'
    WHERE t.type = 'purchase' AND t.remarks = 'Opening Balance'
    GROUP BY v.id, v.display_name
    ORDER BY v.display_name ASC
");

// ── Fetch Customer Opening Balances ──────────────────────────────────────────
$customer_rows = $conn->query("
    SELECT c.id as customer_id, c.display_name as customer_name,
           SUM(t.total_amount) as balance,
           MAX(t.transaction_date) as last_date,
           COUNT(t.id) as entry_count
    FROM transactions t
    JOIN entity_list c ON c.id = t.entity_id AND c.entity_type = 'Customer'
    WHERE t.type = 'sale' AND t.remarks = 'Opening Balance'
    GROUP BY c.id, c.display_name
    ORDER BY c.display_name ASC
");
?>

<div class="container-fluid">

    <!-- Page Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-body py-3 px-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0 text-primary font-weight-bold">
                                <i class="fas fa-balance-scale mr-2"></i>Opening Balance History
                            </h4>
                            <p class="text-muted small mb-0">Review all recorded opening balances across accounts, inventory, vendors and customers.</p>
                        </div>
                        <a href="<?php echo base_url ?>admin/?page=transactions/opening_balances/create" class="btn btn-primary rounded-pill px-4">
                            <i class="fas fa-plus mr-1"></i> Record New Opening Balance
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <?php
    $acct_arr = []; $total_acct = 0;
    if($acct_rows) while($r = $acct_rows->fetch_assoc())   { $total_acct  += $r['balance'];      $acct_arr[]   = $r; }

    $stock_arr = []; $total_stock = 0;
    if($stock_rows) while($r = $stock_rows->fetch_assoc()) { $total_stock  += $r['total_value'];  $stock_arr[]  = $r; }

    $vendor_arr = []; $total_vendor = 0;
    if($vendor_rows) while($r = $vendor_rows->fetch_assoc()){ $total_vendor += $r['balance'];      $vendor_arr[] = $r; }

    $cust_arr = []; $total_cust = 0;
    if($customer_rows) while($r = $customer_rows->fetch_assoc()){ $total_cust += $r['balance'];   $cust_arr[]   = $r; }
    ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="info-box shadow-sm mb-0">
                <span class="info-box-icon bg-primary"><i class="fas fa-university"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Account Balances</span>
                    <span class="info-box-number"><?php echo number_format($total_acct,2) ?></span>
                    <span class="progress-description"><?php echo count($acct_arr) ?> accounts</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box shadow-sm mb-0">
                <span class="info-box-icon bg-info"><i class="fas fa-boxes"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Inventory Value</span>
                    <span class="info-box-number"><?php echo number_format($total_stock,2) ?></span>
                    <span class="progress-description"><?php echo count($stock_arr) ?> items</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box shadow-sm mb-0">
                <span class="info-box-icon bg-danger"><i class="fas fa-truck"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Vendor Payables</span>
                    <span class="info-box-number"><?php echo number_format($total_vendor,2) ?></span>
                    <span class="progress-description"><?php echo count($vendor_arr) ?> vendors</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box shadow-sm mb-0">
                <span class="info-box-icon bg-success"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Customer Receivables</span>
                    <span class="info-box-number"><?php echo number_format($total_cust,2) ?></span>
                    <span class="progress-description"><?php echo count($cust_arr) ?> customers</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="card card-primary card-outline card-tabs shadow">
        <div class="card-header p-0 pt-1 border-bottom-0 bg-light">
            <ul class="nav nav-tabs" id="obTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="pill" href="#tab-accounts" role="tab">
                        <i class="fas fa-wallet mr-1"></i> Cash Accounts
                        <span class="badge badge-primary ml-1"><?php echo count($acct_arr) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#tab-stock" role="tab">
                        <i class="fas fa-boxes mr-1"></i> Inventory Stock
                        <span class="badge badge-info ml-1"><?php echo count($stock_arr) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#tab-vendors" role="tab">
                        <i class="fas fa-truck mr-1"></i> Vendor Payables
                        <span class="badge badge-danger ml-1"><?php echo count($vendor_arr) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#tab-customers" role="tab">
                        <i class="fas fa-users mr-1"></i> Customer Receivables
                        <span class="badge badge-success ml-1"><?php echo count($cust_arr) ?></span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content">

                <!-- Cash Accounts -->
                <div class="tab-pane fade show active" id="tab-accounts" role="tabpanel">
                    <?php if(empty($acct_arr)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>No account opening balances recorded yet.
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-sm">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>#</th>
                                    <th>Account Name</th>
                                    <th class="text-right">Opening Balance</th>
                                    <th class="text-center">Entries</th>
                                    <th class="text-center">Last Recorded</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($acct_arr as $i => $row): ?>
                                <tr>
                                    <td class="text-center"><?php echo $i+1 ?></td>
                                    <td><i class="fas fa-university text-primary mr-1"></i> <?php echo htmlspecialchars($row['account_name']) ?></td>
                                    <td class="text-right font-weight-bold <?php echo $row['balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?php echo number_format($row['balance'], 2) ?>
                                    </td>
                                    <td class="text-center"><span class="badge badge-secondary"><?php echo $row['entry_count'] ?></span></td>
                                    <td class="text-center text-muted small"><?php echo date('M d, Y', strtotime($row['last_date'])) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary view-account" data-id="<?php echo $row['account_id'] ?>" title="View / Edit entries"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="2" class="text-right">Total</td>
                                    <td class="text-right text-primary"><?php echo number_format($total_acct, 2) ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Inventory Stock -->
                <div class="tab-pane fade" id="tab-stock" role="tabpanel">
                    <?php if(empty($stock_arr)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>No inventory opening stock recorded yet.
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-sm" id="stock-tbl">
                            <thead class="bg-info text-white">
                                <tr>
                                    <th>#</th>
                                    <th>Item Name</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-right">Opening Qty</th>
                                    <th class="text-right">Avg Cost</th>
                                    <th class="text-right">Total Value</th>
                                    <th class="text-center">Last Recorded</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($stock_arr as $i => $row): ?>
                                <tr>
                                    <td class="text-center"><?php echo $i+1 ?></td>
                                    <td><i class="fas fa-box text-info mr-1"></i> <?php echo htmlspecialchars($row['item_name']) ?></td>
                                    <td class="text-center"><span class="badge badge-light border"><?php echo htmlspecialchars($row['unit'] ?: '—') ?></span></td>
                                    <td class="text-right font-weight-bold"><?php echo number_format($row['total_qty'], 2) ?></td>
                                    <td class="text-right"><?php echo number_format($row['avg_cost'], 2) ?></td>
                                    <td class="text-right font-weight-bold text-info"><?php echo number_format($row['total_value'], 2) ?></td>
                                    <td class="text-center text-muted small"><?php echo date('M d, Y', strtotime($row['last_date'])) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary view-stock" data-id="<?php echo $row['item_id'] ?>" title="View / Edit entries"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="5" class="text-right">Total Inventory Value</td>
                                    <td class="text-right text-info"><?php echo number_format($total_stock, 2) ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Vendor Payables -->
                <div class="tab-pane fade" id="tab-vendors" role="tabpanel">
                    <?php if(empty($vendor_arr)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>No vendor opening balances recorded yet.
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-sm">
                            <thead class="bg-danger text-white">
                                <tr>
                                    <th>#</th>
                                    <th>Vendor Name</th>
                                    <th class="text-right">Opening Payable</th>
                                    <th class="text-center">Entries</th>
                                    <th class="text-center">Last Recorded</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($vendor_arr as $i => $row): ?>
                                <tr>
                                    <td class="text-center"><?php echo $i+1 ?></td>
                                    <td><i class="fas fa-truck text-danger mr-1"></i> <?php echo htmlspecialchars($row['vendor_name']) ?></td>
                                    <td class="text-right font-weight-bold text-danger"><?php echo number_format($row['balance'], 2) ?></td>
                                    <td class="text-center"><span class="badge badge-secondary"><?php echo $row['entry_count'] ?></span></td>
                                    <td class="text-center text-muted small"><?php echo date('M d, Y', strtotime($row['last_date'])) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary view-vendor" data-id="<?php echo $row['vendor_id'] ?>" title="View / Edit entries"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="2" class="text-right">Total Payables</td>
                                    <td class="text-right text-danger"><?php echo number_format($total_vendor, 2) ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Customer Receivables -->
                <div class="tab-pane fade" id="tab-customers" role="tabpanel">
                    <?php if(empty($cust_arr)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>No customer opening balances recorded yet.
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-sm">
                            <thead class="bg-success text-white">
                                <tr>
                                    <th>#</th>
                                    <th>Customer Name</th>
                                    <th class="text-right">Opening Receivable</th>
                                    <th class="text-center">Entries</th>
                                    <th class="text-center">Last Recorded</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($cust_arr as $i => $row): ?>
                                <tr>
                                    <td class="text-center"><?php echo $i+1 ?></td>
                                    <td><i class="fas fa-user-circle text-success mr-1"></i> <?php echo htmlspecialchars($row['customer_name']) ?></td>
                                    <td class="text-right font-weight-bold text-success"><?php echo number_format($row['balance'], 2) ?></td>
                                    <td class="text-center"><span class="badge badge-secondary"><?php echo $row['entry_count'] ?></span></td>
                                    <td class="text-center text-muted small"><?php echo date('M d, Y', strtotime($row['last_date'])) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary view-customer" data-id="<?php echo $row['customer_id'] ?>" title="View / Edit entries"><i class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="2" class="text-right">Total Receivables</td>
                                    <td class="text-right text-success"><?php echo number_format($total_cust, 2) ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

</div>

<style>
    .info-box { border-radius: 10px; }
    .nav-tabs .nav-link.active {
        background-color: #fff !important;
        border-bottom-color: transparent !important;
        font-weight: bold;
    }
    .nav-tabs .nav-link { border-top-left-radius: 8px; border-top-right-radius: 8px; padding: 10px 18px; }
    .table th, .table td { vertical-align: middle !important; }
    .card-tabs { border-radius: 12px; overflow: hidden; }
</style>

<script>
    $(function(){
        $('.view-account').click(function(){
            var id = $(this).data('id');
            uni_modal('Account Opening Balance Details','modules/transactions/opening_balances/view.php?category=account&id='+id,'large');
        });
        $('.view-stock').click(function(){
            var id = $(this).data('id');
            uni_modal('Stock Opening Entries','modules/transactions/opening_balances/view.php?category=stock&id='+id,'large');
        });
        $('.view-vendor').click(function(){
            var id = $(this).data('id');
            uni_modal('Vendor Opening Payables','modules/transactions/opening_balances/view.php?category=vendor&id='+id,'large');
        });
        $('.view-customer').click(function(){
            var id = $(this).data('id');
            uni_modal('Customer Opening Receivables','modules/transactions/opening_balances/view.php?category=customer&id='+id,'large');
        });
    });
</script>

<script>
$(function(){
    // DataTables for stock (many items possible)
    $('#stock-tbl').DataTable({
        responsive: true,
        order: [[1,'asc']],
        pageLength: 25,
        columnDefs: [{ orderable: false, targets: 6 }]
    });
});
</script>
