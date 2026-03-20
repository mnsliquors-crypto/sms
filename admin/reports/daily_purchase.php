<?php
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d");
$to   = isset($_GET['to'])   ? $_GET['to']   : date("Y-m-d");
$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;

$qry = $conn->query("
    SELECT t.*, v.display_name as vendor_name, COALESCE(t.tax,0) as vat_input, t.reference_code as po_code, t.transaction_date as date_created, t.total_amount as amount,
           (SELECT COALESCE(SUM(total_amount),0) FROM transactions p WHERE p.parent_id = t.id AND p.type='payment') as paid_amount
    FROM transactions t
    LEFT JOIN entity_list v ON t.entity_id = v.id AND v.entity_type = 'Supplier'
    WHERE DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}' " . ($vendor_id ? " AND t.entity_id = {$vendor_id}" : "") . "
      AND t.type = 'purchase'
    ORDER BY t.transaction_date DESC
");

$t_total=$t_vat=$t_paid=$t_pending=0;
$rows=[];
while($r=$qry->fetch_assoc()){
    $t_total  += $r['amount'];
    $t_vat    += $r['vat_input'];
    $t_paid   += $r['paid_amount'];
    $t_pending+= max(0,$r['amount']-$r['paid_amount']);
    $rows[]=$r;
}
$vendors=$conn->query("SELECT id, display_name as name FROM entity_list WHERE entity_type = 'Supplier' AND status=1 ORDER BY display_name");
?>
<div class="card card-outline card-primary shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shopping-basket mr-1"></i>Daily Purchase Report</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/reports/report_api.php?action=export_excel&report=daily_purchase&from=<?php echo $from ?>&to=<?php echo $to ?>" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Export</a>
            <button class="btn btn-sm btn-info print-btn" type="button"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <div id="print-data">
        <form method="GET">
            <input type="hidden" name="page" value="reports/daily_purchase">
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
                    <label class="control-label small font-weight-bold">Vendor</label>
                    <select name="vendor_id" class="form-control form-control-sm select2">
                        <option value="">-- All Vendors --</option>
                        <?php while($v=$vendors->fetch_assoc()): ?>
                            <option value="<?php echo $v['id'] ?>" <?php echo $vendor_id==$v['id']?'selected':'' ?>><?php echo htmlspecialchars($v['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="<?php echo base_url ?>admin/?page=reports/daily_purchase" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        <hr class="mt-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th><th>Bill No</th><th>Date</th><th>Vendor</th>
                            <th class="text-right">Total Amt</th>
                            <th class="text-right">VAT Input</th>
                            <th class="text-right">Paid Amt</th>
                            <th class="text-right">Pending</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($rows)): ?><tr><td colspan="9" class="text-center text-muted py-4">No records found.</td></tr><?php endif; ?>
                    <?php $i=1; foreach($rows as $r): ?>
                        <tr>
                            <td><?php echo $i++ ?></td>
                            <td><a href="<?php echo base_url ?>admin/?page=purchases/view_purchase&id=<?php echo $r['id'] ?>" target="_blank"><?php echo htmlspecialchars($r['po_code']) ?></a></td>
                            <td><?php echo date('d-m-Y',strtotime($r['date_created'])) ?></td>
                            <td><?php echo htmlspecialchars($r['vendor_name']??'N/A') ?></td>
                            <td class="text-right font-weight-bold"><?php echo number_format($r['amount'],2) ?></td>
                            <td class="text-right"><?php echo number_format($r['vat_input'],2) ?></td>
                            <td class="text-right"><?php echo number_format($r['paid_amount'],2) ?></td>
                            <td class="text-right text-danger"><?php echo number_format(max(0,$r['amount']-$r['paid_amount']),2) ?></td>
                            <td class="text-center">
                                <?php 
                                    $row_balance = $r['amount'] - $r['paid_amount'];
                                    $payment_status = ($row_balance <= 0) ? 1 : (($r['paid_amount'] > 0) ? 2 : 0);
                                    if($payment_status == 1): ?><span class="badge badge-success">Paid</span>
                                <?php elseif($payment_status == 2): ?><span class="badge badge-warning">Partial</span>
                                <?php else: ?><span class="badge badge-danger">Unpaid</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark font-weight-bold">
                            <th colspan="4" class="text-right">TOTALS</th>
                            <th class="text-right"><?php echo number_format($t_total,2) ?></th>
                            <th class="text-right"><?php echo number_format($t_vat,2) ?></th>
                            <th class="text-right"><?php echo number_format($t_paid,2) ?></th>
                            <th class="text-right"><?php echo number_format($t_pending,2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="row mt-2">
                <div class="col-md-4 offset-md-8">
                    <table class="table table-bordered table-sm">
                        <tr><td>Total Purchase</td><td class="text-right font-weight-bold"><?php echo number_format($t_total,2) ?></td></tr>
                        <tr><td>Total VAT Input</td><td class="text-right"><?php echo number_format($t_vat,2) ?></td></tr>
                        <tr><td>Total Paid</td><td class="text-right text-success"><?php echo number_format($t_paid,2) ?></td></tr>
                        <tr class="table-danger"><th>Total Pending</th><th class="text-right"><?php echo number_format($t_pending,2) ?></th></tr>
                    </table>
                </div>
            </div>
        </div>
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
