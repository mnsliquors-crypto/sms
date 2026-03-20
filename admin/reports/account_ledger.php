<?php
$account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-01");
$to   = isset($_GET['to'])   ? $_GET['to']   : date("Y-m-d");

$rows = []; $running = 0; $t_debit = $t_credit = 0; $account_name = ''; $open_balance = 0;

if ($account_id) {
    $acc_res = $conn->query("SELECT * FROM account_list WHERE id={$account_id} LIMIT 1")->fetch_assoc();
    $account_name = $acc_res['name'] ?? '';
    // Opening balance: sum of all transaction_list entries for this account before 'from'
    $open_balance = floatval($conn->query("SELECT 
        SUM(CASE WHEN type = 1 THEN amount ELSE -amount END) 
        FROM transaction_list 
        WHERE account_id = '{$account_id}' 
        AND DATE(date_created) < '{$from}'")->fetch_array()[0] ?? 0);

    // Build ledger from transactions tagged to this account
    // We use a UNION approach
    $txns = $conn->query("
        SELECT
            t.transaction_date as date_created, t.total_amount as amount,
            CASE t.type 
                WHEN 'payment' THEN (SELECT CASE type WHEN 'sale' THEN 'Credit' ELSE 'Debit' END FROM transactions WHERE id=t.parent_id)
                WHEN 'transfer' THEN (SELECT CASE WHEN account_id = {$account_id} AND type = 1 THEN 'Credit' ELSE 'Debit' END FROM transaction_list WHERE ref_table = 'transactions' AND ref_id = t.id AND account_id = {$account_id} LIMIT 1)
                WHEN 'opening_balance' THEN (SELECT CASE WHEN type = 1 THEN 'Credit' ELSE 'Debit' END FROM transaction_list WHERE ref_table = 'transactions' AND ref_id = t.id AND account_id = {$account_id} LIMIT 1)
                ELSE 'Debit' 
            END as dr_cr,
            CASE t.type
                WHEN 'payment' THEN (SELECT CASE WHEN type='sale' THEN 'Sales' ELSE 'Purchase' END FROM transactions WHERE id=t.parent_id)
                WHEN 'expense' THEN 'Expense'
                WHEN 'transfer' THEN 'Fund Transfer'
                WHEN 'opening_balance' THEN 'Opening'
                ELSE t.type
            END as txn_type,
            COALESCE(NULLIF(t.reference_code, ''), (SELECT reference_code FROM transactions WHERE id=t.parent_id), t.reference_code, '') as code,
            COALESCE(a.name, (SELECT name FROM account_list WHERE id={$account_id})) as mode
         FROM transactions t
         LEFT JOIN account_list a ON t.account_id = a.id
         WHERE (t.account_id = {$account_id} OR t.id IN (SELECT ref_id FROM transaction_list WHERE ref_table='transactions' AND account_id = {$account_id}))
           AND t.type IN ('payment','expense','transfer','opening_balance')
           AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'
         ORDER BY t.transaction_date ASC
    ");

    $running = $open_balance;
    while ($r = $txns->fetch_assoc()) {
        if ($r['dr_cr'] === 'Credit') { $r['debit'] = 0; $r['credit'] = $r['amount']; $running += $r['amount']; $t_credit += $r['amount']; }
        else                          { $r['debit'] = $r['amount']; $r['credit'] = 0; $running -= $r['amount']; $t_debit  += $r['amount']; }
        $r['running_balance'] = $running;
        $rows[] = $r;
    }
}
$accounts = $conn->query("SELECT id, name FROM account_list ORDER BY name");
?>
<div class="card card-outline card-dark shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-book mr-1"></i>Account Ledger Report</h3>
        <div class="card-tools">
            <button class="btn btn-sm btn-info print-btn"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <div id="print-data">
        <form method="GET">
            <input type="hidden" name="page" value="reports/account_ledger">
            <div class="row align-items-end">
                <div class="form-group col-md-3">
                    <label class="small font-weight-bold">Account</label>
                    <select name="account_id" class="form-control form-control-sm select2" required>
                        <option value="">-- Select Account --</option>
                        <?php while($a=$accounts->fetch_assoc()): ?>
                            <option value="<?php echo $a['id'] ?>" <?php echo $account_id==$a['id']?'selected':'' ?>><?php echo htmlspecialchars($a['name']) ?></option>
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
                <div class="form-group col-md-3">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i> View Ledger</button>
                    <a href="<?php echo base_url ?>admin/?page=reports/account_ledger" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        <hr class="mt-0">
        <?php if(!$account_id): ?>
            <div class="text-center text-muted py-5"><i class="fas fa-book fa-3x mb-3 d-block text-light"></i>Please select an account to view its ledger.</div>
        <?php else: ?>
            <h5 class="font-weight-bold"><?php echo htmlspecialchars($account_name) ?> — Ledger</h5>
            <small class="text-muted"><?php echo date('d-m-Y',strtotime($from)) ?> to <?php echo date('d-m-Y',strtotime($to)) ?> &nbsp;|&nbsp; Opening Balance: <strong><?php echo number_format($open_balance,2) ?></strong> &nbsp;|&nbsp; Period End Balance: <strong><?php echo number_format($running,2) ?></strong></small>
            <div class="table-responsive mt-2">
                <table class="table table-bordered table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th><th>Date</th><th>Code</th><th>Type</th><th>Mode</th>
                            <th class="text-right">Debit (Out)</th>
                            <th class="text-right">Credit (In)</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                    <tr class="table-secondary">
                        <td colspan="5" class="font-weight-bold">Account Opening Balance</td>
                        <td></td><td></td>
                        <td class="text-right font-weight-bold"><?php echo number_format($open_balance,2) ?></td>
                    </tr>
                    <?php if(empty($rows)): ?><tr><td colspan="8" class="text-center text-muted py-4">No transactions in this period.</td></tr><?php endif; ?>
                    <?php $i=1; foreach($rows as $r): ?>
                        <tr>
                            <td><?php echo $i++ ?></td>
                            <td><?php echo date('d-m-Y',strtotime($r['date_created'])) ?></td>
                            <td><?php echo htmlspecialchars($r['code']) ?></td>
                            <td><?php echo htmlspecialchars($r['txn_type']) ?></td>
                            <td><?php echo htmlspecialchars($r['mode']??'') ?></td>
                            <td class="text-right text-danger"><?php echo $r['debit']>0?number_format($r['debit'],2):'–' ?></td>
                            <td class="text-right text-success"><?php echo $r['credit']>0?number_format($r['credit'],2):'–' ?></td>
                            <td class="text-right font-weight-bold <?php echo $r['running_balance']<0?'text-danger':'text-success' ?>"><?php echo number_format($r['running_balance'],2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark font-weight-bold">
                            <th colspan="5" class="text-right">TOTALS for Period</th>
                            <th class="text-right text-danger"><?php echo number_format($t_debit,2) ?></th>
                            <th class="text-right text-success"><?php echo number_format($t_credit,2) ?></th>
                            <th class="text-right"><?php echo number_format($running,2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>$(function(){
    if($.fn.select2) $('.select2').select2({theme:'bootstrap4',width:'100%'});
    $('.print-btn').click(function(){
        start_loader();
        var nw=window.open('','_blank','width=1100,height=900');
        nw.document.write('<html>'+$('head').clone()[0].outerHTML+'<body>'+$('#print-data').html()+'</body></html>');
        nw.document.close();
        setTimeout(function(){nw.print();setTimeout(function(){nw.close();end_loader();},200);},500);
    });
});</script>
