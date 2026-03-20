<?php
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d");
$to   = isset($_GET['to'])   ? $_GET['to']   : date("Y-m-d");
$customer_id  = isset($_GET['customer_id'])  ? intval($_GET['customer_id'])  : 0;
$payment_mode = isset($_GET['payment_mode']) ? $_GET['payment_mode'] : '';

// Build WHERE (sales_list has no payment_method column; filter is applied in PHP)
$where = "DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'";
if ($customer_id) $where .= " AND t.entity_id = {$customer_id}";

// Note: Current schema focuses on core transaction data.
// Payment method comes from related payment transactions.
$qry = $conn->query("
    SELECT t.*,
           COALESCE(c.display_name, 'Walk-in') as customer_name,
           t.discount as disc,
           COALESCE((SELECT SUM(total_amount) FROM transactions WHERE parent_id = t.id AND type = 'payment'), 0) as paid_amount,
           0 as vat_amt,
           COALESCE((SELECT COUNT(*) FROM transaction_items WHERE transaction_id = t.id), 0) as item_count,
           COALESCE((SELECT a.name FROM transactions p JOIN account_list a ON p.account_id = a.id WHERE p.parent_id = t.id AND p.type = 'payment' ORDER BY p.id DESC LIMIT 1), 'Credit') as payment_method
    FROM transactions t
    LEFT JOIN entity_list c ON t.entity_id = c.id AND c.entity_type = 'Customer'
    WHERE t.type = 'sale' AND {$where}
    ORDER BY t.transaction_date DESC, t.id DESC
");

// Totals
$t_gross=$t_disc=$t_vat=$t_net=$t_received=$t_balance=$t_cash=$t_qr=$t_bank=$t_credit=0;
$rows = [];
while ($r = $qry->fetch_assoc()) {
    // Filter by payment mode in PHP (since sales_list has no payment_method column)
    if ($payment_mode && $r['payment_method'] !== $payment_mode) continue;
    $r['gross_amt'] = $r['total_amount'] + $r['disc'];
    $t_gross    += $r['gross_amt'];
    $t_disc     += $r['disc'];
    $t_net      += $r['total_amount'];
    $t_received += $r['paid_amount'];
    $t_balance  += max(0, $r['total_amount'] - $r['paid_amount']);
    $pm = $r['payment_method'] ?? 'Credit';
    if ($pm === 'Cash')      $t_cash   += $r['paid_amount'];
    elseif ($pm === 'QR')    $t_qr     += $r['paid_amount'];
    elseif ($pm === 'Bank')  $t_bank   += $r['paid_amount'];
    else                     $t_credit += max(0, $r['total_amount'] - $r['paid_amount']);
    $rows[] = $r;
}

// Customers & payment modes for filters
$customers = $conn->query("SELECT id, display_name as name FROM entity_list WHERE entity_type = 'Customer' AND status=1 ORDER BY display_name");
?>
<div class="card card-outline card-success shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i>Daily Sales Report</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/reports/report_api.php?action=export_excel&report=daily_sales&from=<?php echo $from ?>&to=<?php echo $to ?>" class="btn btn-sm btn-success">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <button class="btn btn-sm btn-info print-btn" type="button"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" id="filter-form">
            <input type="hidden" name="page" value="reports/sales">
            <div class="row align-items-end">
                <div class="form-group col-md-2">
                    <label class="control-label small font-weight-bold">Date From</label>
                    <input type="date" name="from" value="<?php echo $from ?>" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-2">
                    <label class="control-label small font-weight-bold">Date To</label>
                    <input type="date" name="to" value="<?php echo $to ?>" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-3">
                    <label class="control-label small font-weight-bold">Customer</label>
                    <select name="customer_id" class="form-control form-control-sm select2">
                        <option value="">-- All Customers --</option>
                        <?php while ($c = $customers->fetch_assoc()): ?>
                            <option value="<?php echo $c['id'] ?>" <?php echo $customer_id==$c['id']?'selected':'' ?>><?php echo htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label class="control-label small font-weight-bold">Payment Mode</label>
                    <select name="payment_mode" class="form-control form-control-sm">
                        <option value="">-- All Modes --</option>
                        <?php foreach(['Cash','QR','Bank','Credit'] as $pm): ?>
                            <option value="<?php echo $pm ?>" <?php echo $payment_mode===$pm?'selected':'' ?>><?php echo $pm ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="<?php echo base_url ?>admin/?page=reports/sales" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        <hr class="mt-0">

        <!-- Table -->
        <div id="print-data">
            <div class="text-center mb-2 d-none print-header">
                <h5>Daily Sales Report</h5>
                <p class="mb-0"><?php echo date('d-m-Y',strtotime($from)) ?> to <?php echo date('d-m-Y',strtotime($to)) ?></p>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover table-sm" id="salesTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Bill No</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th class="text-center">Items</th>
                            <th class="text-right">Gross Amt</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">VAT</th>
                            <th class="text-right">Net Amt</th>
                            <th class="text-center">Mode</th>
                            <th class="text-right">Received</th>
                            <th class="text-right">Balance</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="13" class="text-center text-muted py-4">No records found for selected filters.</td></tr>
                    <?php endif; ?>
                    <?php $i=1; foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo $i++ ?></td>
                            <td><a href="<?php echo base_url ?>admin/?page=sales/view_sale&id=<?php echo $row['id'] ?>" target="_blank"><?php echo htmlspecialchars($row['reference_code']) ?></a></td>
                            <td><?php echo date('d-m-Y', strtotime($row['transaction_date'])) ?></td>
                            <td><?php echo htmlspecialchars($row['customer_name']) ?></td>
                            <td class="text-center"><?php echo $row['item_count'] ?></td>
                            <td class="text-right"><?php echo number_format($row['gross_amt'],2) ?></td>
                            <td class="text-right"><?php echo number_format($row['disc'],2) ?></td>
                            <td class="text-right"><?php echo number_format($row['vat_amt'],2) ?></td>
                            <td class="text-right font-weight-bold"><?php echo number_format($row['total_amount'],2) ?></td>
                            <td class="text-center">
                                <?php $pm=$row['payment_method']??'Credit';
                                $badgeClass=['Cash'=>'badge-success','QR'=>'badge-warning','Bank'=>'badge-primary','Credit'=>'badge-secondary'];
                                echo '<span class="badge '.($badgeClass[$pm]??'badge-secondary').'">'.$pm.'</span>'; ?>
                            </td>
                            <td class="text-right"><?php echo number_format($row['paid_amount'],2) ?></td>
                            <td class="text-right text-danger"><?php echo number_format(max(0,$row['total_amount']-$row['paid_amount']),2) ?></td>
                            <td class="text-center">
                                <?php 
                                $balance = max(0, $row['total_amount'] - $row['paid_amount']);
                                if($balance <= 0): ?><span class="badge badge-success">Paid</span>
                                <?php elseif($balance < $row['total_amount']): ?><span class="badge badge-warning">Partial</span>
                                <?php else: ?><span class="badge badge-danger">Unpaid</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark font-weight-bold">
                            <th colspan="5" class="text-right">TOTALS</th>
                            <th class="text-right"><?php echo number_format($t_gross,2) ?></th>
                            <th class="text-right"><?php echo number_format($t_disc,2) ?></th>
                            <th class="text-right"><?php echo number_format($t_vat,2) ?></th>
                            <th class="text-right"><?php echo number_format($t_net,2) ?></th>
                            <th></th>
                            <th class="text-right"><?php echo number_format($t_received,2) ?></th>
                            <th class="text-right"><?php echo number_format($t_balance,2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Payment Mode Summary -->
            <div class="row mt-3">
                <div class="col-md-6 offset-md-6">
                    <table class="table table-bordered table-sm">
                        <tr><th colspan="2" class="bg-dark text-white text-center">Payment Breakdown</th></tr>
                        <tr><td><i class="fas fa-money-bill-wave text-success"></i> Cash</td><td class="text-right font-weight-bold"><?php echo number_format($t_cash,2) ?></td></tr>
                        <tr><td><i class="fas fa-qrcode text-warning"></i> QR</td><td class="text-right font-weight-bold"><?php echo number_format($t_qr,2) ?></td></tr>
                        <tr><td><i class="fas fa-university text-primary"></i> Bank</td><td class="text-right font-weight-bold"><?php echo number_format($t_bank,2) ?></td></tr>
                        <tr><td><i class="fas fa-credit-card text-danger"></i> Credit (Balance)</td><td class="text-right font-weight-bold text-danger"><?php echo number_format($t_balance,2) ?></td></tr>
                        <tr class="table-dark"><th>Total Sales</th><th class="text-right"><?php echo number_format($t_net,2) ?></th></tr>
                        <tr><th>Total VAT Collected</th><th class="text-right"><?php echo number_format($t_vat,2) ?></th></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(function(){
    // Select2 for customer
    if($.fn.select2) $('.select2').select2({ theme:'bootstrap4', width:'100%' });

    // Print
    $('.print-btn').click(function(){
        start_loader();
        var _h = $('head').clone();
        var _p = $('#print-data').clone();
        _p.find('.no-print').remove();
        _p.find('.print-header').removeClass('d-none');
        var _el = $('<div>');
        _h.find('title').text('Daily Sales Report');
        _el.append(_h).append(_p);
        var nw = window.open('','_blank','width=1100,height=900,top=50,left=100');
        nw.document.write(_el.html());
        nw.document.close();
        setTimeout(function(){ nw.print(); setTimeout(function(){ nw.close(); end_loader(); },200); },500);
    });
});
</script>
