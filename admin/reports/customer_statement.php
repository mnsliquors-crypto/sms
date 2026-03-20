<?php
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-01");
$to   = isset($_GET['to'])   ? $_GET['to']   : date("Y-m-d");

$rows = []; $running = 0; $t_debit = $t_credit = 0; $customer_name = ''; $open_balance = 0;

if ($customer_id) {
    $cust_res = $conn->query("SELECT * FROM entity_list WHERE id={$customer_id} AND entity_type = 'Customer' LIMIT 1")->fetch_assoc();
    $customer_name = $cust_res['display_name'] ?? '';

    // Opening balance calculation: (Sales before 'from') - (Payments & Returns before 'from')
    $opening_debit = $conn->query("SELECT SUM(total_amount) FROM transactions WHERE entity_id = '{$customer_id}' AND type = 'sale' AND DATE(transaction_date) < '{$from}'")->fetch_array()[0] ?? 0;
    
    $opening_credit_payments = $conn->query("SELECT SUM(total_amount) FROM transactions WHERE parent_id IN (SELECT id FROM transactions WHERE entity_id = '{$customer_id}' AND type = 'sale') AND type = 'payment' AND DATE(transaction_date) < '{$from}'")->fetch_array()[0] ?? 0;
    
    $opening_credit_returns = $conn->query("SELECT SUM(total_amount) FROM transactions WHERE entity_id = '{$customer_id}' AND type = 'return' AND entity_type = 'Customer' AND DATE(transaction_date) < '{$from}'")->fetch_array()[0] ?? 0;
    
    $open_balance = floatval($opening_debit) - (floatval($opening_credit_payments) + floatval($opening_credit_returns));

    // Fetch transactions in range
    $txns = $conn->query("
        SELECT MIN(id) as id, reference_code, MIN(transaction_date) as transaction_date, txn_type, SUM(debit) as debit, SUM(credit) as credit, GROUP_CONCAT(DISTINCT remarks SEPARATOR '; ') as remarks FROM (
            SELECT id, reference_code, transaction_date, 'Sale' as txn_type, total_amount as debit, 0 as credit, remarks 
            FROM transactions 
            WHERE entity_id = '{$customer_id}' AND type = 'sale' AND DATE(transaction_date) BETWEEN '{$from}' AND '{$to}'
            UNION ALL
            SELECT id, reference_code, transaction_date, 'Payment' as txn_type, 0 as debit, total_amount as credit, remarks 
            FROM transactions 
            WHERE parent_id IN (SELECT id FROM transactions WHERE entity_id = '{$customer_id}' AND type = 'sale') AND type = 'payment' AND DATE(transaction_date) BETWEEN '{$from}' AND '{$to}'
            UNION ALL
            SELECT id, reference_code, transaction_date, 'Return' as txn_type, 0 as debit, total_amount as credit, remarks 
            FROM transactions 
            WHERE entity_id = '{$customer_id}' AND type = 'return' AND entity_type = 'Customer' AND DATE(transaction_date) BETWEEN '{$from}' AND '{$to}'
        ) t 
        GROUP BY reference_code, txn_type
        ORDER BY transaction_date ASC, id ASC
    ");

    $running = $open_balance;
    while ($r = $txns->fetch_assoc()) {
        $t_debit += $r['debit'];
        $t_credit += $r['credit'];
        $running += ($r['debit'] - $r['credit']);
        $r['running_balance'] = $running;
        $rows[] = $r;
    }
}
$customers = $conn->query("SELECT id, display_name as name FROM entity_list WHERE entity_type = 'Customer' AND status = 1 ORDER BY display_name ASC");
?>
<div class="card card-outline card-primary shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice mr-1"></i>Customer Statement</h3>
        <div class="card-tools">
            <button class="btn btn-sm btn-info print-btn"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <div id="print-data">
        <form method="GET" class="no-print">
            <input type="hidden" name="page" value="reports/customer_statement">
            <div class="row align-items-end mb-4">
                <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Customer</label>
                    <select name="customer_id" class="form-control form-control-sm select2" required>
                        <option value="">-- Select Customer --</option>
                        <?php while($c=$customers->fetch_assoc()): ?>
                            <option value="<?php echo $c['id'] ?>" <?php echo $customer_id==$c['id']?'selected':'' ?>><?php echo htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label class="small font-weight-bold">Date From</label>
                    <input type="date" name="from" value="<?php echo $from ?>" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-2">
                    <label class="small font-weight-bold">Date To</label>
                    <input type="date" name="to" value="<?php echo $to ?>" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-4">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i> View Statement</button>
                    <a href="<?php echo base_url ?>admin/?page=reports/customer_statement" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        
        <?php if(!$customer_id): ?>
            <div class="text-center text-muted py-5"><i class="fas fa-user-tie fa-3x mb-3 d-block text-light"></i>Please select a customer to view their statement.</div>
        <?php else: ?>
            <div class="report-header mb-4">
                <div class="row">
                    <div class="col-12 text-center">
                        <h4 class="font-weight-bold mb-0"><?php echo $_settings->info('name') ?></h4>
                        <p class="mb-0 text-muted"><?php echo $_settings->info('address') ?></p>
                        <h5 class="mt-3 font-weight-bold">CUSTOMER STATEMENT</h5>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Customer:</strong> <?php echo htmlspecialchars($customer_name) ?></p>
                        <p class="mb-1"><strong>Address:</strong> <?php echo htmlspecialchars($cust_res['address'] ?? 'N/A') ?></p>
                        <p class="mb-1"><strong>Contact:</strong> <?php echo htmlspecialchars($cust_res['contact'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6 text-md-right">
                        <p class="mb-1"><strong>Period:</strong> <?php echo date('d M Y',strtotime($from)) ?> to <?php echo date('d M Y',strtotime($to)) ?></p>
                        <p class="mb-1"><strong>Date Generated:</strong> <?php echo date('d M Y H:i') ?></p>
                    </div>
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-bordered table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th width="10%">Date</th>
                            <th width="15%">Reference</th>
                            <th width="15%">Type</th>
                            <th>Remarks</th>
                            <th width="12%" class="text-right">Debit (+)</th>
                            <th width="12%" class="text-right">Credit (-)</th>
                            <th width="15%" class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                    <tr class="table-secondary">
                        <td colspan="4" class="font-weight-bold">Opening Balance</td>
                        <td colspan="2"></td>
                        <td class="text-right font-weight-bold"><?php echo number_format($open_balance,2) ?></td>
                    </tr>
                    <?php if(empty($rows)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No transactions recorded in this period.</td></tr>
                    <?php else: ?>
                        <?php foreach($rows as $r): ?>
                            <tr>
                                <td><?php echo date('d-m-Y',strtotime($r['transaction_date'])) ?></td>
                                <td><?php echo htmlspecialchars($r['reference_code']) ?></td>
                                <td>
                                    <?php 
                                        if($r['txn_type'] == 'Sale') echo '<span class="badge badge-primary">Sale</span>';
                                        elseif($r['txn_type'] == 'Payment') echo '<span class="badge badge-success">Payment</span>';
                                        elseif($r['txn_type'] == 'Return') echo '<span class="badge badge-warning">Return</span>';
                                    ?>
                                </td>
                                <td class="small"><?php echo nl2br(htmlspecialchars($r['remarks'])) ?></td>
                                <td class="text-right text-primary"><?php echo $r['debit'] > 0 ? number_format($r['debit'], 2) : '–' ?></td>
                                <td class="text-right text-success"><?php echo $r['credit'] > 0 ? number_format($r['credit'], 2) : '–' ?></td>
                                <td class="text-right font-weight-bold"><?php echo number_format($r['running_balance'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark font-weight-bold">
                            <th colspan="4" class="text-right">PERIOD TOTALS</th>
                            <th class="text-right"><?php echo number_format($t_debit,2) ?></th>
                            <th class="text-right"><?php echo number_format($t_credit,2) ?></th>
                            <th class="text-right"><?php echo number_format($running,2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row mt-4 no-print">
                <div class="col-md-4">
                    <div class="info-box bg-light shadow-sm">
                        <span class="info-box-icon bg-primary"><i class="fas fa-plus-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Debit</span>
                            <span class="info-box-number"><?php echo number_format($t_debit, 2) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-light shadow-sm">
                        <span class="info-box-icon bg-success"><i class="fas fa-minus-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Credit</span>
                            <span class="info-box-number"><?php echo number_format($t_credit, 2) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box bg-light shadow-sm">
                        <span class="info-box-icon bg-dark"><i class="fas fa-wallet"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Closing Balance</span>
                            <span class="info-box-number"><?php echo number_format($running, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>
<style>
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .table-dark { background-color: #343a40 !important; color: #fff !important; }
        .table-secondary { background-color: #e2e3e5 !important; }
        .badge { border: 1px solid #000; color: #000 !important; background: transparent !important; }
    }
</style>
<script>$(function(){
    if($.fn.select2) $('.select2').select2({theme:'bootstrap4',width:'100%'});
    $('.print-btn').click(function(){
        start_loader();
        var nw=window.open('','_blank','width=1100,height=900');
        nw.document.write('<html>'+$('head').clone()[0].outerHTML+'<body class="p-4">'+$('#print-data').html()+'</body></html>');
        nw.document.close();
        setTimeout(function(){nw.print();setTimeout(function(){nw.close();end_loader();},200);},500);
    });
});</script>
