<?php
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d");
$to   = isset($_GET['to'])   ? $_GET['to']   : date("Y-m-d");

// Sales data
$t_sales=$t_cogs=$t_expenses=0;

$sales_qry = $conn->query("
    SELECT t.*, COALESCE(c.display_name,'Walk-in') as customer,
           t.total_amount as amount, t.reference_code as sales_code, t.transaction_date as date_created
    FROM transactions t
    LEFT JOIN entity_list c ON t.entity_id=c.id AND c.entity_type = 'Customer'
    WHERE t.type = 'sale' AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'
    ORDER BY t.transaction_date ASC
");
$sales_rows=[];
while($r=$sales_qry->fetch_assoc()){
    $cogs = $conn->query("SELECT COALESCE(SUM(quantity*cost),0) as c FROM (SELECT ti.quantity, (SELECT cost FROM item_list WHERE id = ti.item_id) as cost FROM transaction_items ti WHERE ti.transaction_id = '{$r['id']}') as items")->fetch_assoc()['c'] ?? 0;
    $r['cogs']=$cogs;
    $t_sales += $r['amount'];
    $t_cogs  += $cogs;
    $sales_rows[]=$r;
}

$exp_qry=$conn->query("SELECT t.transaction_date as date_created, t.total_amount as amount, t.remarks, 'Expense' as category FROM transactions t WHERE t.type = 'expense' AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}' ORDER BY t.transaction_date ASC");
$exp_rows=[];
while($r=$exp_qry->fetch_assoc()){ $t_expenses+=$r['amount']; $exp_rows[]=$r; }

$gross_profit = $t_sales - $t_cogs;
$net_profit   = $gross_profit - $t_expenses;
?>
<div class="card card-outline card-purple shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i>Profit & Loss Report</h3>
        <div class="card-tools">
            <button class="btn btn-sm btn-info print-btn"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <div id="print-data">
        <form method="GET">
            <input type="hidden" name="page" value="reports/profit">
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
                    <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="<?php echo base_url ?>admin/?page=reports/profit" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        <hr class="mt-0">
            <!-- P&L Summary -->
            <div class="row mb-3">
                <div class="col-md-6 offset-md-3">
                    <div class="card <?php echo $net_profit>=0?'card-success':'card-danger' ?> card-outline">
                        <div class="card-header text-center"><h5 class="font-weight-bold mb-0">Profit & Loss Statement</h5>
                            <small class="text-muted"><?php echo date('d-m-Y',strtotime($from)) ?> — <?php echo date('d-m-Y',strtotime($to)) ?></small>
                        </div>
                        <div class="card-body p-0">
                            <table class="table mb-0">
                                <tr><td>Total Sales (Revenue)</td><td class="text-right font-weight-bold text-success"><?php echo number_format($t_sales,2) ?></td></tr>
                                <tr><td>Less: Cost of Goods Sold (COGS)</td><td class="text-right text-danger">– <?php echo number_format($t_cogs,2) ?></td></tr>
                                <tr class="table-light"><th>Gross Profit</th><th class="text-right <?php echo $gross_profit>=0?'text-success':'text-danger' ?>"><?php echo number_format($gross_profit,2) ?></th></tr>
                                <tr><td>Less: Operational Expenses</td><td class="text-right text-danger">– <?php echo number_format($t_expenses,2) ?></td></tr>
                                <tr class="<?php echo $net_profit>=0?'table-success':'table-danger' ?>">
                                    <th><?php echo $net_profit>=0?'Net Profit':'Net Loss' ?></th>
                                    <th class="text-right"><?php echo number_format(abs($net_profit),2) ?></th>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="row mb-3">
                <div class="col-md-3"><div class="small-box bg-info"><div class="inner"><h4><?php echo number_format($t_sales,2) ?></h4><p>Total Sales</p></div><div class="icon"><i class="fas fa-chart-line"></i></div></div></div>
                <div class="col-md-3"><div class="small-box bg-warning"><div class="inner"><h4><?php echo number_format($t_cogs,2) ?></h4><p>Cost of Goods Sold</p></div><div class="icon"><i class="fas fa-boxes"></i></div></div></div>
                <div class="col-md-3"><div class="small-box bg-danger"><div class="inner"><h4><?php echo number_format($t_expenses,2) ?></h4><p>Expenses</p></div><div class="icon"><i class="fas fa-receipt"></i></div></div></div>
                <div class="col-md-3"><div class="small-box <?php echo $net_profit>=0?'bg-success':'bg-danger' ?>"><div class="inner"><h4><?php echo number_format($net_profit,2) ?></h4><p><?php echo $net_profit>=0?'Net Profit':'Net Loss' ?></p></div><div class="icon"><i class="fas fa-coins"></i></div></div></div>
            </div>

            <!-- Sales detail -->
            <h6 class="font-weight-bold"><i class="fas fa-chart-bar text-success mr-1"></i>Sales Breakdown</h6>
            <div class="table-responsive mb-3">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-light"><tr><th>#</th><th>Date</th><th>Bill No</th><th>Customer</th><th class="text-right">Sales Amt</th><th class="text-right">COGS</th><th class="text-right">Gross Profit</th></tr></thead>
                    <tbody>
                    <?php if(empty($sales_rows)): ?><tr><td colspan="7" class="text-center text-muted">No sales in this period.</td></tr><?php endif; ?>
                    <?php $i=1; foreach($sales_rows as $r): $gp=$r['amount']-$r['cogs']; ?>
                        <tr>
                            <td><?php echo $i++ ?></td>
                            <td><?php echo date('d-m-Y',strtotime($r['date_created'])) ?></td>
                            <td><a href="<?php echo base_url ?>admin/?page=sales/view_sale&id=<?php echo $r['id'] ?>" target="_blank"><?php echo $r['sales_code'] ?></a></td>
                            <td><?php echo htmlspecialchars($r['customer']) ?></td>
                            <td class="text-right"><?php echo number_format($r['amount'],2) ?></td>
                            <td class="text-right"><?php echo number_format($r['cogs'],2) ?></td>
                            <td class="text-right <?php echo $gp>=0?'text-success':'text-danger' ?>"><?php echo number_format($gp,2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark font-weight-bold">
                            <th colspan="4" class="text-right">TOTALS</th>
                            <th class="text-right"><?php echo number_format($t_sales,2) ?></th>
                            <th class="text-right"><?php echo number_format($t_cogs,2) ?></th>
                            <th class="text-right <?php echo $gross_profit>=0?'text-success':'text-danger' ?>"><?php echo number_format($gross_profit,2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Expenses detail -->
            <h6 class="font-weight-bold"><i class="fas fa-receipt text-danger mr-1"></i>Expenses Breakdown</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-light"><tr><th>#</th><th>Date</th><th>Category</th><th>Remarks</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                    <?php if(empty($exp_rows)): ?><tr><td colspan="5" class="text-center text-muted">No expenses in this period.</td></tr><?php endif; ?>
                    <?php $i=1; foreach($exp_rows as $r): ?>
                        <tr>
                            <td><?php echo $i++ ?></td>
                            <td><?php echo date('d-m-Y',strtotime($r['date_created'])) ?></td>
                            <td><?php echo htmlspecialchars($r['category']??'–') ?></td>
                            <td><?php echo htmlspecialchars($r['remarks']??'–') ?></td>
                            <td class="text-right text-danger"><?php echo number_format($r['amount'],2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr class="table-dark font-weight-bold"><th colspan="4" class="text-right">TOTAL EXPENSES</th><th class="text-right text-danger"><?php echo number_format($t_expenses,2) ?></th></tr></tfoot>
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
