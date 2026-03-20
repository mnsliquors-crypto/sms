<?php
$from     = isset($_GET['from'])     ? $_GET['from']     : date("Y-m-d");
$to       = isset($_GET['to'])       ? $_GET['to']       : date("Y-m-d");
$exp_type = isset($_GET['exp_type']) ? intval($_GET['exp_type']) : 0; // 1=Variable, 2=Fixed, etc.

$where = "DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'";
// Filter by category_id if needed, assuming it's stored in certain fields or we filter by entity_id/account_id
if ($exp_type) $where .= " AND t.account_id = {$exp_type}"; // Adjust based on how exp_type was intended

$qry = $conn->query("SELECT *, transaction_date as date_created, total_amount as amount, remarks as name FROM transactions t WHERE {$where} AND type='expense' ORDER BY transaction_date DESC");
$t_amount = 0;
$rows = [];
while ($r = $qry->fetch_assoc()) { 
    $r['type'] = 1; // Defaulting for visual badge
    $t_amount += $r['amount']; $rows[] = $r; 
}

// Distinct categories from 'name' field
$name_totals = [];
?>
<div class="card card-outline card-danger shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-receipt mr-1"></i>Expense Report</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/reports/report_api.php?action=export_excel&report=daily_expense&from=<?php echo $from ?>&to=<?php echo $to ?>" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Export</a>
            <button class="btn btn-sm btn-info print-btn"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <div id="print-data">
        <form method="GET">
            <input type="hidden" name="page" value="reports/daily_expense">
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
                    <label class="small font-weight-bold">Type</label>
                    <select name="exp_type" class="form-control form-control-sm">
                        <option value="0">-- All Types --</option>
                        <option value="1" <?php echo $exp_type===1?'selected':'' ?>>Type 1</option>
                        <option value="2" <?php echo $exp_type===2?'selected':'' ?>>Type 2</option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="<?php echo base_url ?>admin/?page=reports/daily_expense" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        <hr class="mt-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark">
                        <tr><th>#</th><th>Date</th><th>Name / Description</th><th>Type</th><th class="text-right">Amount</th><th>Remarks</th></tr>
                    </thead>
                    <tbody>
                    <?php if(empty($rows)): ?><tr><td colspan="6" class="text-center text-muted py-4">No records found.</td></tr><?php endif; ?>
                    <?php $i=1; foreach($rows as $r): ?>
                        <tr>
                            <td><?php echo $i++ ?></td>
                            <td><?php echo date('d-m-Y',strtotime($r['date_created'])) ?></td>
                            <td><?php echo htmlspecialchars($r['name']??'') ?></td>
                            <td><span class="badge badge-secondary"><?php echo $r['type']==1?'Variable':'Fixed' ?></span></td>
                            <td class="text-right font-weight-bold"><?php echo number_format($r['amount'],2) ?></td>
                            <td><?php echo htmlspecialchars($r['remarks']??'') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark font-weight-bold">
                            <th colspan="4" class="text-right">TOTAL EXPENSES</th>
                            <th class="text-right"><?php echo number_format($t_amount,2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="row mt-2">
                <div class="col-md-4 offset-md-8">
                    <table class="table table-bordered table-sm">
                        <?php
                        // Group by category
                        $cat_totals = [];
                        foreach($rows as $r) { $cat_totals[$r['category']??'Other'] = ($cat_totals[$r['category']??'Other'] ?? 0) + $r['amount']; }
                        arsort($cat_totals);
                        foreach($cat_totals as $cat=>$amt): ?>
                        <tr><td><?php echo htmlspecialchars($cat) ?></td><td class="text-right"><?php echo number_format($amt,2) ?></td></tr>
                        <?php endforeach; ?>
                        <tr class="table-danger"><th>Grand Total</th><th class="text-right"><?php echo number_format($t_amount,2) ?></th></tr>
                    </table>
                </div>
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
