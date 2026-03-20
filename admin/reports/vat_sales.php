<?php
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d");
$to   = isset($_GET['to'])   ? $_GET['to']   : date("Y-m-d");

// Note: Current schema focuses on core transaction data.
// Output VAT tracking requires dedicated tax columns in transactions.
// This report shows all sales as taxable supplies (VAT amount = 0 until schema is extended).
$qry = $conn->query("
    SELECT t.*, COALESCE(c.display_name,'Walk-in') as customer_name,
           t.discount as disc, t.transaction_date as date_created, t.reference_code as sales_code
    FROM transactions t
    LEFT JOIN entity_list c ON t.entity_id=c.id AND c.entity_type = 'Customer'
    WHERE t.type = 'sale' AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'
    ORDER BY t.transaction_date DESC
");
$t_taxable=$t_vat=$t_total=0;
$rows=[];
while($r=$qry->fetch_assoc()){
    $r['vat_amt']     = floatval($r['tax']);
    $r['taxable_amt'] = floatval($r['total_amount']) - $r['vat_amt'];
    $t_taxable += $r['taxable_amt'];
    $t_vat     += $r['vat_amt'];
    $t_total   += floatval($r['total_amount']);
    $rows[]=$r;
}
?>
<div class="card card-outline card-olive shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i>VAT Sales Report</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/reports/report_api.php?action=export_excel&report=vat_sales&from=<?php echo $from ?>&to=<?php echo $to ?>" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Export</a>
            <button class="btn btn-sm btn-info print-btn"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET">
            <input type="hidden" name="page" value="reports/vat_sales">
            <div class="row align-items-end">
                <div class="form-group col-md-2"><label class="small font-weight-bold">Date From</label>
                    <input type="date" name="from" value="<?php echo $from ?>" class="form-control form-control-sm"></div>
                <div class="form-group col-md-2"><label class="small font-weight-bold">Date To</label>
                    <input type="date" name="to" value="<?php echo $to ?>" class="form-control form-control-sm"></div>
                <div class="form-group col-md-3">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="<?php echo base_url ?>admin/?page=reports/vat_sales" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        <hr class="mt-0">

        <!-- VAT summary boxes -->
        <div class="row mb-3">
            <div class="col-md-4"><div class="small-box bg-info"><div class="inner"><h4><?php echo number_format($t_taxable,2) ?></h4><p>Taxable Amount</p></div><div class="icon"><i class="fas fa-file-invoice"></i></div></div></div>
            <div class="col-md-4"><div class="small-box bg-warning"><div class="inner"><h4><?php echo number_format($t_vat,2) ?></h4><p>Total Output VAT</p></div><div class="icon"><i class="fas fa-percentage"></i></div></div></div>
            <div class="col-md-4"><div class="small-box bg-success"><div class="inner"><h4><?php echo number_format($t_total,2) ?></h4><p>Total Sales (incl. VAT)</p></div><div class="icon"><i class="fas fa-money-bill"></i></div></div></div>
        </div>

        <div id="print-data">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th><th>Date</th><th>Bill No</th><th>Customer</th>
                            <th class="text-right">Taxable Amt</th>
                            <th class="text-right">VAT Rate</th>
                            <th class="text-right">VAT Amount</th>
                            <th class="text-right">Total (incl. VAT)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($rows)): ?><tr><td colspan="8" class="text-center text-muted py-4">No VAT sales records found for selected period.</td></tr><?php endif; ?>
                    <?php $i=1; foreach($rows as $r):
                        $rate = $r['taxable_amt']>0 ? round(($r['vat_amt']/$r['taxable_amt'])*100,2) : 0; ?>
                        <tr>
                            <td><?php echo $i++ ?></td>
                            <td><?php echo date('d-m-Y',strtotime($r['date_created'])) ?></td>
                            <td><a href="<?php echo base_url ?>admin/?page=sales/view_sale&id=<?php echo $r['id'] ?>" target="_blank"><?php echo htmlspecialchars($r['sales_code']) ?></a></td>
                            <td><?php echo htmlspecialchars($r['customer_name']) ?></td>
                            <td class="text-right"><?php echo number_format($r['taxable_amt'],2) ?></td>
                            <td class="text-right"><?php echo $rate ?>%</td>
                            <td class="text-right text-warning font-weight-bold"><?php echo number_format($r['vat_amt'],2) ?></td>
                            <td class="text-right font-weight-bold"><?php echo number_format($r['total_amount'],2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark font-weight-bold">
                            <th colspan="4" class="text-right">TOTALS</th>
                            <th class="text-right"><?php echo number_format($t_taxable,2) ?></th>
                            <th></th>
                            <th class="text-right text-warning"><?php echo number_format($t_vat,2) ?></th>
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
