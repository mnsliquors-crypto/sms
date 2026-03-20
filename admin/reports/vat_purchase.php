<?php
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d");
$to   = isset($_GET['to'])   ? $_GET['to']   : date("Y-m-d");

$qry = $conn->query("
    SELECT t.id, t.transaction_date as date_created, t.reference_code as po_code, t.total_amount as amount, v.display_name as vendor_name, 0 as vat_input
    FROM transactions t
    LEFT JOIN entity_list v ON t.entity_id=v.id AND v.entity_type = 'Supplier'
    WHERE t.type = 'purchase' AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'
    ORDER BY t.transaction_date DESC
");
$t_taxable=$t_vat_in=$t_total=0;
$rows=[];
while($r=$qry->fetch_assoc()){
    $r['taxable_amt']=$r['amount']-$r['vat_input'];
    $t_taxable += $r['taxable_amt'];
    $t_vat_in  += $r['vat_input'];
    $t_total   += $r['amount'];
    $rows[]=$r;
}
?>
<div class="card card-outline card-navy shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i>VAT Purchase Report</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/reports/report_api.php?action=export_excel&report=vat_purchase&from=<?php echo $from ?>&to=<?php echo $to ?>" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Export</a>
            <button class="btn btn-sm btn-info print-btn"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET">
            <input type="hidden" name="page" value="reports/vat_purchase">
            <div class="row align-items-end">
                <div class="form-group col-md-2"><label class="small font-weight-bold">Date From</label>
                    <input type="date" name="from" value="<?php echo $from ?>" class="form-control form-control-sm"></div>
                <div class="form-group col-md-2"><label class="small font-weight-bold">Date To</label>
                    <input type="date" name="to" value="<?php echo $to ?>" class="form-control form-control-sm"></div>
                <div class="form-group col-md-3">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="<?php echo base_url ?>admin/?page=reports/vat_purchase" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        <hr class="mt-0">

        <div class="row mb-3">
            <div class="col-md-4"><div class="small-box bg-info"><div class="inner"><h4><?php echo number_format($t_taxable,2) ?></h4><p>Taxable Amount</p></div><div class="icon"><i class="fas fa-file-invoice"></i></div></div></div>
            <div class="col-md-4"><div class="small-box bg-primary"><div class="inner"><h4><?php echo number_format($t_vat_in,2) ?></h4><p>Total Input VAT</p></div><div class="icon"><i class="fas fa-percentage"></i></div></div></div>
            <div class="col-md-4"><div class="small-box bg-navy"><div class="inner"><h4><?php echo number_format($t_total,2) ?></h4><p>Total Purchase (incl. VAT)</p></div><div class="icon"><i class="fas fa-shopping-basket"></i></div></div></div>
        </div>

        <div id="print-data">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th><th>Date</th><th>Bill No</th><th>Vendor</th>
                            <th class="text-right">Taxable Amt</th>
                            <th class="text-right">Input VAT</th>
                            <th class="text-right">Total (incl. VAT)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($rows)): ?><tr><td colspan="7" class="text-center text-muted py-4">No VAT purchase records found.</td></tr><?php endif; ?>
                    <?php $i=1; foreach($rows as $r): ?>
                        <tr>
                            <td><?php echo $i++ ?></td>
                            <td><?php echo date('d-m-Y',strtotime($r['date_created'])) ?></td>
                            <td><a href="<?php echo base_url ?>admin/?page=purchases/view_purchase&id=<?php echo $r['id'] ?>" target="_blank"><?php echo htmlspecialchars($r['po_code']) ?></a></td>
                            <td><?php echo htmlspecialchars($r['vendor_name']??'N/A') ?></td>
                            <td class="text-right"><?php echo number_format($r['taxable_amt'],2) ?></td>
                            <td class="text-right text-primary font-weight-bold"><?php echo number_format($r['vat_input'],2) ?></td>
                            <td class="text-right font-weight-bold"><?php echo number_format($r['amount'],2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark font-weight-bold">
                            <th colspan="4" class="text-right">TOTALS</th>
                            <th class="text-right"><?php echo number_format($t_taxable,2) ?></th>
                            <th class="text-right text-primary"><?php echo number_format($t_vat_in,2) ?></th>
                            <th class="text-right"><?php echo number_format($t_total,2) ?></th>
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
        var nw=window.open('','_blank','width=1200,height=900');
        nw.document.write('<html>'+$('head').clone()[0].outerHTML+'<body>'+$('#print-data').html()+'</body></html>');
        nw.document.close();
        setTimeout(function(){nw.print();setTimeout(function(){nw.close();end_loader();},200);},500);
    });
});</script>
