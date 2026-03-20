<?php
require_once('../../config.php');
if(!isset($_SESSION['userdata'])){
    header('Location: ../../login.php');
    exit;
}
$type = isset($_GET['type']) ? $_GET['type'] : 'daily_sales';
$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d');
$to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$method = isset($_GET['method']) ? $_GET['method'] : '';


// page title mapping
$titles = [
    'daily_sales' => 'Sales (Daily)',
    'total_sales' => 'Total Sales',
    'cash_sales' => 'Cash Sales',
    'qr_sales' => 'QR Sales',
    'bank_sales' => 'Bank Sales',
    'cash_balance' => 'Cash Balance (Payments)',
    'qr_balance' => 'QR Balance (Payments)',
    'bank_balance' => 'Bank Balance (Payments)',
    'profit_today' => 'Profit / Loss (Filtered)',
    'purchase_today' => 'Purchases',
    'vat_purchase' => 'VAT Purchases',
    'import' => 'Import — Types'
];
$title = isset($titles[$type]) ? $titles[$type] : 'Details';
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h5 class="card-title"><?php echo $title ?></h5>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/?page=home" class="btn btn-sm btn-default">Back to Dashboard</a>
        </div>
    </div>
    <div class="card-body">
        <form id="filter-form" method="get" action="">
            <input type="hidden" name="page" value="dashboard_details">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type) ?>">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label>Date From</label>
                    <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($from) ?>">
                </div>
                <div class="col-md-3">
                    <label>Date To</label>
                    <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($to) ?>">
                </div>
                <?php if(in_array($type, ['daily_sales','total_sales','profit_today','cash_sales','qr_sales','bank_sales'])): ?>
                <div class="col-md-3">
                    <label>Customer</label>
                    <select name="customer_id" class="form-control">
                        <option value="0">-- All Customers --</option>
                        <?php $c_q = $conn->query("SELECT id, display_name as name FROM entity_list WHERE entity_type = 'Customer' AND status = 1 ORDER BY display_name"); while($c = $c_q->fetch_assoc()): ?>
                        <option value="<?php echo $c['id'] ?>" <?php echo $customer_id == $c['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if(in_array($type, ['daily_sales','total_sales','profit_today'])): ?>
                <div class="col-md-3">
                    <label>Item</label>
                    <select name="item_id" class="form-control">
                        <option value="0">-- All Items --</option>
                        <?php $it_q = $conn->query("SELECT id, name FROM item_list WHERE status = 1 ORDER BY name"); while($it = $it_q->fetch_assoc()): ?>
                        <option value="<?php echo $it['id'] ?>" <?php echo $item_id == $it['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($it['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if(in_array($type, ['purchase_today','vat_purchase'])): ?>
                <div class="col-md-3">
                    <label>Vendor</label>
                    <select name="vendor_id" class="form-control">
                        <option value="0">-- All Vendors --</option>
                        <?php $s_q = $conn->query("SELECT id, display_name as name FROM entity_list WHERE entity_type = 'Supplier' AND status = 1 ORDER BY display_name"); while($s = $s_q->fetch_assoc()): ?>
                        <option value="<?php echo $s['id'] ?>" <?php echo $vendor_id == $s['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($s['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if(in_array($type, ['cash_sales','qr_sales','bank_sales','cash_balance','qr_balance','bank_balance'])): ?>
                <div class="col-md-3">
                    <label>Payment Method</label>
                    <select name="method" class="form-control">
                        <option value="">-- All --</option>
                        <option value="Cash" <?php echo $method === 'Cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="QR" <?php echo $method === 'QR' ? 'selected' : '' ?>>QR</option>
                        <option value="Bank" <?php echo $method === 'Bank' ? 'selected' : '' ?>>Bank</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md-3 align-self-end">
                    <button class="btn btn-primary">Filter</button>
                    <a href="<?php echo base_url ?>admin/?page=dashboard_details&type=<?php echo urlencode($type) ?>" class="btn btn-light">Reset</a>
                </div>
            </div>
        </form>

        <?php
        // Build WHERE clauses safely
        $from_esc = $conn->real_escape_string($from);
        $to_esc = $conn->real_escape_string($to);
        $where_date = "date(transaction_date) BETWEEN '{$from_esc}' AND '{$to_esc}'";

        switch($type){
            case 'daily_sales':
            case 'total_sales':
                // sales list with optional item/customer filter
                $filters = [];
                $filters[] = $where_date;
                $filters[] = "t.type = 'sale'";
                if($customer_id) $filters[] = "t.entity_id = '". intval($customer_id) ."'";
                if($item_id) $filters[] = "EXISTS (SELECT 1 FROM transaction_items ti WHERE ti.transaction_id = t.id AND ti.item_id = '". intval($item_id) ."')";
                $where_sql = implode(' AND ', $filters);
                $qry = $conn->query("SELECT t.*, c.display_name as customer, t.reference_code as sales_code FROM transactions t LEFT JOIN entity_list c ON t.entity_id = c.id AND c.entity_type = 'Customer' WHERE {$where_sql} ORDER BY t.transaction_date DESC");
                $total_amount = 0; $total_profit = 0; $rows = [];
                while($r = $qry->fetch_assoc()){
                    $stocks = $conn->query("SELECT SUM(total_price - (quantity * (SELECT cost FROM item_list WHERE id = ti.item_id))) as profit FROM transaction_items ti WHERE ti.transaction_id = '{$r['id']}'")->fetch_assoc();
                    $profit = $stocks['profit'] ?? 0;
                    $total_amount += $r['total_amount'];
                    $total_profit += $profit;
                    $rows[] = ['row'=>$r,'profit'=>$profit];
                }
                ?>
                <div class="mb-3">
                    <strong>Total sales:</strong> <?php echo number_format($total_amount,2) ?>
                    &nbsp; &nbsp;
                    <strong>Total profit:</strong> <?php echo number_format($total_profit,2) ?>
                </div>
                <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Sales Code</th>
                            <th>Customer</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Profit</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rows as $r): $s = $r['row']; ?>
                        <tr>
                             <td><?php echo date('Y-m-d H:i', strtotime($s['transaction_date'])) ?></td>
                            <td><?php echo htmlspecialchars($s['sales_code']) ?></td>
                            <td><?php echo htmlspecialchars($s['customer'] ?: 'Walk-in') ?></td>
                            <td class="text-right"><?php echo number_format($s['total_amount'],2) ?></td>
                            <td class="text-right"><?php echo number_format($r['profit'],2) ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="<?php echo base_url ?>admin/?page=sales/view_sale&id=<?php echo $s['id'] ?>" target="_blank">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php
                break;

            case 'cash_sales':
            case 'qr_sales':
            case 'bank_sales':
                $pm = ($type === 'cash_sales') ? 'Cash' : (($type === 'qr_sales') ? 'QR' : 'Bank');
                $filters = ["date(p.transaction_date) BETWEEN '{$from_esc}' AND '{$to_esc}'", "p.type = 'payment'", "a.name LIKE '%{$pm}%'"];
                if($customer_id) $filters[] = "s.entity_id = '". intval($customer_id) ."'";
                $where_sql = implode(' AND ', $filters);
                $qry = $conn->query("SELECT p.*, s.reference_code as sales_code, c.display_name as customer, p.total_amount as amount, p.reference_code as payment_code, p.transaction_date as date_created FROM transactions p JOIN account_list a ON p.account_id = a.id LEFT JOIN transactions s ON p.parent_id = s.id AND s.type = 'sale' LEFT JOIN entity_list c ON s.entity_id = c.id AND c.entity_type = 'Customer' WHERE {$where_sql} ORDER BY p.transaction_date DESC");
                $total = 0; $rows = [];
                while($r = $qry->fetch_assoc()){ $total += $r['amount']; $rows[] = $r; }
                ?>
                <div class="mb-3"><strong>Payment method:</strong> <?php echo $pm ?> &nbsp; <strong>Total:</strong> <?php echo number_format($total,2) ?></div>
                <div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Date</th><th>Payment Code</th><th>Sale</th><th>Customer</th><th class="text-right">Amount</th><th>Action</th></tr></thead><tbody>
                <?php foreach($rows as $r): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($r['date_created'])) ?></td>
                        <td><?php echo htmlspecialchars($r['payment_code']) ?></td>
                        <td><?php echo htmlspecialchars($r['sales_code'] ?? '') ?></td>
                        <td><?php echo htmlspecialchars($r['customer'] ?? '') ?></td>
                        <td class="text-right"><?php echo number_format($r['amount'],2) ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="<?php echo base_url ?>admin/?page=payments/view_payment&id=<?php echo $r['id'] ?>" target="_blank">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>
                <?php
                break;

            case 'cash_balance':
            case 'qr_balance':
            case 'bank_balance':
                // show payments (or accounts) filtered by method
                $pm_filter = ($type === 'cash_balance') ? 'Cash' : (($type === 'qr_balance') ? 'QR' : 'Bank');
                $filters = ["date(p.transaction_date) BETWEEN '{$from_esc}' AND '{$to_esc}'", "a.name LIKE '%{$pm_filter}%'", "p.type = 'payment'"];
                if($method) $filters[] = "a.name LIKE '%".$conn->real_escape_string($method)."%'";
                $where_sql = implode(' AND ', $filters);
                $qry = $conn->query("SELECT p.*, s.reference_code as sales_code, po.reference_code as po_code, p.total_amount as amount, p.reference_code as payment_code, p.transaction_date as date_created FROM transactions p JOIN account_list a ON p.account_id = a.id LEFT JOIN transactions s ON p.parent_id = s.id AND s.type = 'sale' LEFT JOIN transactions po ON p.parent_id = po.id AND po.type = 'purchase' WHERE {$where_sql} ORDER BY p.transaction_date DESC");
                $total = 0; $rows = [];
                while($r = $qry->fetch_assoc()){ $total += $r['amount']; $rows[] = $r; }
                ?>
                <div class="mb-3"><strong>Method:</strong> <?php echo $pm_filter ?> &nbsp; <strong>Total:</strong> <?php echo number_format($total,2) ?></div>
                <div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Date</th><th>Payment Code</th><th>Reference</th><th class="text-right">Amount</th><th>Action</th></tr></thead><tbody>
                <?php foreach($rows as $r): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($r['date_created'])) ?></td>
                        <td><?php echo htmlspecialchars($r['payment_code']) ?></td>
                        <td><?php echo htmlspecialchars($r['sales_code'] ?? $r['po_code'] ?? '') ?></td>
                        <td class="text-right"><?php echo number_format($r['amount'],2) ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="<?php echo base_url ?>admin/payments/view_payment.php?id=<?php echo $r['id'] ?>" target="_blank">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>
                <?php
                break;

            case 'profit_today':
                // reuse profit report logic but with filters
                $f = $from_esc; $t = $to_esc;
                include 'reports/profit.php';
                break;

            case 'purchase_today':
            case 'vat_purchase':
                $filters = ["date(t.transaction_date) BETWEEN '{$from_esc}' AND '{$to_esc}'", "t.type = 'purchase'"];
                if($vendor_id) $filters[] = "t.entity_id = '".intval($vendor_id)."'";
                if($type === 'vat_purchase') $filters[] = "0 = 1"; // currently no tax tracking
                $where_sql = implode(' AND ', $filters);
                $qry = $conn->query("SELECT t.*, s.display_name as vendor, t.total_amount as amount, t.reference_code as po_code, t.transaction_date as date_created FROM transactions t LEFT JOIN entity_list s ON t.entity_id = s.id AND s.entity_type = 'Supplier' WHERE {$where_sql} ORDER BY t.transaction_date DESC");
                $total = 0; $total_vat = 0; $rows = [];
                while($r = $qry->fetch_assoc()){ $total += $r['amount']; $total_vat += 0; $rows[] = $r; }
                ?>
                <div class="mb-3"><strong>Total Purchases:</strong> <?php echo number_format($total,2) ?> &nbsp; <strong>Total VAT:</strong> <?php echo number_format($total_vat,2) ?></div>
                <div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Date</th><th>Bill Code</th><th>Vendor</th><th class="text-right">Amount</th><th class="text-right">VAT</th><th>Action</th></tr></thead><tbody>
                <?php foreach($rows as $r): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($r['date_created'])) ?></td>
                        <td><?php echo htmlspecialchars($r['po_code']) ?></td>
                        <td><?php echo htmlspecialchars($r['vendor']) ?></td>
                         <td class="text-right"><?php echo number_format($r['amount'],2) ?></td>
                        <td class="text-right"><?php echo number_format(0,2) ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="<?php echo base_url ?>admin/?page=purchases/view_purchase&id=<?php echo $r['id'] ?>" target="_blank">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>
                <?php
                break;

            case 'import':
                ?>
                <div class="row">
                    <div class="col-md-3 mb-2"><a href="<?php echo base_url ?>admin/?page=import&type=items" class="btn btn-block btn-outline-primary">Import Items</a></div>
                    <div class="col-md-3 mb-2"><a href="<?php echo base_url ?>admin/?page=import&type=suppliers" class="btn btn-block btn-outline-primary">Import Suppliers</a></div>
                    <div class="col-md-3 mb-2"><a href="<?php echo base_url ?>admin/?page=import&type=customers" class="btn btn-block btn-outline-primary">Import Customers</a></div>
                    <div class="col-md-3 mb-2"><a href="<?php echo base_url ?>admin/?page=import&type=categories" class="btn btn-block btn-outline-primary">Import Categories</a></div>
                    <div class="col-md-3 mb-2"><a href="<?php echo base_url ?>admin/?page=import&type=accounts" class="btn btn-block btn-outline-primary">Import Accounts</a></div>
                </div>
                <?php
                break;

            default:
                echo '<div class="text-muted">No details available for this type.</div>';
                break;
        }
        ?>
    </div>
</div>

<script>
    // submit filter form via GET
    $('#filter-form').on('submit', function(e){
        // default behavior is fine (GET form)
    });
</script>