<?php
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d");
$to   = isset($_GET['to'])   ? $_GET['to']   : date("Y-m-d");

// Get accounts for dropdown
$accounts_qry = $conn->query("SELECT id, name FROM account_list ORDER BY name ASC");
$accounts = [];
$default_account = 0;
while($acc = $accounts_qry->fetch_assoc()) {
    $accounts[] = $acc;
    if (strpos(strtolower($acc['name']), 'cash') !== false && $default_account == 0) {
        $default_account = $acc['id'];
    }
}
if($default_account == 0 && count($accounts) > 0) {
    $default_account = $accounts[0]['id'];
}

$account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : $default_account;
$account_name = "";
foreach($accounts as $acc) {
    if($acc['id'] == $account_id) {
        $account_name = $acc['name'];
        break;
    }
}

// Get account opening balance at start of 'from' date
$opening_balance = 0;
if ($account_id) {
    $opening_balance = floatval($conn->query("SELECT 
        SUM(CASE WHEN type = 1 THEN amount ELSE -amount END) 
        FROM transaction_list 
        WHERE account_id = '{$account_id}' 
        AND DATE(date_created) < '{$from}'")->fetch_array()[0] ?? 0);
}

// Build movements from transactions (movements tagged to Account)
$qry = $conn->query("
    (SELECT
        DATE(t.transaction_date) as txn_date,
        t.transaction_date as date_created,
        CONCAT('Sale Payment – ', COALESCE((SELECT reference_code FROM transactions WHERE id=t.parent_id LIMIT 1), 'N/A')) as narration,
        t.total_amount as in_amount,
        0 as out_amount,
        'sale' as txn_type
     FROM transactions t
     WHERE t.type='payment' AND t.account_id = '{$account_id}'
       AND (SELECT type FROM transactions WHERE id=t.parent_id) = 'sale'
       AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}')
    UNION ALL
    (SELECT
        DATE(t.transaction_date),
        t.transaction_date,
        CONCAT('Purchase Payment – ', COALESCE((SELECT reference_code FROM transactions WHERE id=t.parent_id LIMIT 1), 'N/A')),
        0,
        t.total_amount,
        'purchase'
     FROM transactions t
     WHERE t.type='payment' AND t.account_id = '{$account_id}'
       AND (SELECT type FROM transactions WHERE id=t.parent_id) = 'purchase'
       AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}')
    UNION ALL
    (SELECT
        DATE(t.transaction_date),
        t.transaction_date,
        CONCAT('Expense – ', COALESCE(t.remarks,'–')),
        0,
        t.total_amount,
        'expense'
     FROM transactions t
     WHERE t.type='expense' AND t.account_id = '{$account_id}'
       AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}')
    UNION ALL
    (SELECT
        DATE(t.transaction_date),
        t.transaction_date,
        CONCAT('Fund Transfer (In) – ', COALESCE((SELECT al.name FROM transaction_list tl JOIN account_list al ON tl.account_id = al.id WHERE tl.ref_id = t.id AND tl.account_id != '{$account_id}' LIMIT 1), 'N/A'), ' (', COALESCE(t.remarks,'–'), ')'),
        t.total_amount,
        0,
        'transfer'
     FROM transactions t
     JOIN transaction_list tl ON t.id = tl.ref_id AND tl.ref_table = 'transactions'
     WHERE t.type='transfer' AND tl.account_id = '{$account_id}' AND tl.type = 1
       AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}')
    UNION ALL
    (SELECT
        DATE(t.transaction_date),
        t.transaction_date,
        CONCAT('Fund Transfer (Out) – ', COALESCE((SELECT al.name FROM transaction_list tl JOIN account_list al ON tl.account_id = al.id WHERE tl.ref_id = t.id AND tl.account_id != '{$account_id}' LIMIT 1), 'N/A'), ' (', COALESCE(t.remarks,'–'), ')'),
        0,
        t.total_amount,
        'transfer'
     FROM transactions t
     JOIN transaction_list tl ON t.id = tl.ref_id AND tl.ref_table = 'transactions'
     WHERE t.type='transfer' AND tl.account_id = '{$account_id}' AND tl.type = 2
       AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}')
    UNION ALL
    (SELECT
        DATE(t.transaction_date),
        t.transaction_date,
        'Opening Balance Entry',
        CASE WHEN tl.type = 1 THEN t.total_amount ELSE 0 END,
        CASE WHEN tl.type = 2 THEN t.total_amount ELSE 0 END,
        'opening_balance'
     FROM transactions t
     JOIN transaction_list tl ON t.id = tl.ref_id AND tl.ref_table = 'transactions'
     WHERE t.type='opening_balance' AND tl.account_id = '{$account_id}'
       AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}')
    ORDER BY date_created ASC
");

$t_in = $t_out = 0;
$rows = [];
while ($r = $qry->fetch_assoc()) {
    $t_in  += $r['in_amount'];
    $t_out += $r['out_amount'];
    $rows[] = $r;
}
$closing = $opening_balance + $t_in - $t_out;
?>
<?php // After while loop, inside the table loop, ensure transfer badge exists ?>
<?php $badges = ['sale'=>'badge-success','purchase'=>'badge-primary','expense'=>'badge-danger', 'transfer' => 'badge-info', 'opening_balance' => 'badge-dark']; ?>
<div class="card card-outline card-teal shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-book-open mr-1"></i>Account Book Report</h3>
        <div class="card-tools">
            <button class="btn btn-sm btn-info print-btn"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <div id="print-data">
        <form method="GET">
            <input type="hidden" name="page" value="reports/cash_book">
            <div class="row align-items-end">
                <div class="form-group col-md-2">
                    <label class="small font-weight-bold">Date From</label>
                    <input type="date" name="from" value="<?php echo $from ?>" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-2">
                    <label class="small font-weight-bold">Date To</label>
                    <input type="date" name="to" value="<?php echo $to ?>" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-3">
                    <label class="small font-weight-bold">Account</label>
                    <select name="account_id" class="form-control form-control-sm select2">
                        <?php foreach($accounts as $acc): ?>
                        <option value="<?php echo $acc['id'] ?>" <?php echo $account_id == $acc['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($acc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="<?php echo base_url ?>admin/?page=reports/cash_book" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        <hr class="mt-0">

        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="small-box bg-secondary"><div class="inner"><h4><?php echo number_format($opening_balance,2) ?></h4><p>Opening Balance (<?php echo htmlspecialchars($account_name) ?>)</p></div><div class="icon"><i class="fas fa-coins"></i></div></div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-success"><div class="inner"><h4><?php echo number_format($t_in,2) ?></h4><p>Total IN</p></div><div class="icon"><i class="fas fa-arrow-down"></i></div></div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-danger"><div class="inner"><h4><?php echo number_format($t_out,2) ?></h4><p>Total OUT</p></div><div class="icon"><i class="fas fa-arrow-up"></i></div></div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-primary"><div class="inner"><h4><?php echo number_format($closing,2) ?></h4><p>Closing Balance</p></div><div class="icon"><i class="fas fa-wallet"></i></div></div>
            </div>
        </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th><th>Date</th><th>Narration</th><th>Type</th>
                            <th class="text-right text-success">IN Amount</th>
                            <th class="text-right text-danger">OUT Amount</th>
                            <th class="text-right">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                    <tr class="table-secondary">
                        <td colspan="6" class="font-weight-bold">Opening Balance</td>
                        <td class="text-right font-weight-bold"><?php echo number_format($opening_balance,2) ?></td>
                    </tr>
                    <?php if(empty($rows)): ?><tr><td colspan="7" class="text-center text-muted py-3">No transactions for this period.</td></tr><?php endif; ?>
                    <?php
                    $running = $opening_balance;
                    $i=1;
                    foreach($rows as $r):
                        $running += $r['in_amount'] - $r['out_amount'];
                        $badge=['sale'=>'badge-success','purchase'=>'badge-primary','expense'=>'badge-danger','transfer'=>'badge-info','opening_balance'=>'badge-dark'];
                    ?>
                        <tr>
                            <td><?php echo $i++ ?></td>
                            <td><?php echo date('d-m-Y',strtotime($r['txn_date'])) ?></td>
                            <td><?php echo htmlspecialchars($r['narration']) ?></td>
                            <td><span class="badge <?php echo $badge[$r['txn_type']]??'badge-secondary' ?>"><?php echo ucfirst($r['txn_type']) ?></span></td>
                            <td class="text-right text-success"><?php echo $r['in_amount']>0 ? number_format($r['in_amount'],2) : '–' ?></td>
                            <td class="text-right text-danger"><?php echo $r['out_amount']>0 ? number_format($r['out_amount'],2) : '–' ?></td>
                            <td class="text-right font-weight-bold <?php echo $running>=0?'text-success':'text-danger' ?>"><?php echo number_format($running,2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark font-weight-bold">
                            <th colspan="4" class="text-right">TOTALS</th>
                            <th class="text-right text-success"><?php echo number_format($t_in,2) ?></th>
                            <th class="text-right text-danger"><?php echo number_format($t_out,2) ?></th>
                            <th class="text-right"><?php echo number_format($closing,2) ?></th>
                        </tr>
                        <tr class="<?php echo $closing>=$opening_balance?'table-success':'table-warning' ?>">
                            <th colspan="6" class="text-right">Closing Balance = Opening + IN – OUT</th>
                            <th class="text-right"><?php echo number_format($closing,2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<script>$(function(){
    $('.print-btn').click(function(){
        start_loader();
        var nw=window.open('','_blank','width=1100,height=900');
        nw.document.write('<html>'+$('head').clone()[0].outerHTML+'<body>'+$('#print-data').html()+'</body></html>');
        nw.document.close();
        setTimeout(function(){nw.print();setTimeout(function(){nw.close();end_loader();},200);},500);
    });
});</script>
