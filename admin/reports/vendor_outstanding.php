<?php
$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$status_f  = isset($_GET['status_f']) ? $_GET['status_f'] : 'outstanding';
$from        = isset($_GET['from']) ? $_GET['from'] : date("Y-m-01");
$to          = isset($_GET['to'])   ? $_GET['to']   : date("Y-m-d");

$and_vendor = $vendor_id ? " AND t.entity_id={$vendor_id}" : '';
$and_date   = " AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'";
$and_status = '';
if ($status_f === 'outstanding') $and_status = " HAVING pending_amount > 0";
elseif ($status_f === 'paid')    $and_status = " HAVING pending_amount <= 0";

$qry = $conn->query("
    SELECT
        t.id as transaction_id,
        t.reference_code,
        t.transaction_date,
        v.id as vid,
        COALESCE(v.display_name,'Unknown') as vendor_name,
        COALESCE(v.contact,'') as contact,
        t.total_amount as total_purchase,
        (SELECT COALESCE(SUM(p.total_amount),0) FROM transactions p WHERE p.parent_id = t.id AND p.type IN ('payment', 'return')) as total_paid_returned,
        (t.total_amount - (SELECT COALESCE(SUM(p.total_amount),0) FROM transactions p WHERE p.parent_id = t.id AND p.type IN ('payment', 'return'))) as pending_amount
    FROM transactions t
    LEFT JOIN entity_list v ON t.entity_id = v.id AND v.entity_type = 'Supplier'
    WHERE t.type = 'purchase' {$and_vendor} {$and_date}
    {$and_status}
    ORDER BY t.transaction_date ASC
");


$t_purchase=$t_paid=$t_pending=0;
$rows=[];
while($r=$qry->fetch_assoc()){
    $t_purchase += $r['total_purchase'];
    $t_paid     += $r['total_paid_returned'];
    $t_pending  += max(0,$r['pending_amount']);
    $rows[]=$r;
}
$vendors=$conn->query("SELECT id, display_name as name FROM entity_list WHERE entity_type = 'Supplier' AND status=1 ORDER BY display_name");
?>
<div class="card card-outline card-primary shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-truck mr-1"></i>Vendor Outstanding Report</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/reports/report_api.php?action=export_excel&report=vendor_outstanding&vendor_id=<?php echo $vendor_id ?>&status_f=<?php echo $status_f ?>&from=<?php echo $from ?>&to=<?php echo $to ?>" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Export</a>
            <button class="btn btn-sm btn-info print-btn"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <div id="print-data">
        <form method="GET">
            <input type="hidden" name="page" value="reports/vendor_outstanding">
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
                    <label class="small font-weight-bold">Vendor</label>
                    <select name="vendor_id" class="form-control form-control-sm select2">
                        <option value="">-- All Vendors --</option>
                        <?php while($v=$vendors->fetch_assoc()): ?>
                            <option value="<?php echo $v['id'] ?>" <?php echo $vendor_id==$v['id']?'selected':'' ?>><?php echo htmlspecialchars($v['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label class="small font-weight-bold">Status</label>
                    <select name="status_f" class="form-control form-control-sm">
                        <option value="">-- All --</option>
                        <option value="outstanding" <?php echo $status_f==='outstanding'?'selected':'' ?>>Outstanding Only</option>
                        <option value="paid" <?php echo $status_f==='paid'?'selected':'' ?>>Fully Paid Only</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="<?php echo base_url ?>admin/?page=reports/vendor_outstanding" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        <hr class="mt-0">

        <div class="row mb-3">
            <div class="col-md-4">
                <div class="info-box bg-light shadow-sm"><span class="info-box-icon bg-primary"><i class="fas fa-shopping-basket"></i></span>
                    <div class="info-box-content"><span class="info-box-text">Total Purchase</span><span class="info-box-number"><?php echo number_format($t_purchase,2) ?></span></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box bg-light shadow-sm"><span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content"><span class="info-box-text">Total Paid</span><span class="info-box-number"><?php echo number_format($t_paid,2) ?></span></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box bg-light shadow-sm"><span class="info-box-icon bg-danger"><i class="fas fa-exclamation-circle"></i></span>
                    <div class="info-box-content"><span class="info-box-text">Total Payable</span><span class="info-box-number text-danger"><?php echo number_format($t_pending,2) ?></span></div>
                </div>
            </div>
        </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th><th>Date</th><th>Bill No.</th><th>Vendor</th><th>Contact</th>
                            <th class="text-right">Total Amount</th>
                            <th class="text-right">Total Paid</th>
                            <th class="text-right">Pending Amount</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($rows)): ?><tr><td colspan="9" class="text-center text-muted py-4">No records found.</td></tr><?php endif; ?>
                    <?php $i=1; foreach($rows as $r): $pen=max(0,$r['pending_amount']); ?>
                        <tr>
                            <td><?php echo $i++ ?></td>
                            <td><?php echo date('d-m-Y',strtotime($r['transaction_date'])) ?></td>
                            <td class="font-weight-bold"><a href="<?php echo base_url ?>admin/?page=transactions/purchases/view_purchase&id=<?php echo $r['transaction_id'] ?>" target="_blank"><?php echo htmlspecialchars($r['reference_code']) ?></a></td>
                            <td class="font-weight-bold"><?php echo htmlspecialchars($r['vendor_name']) ?></td>
                            <td><?php echo htmlspecialchars($r['contact']) ?></td>
                            <td class="text-right"><?php echo number_format($r['total_purchase'],2) ?></td>
                            <td class="text-right text-success"><?php echo number_format($r['total_paid_returned'],2) ?></td>
                            <td class="text-right font-weight-bold <?php echo $pen>0?'text-danger':'' ?>"><?php echo number_format($pen,2) ?></td>
                            <td class="text-center">
                                <?php if($pen<=0): ?><span class="badge badge-success">Paid</span>
                                <?php elseif($r['total_paid_returned']>0): ?><span class="badge badge-warning">Partial</span>
                                <?php else: ?><span class="badge badge-danger">Unpaid</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark font-weight-bold">
                            <th colspan="5" class="text-right">TOTALS</th>
                            <th class="text-right"><?php echo number_format($t_purchase,2) ?></th>
                            <th class="text-right"><?php echo number_format($t_paid,2) ?></th>
                            <th class="text-right text-danger"><?php echo number_format($t_pending,2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
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
