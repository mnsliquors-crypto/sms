<?php
$as_of = isset($_GET['as_of']) ? $_GET['as_of'] : date("Y-m-d");

$qry = $conn->query("
    SELECT i.id, i.name, c.name as category,
           COALESCE((SELECT AVG(ti.unit_price) FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE ti.item_id=i.id AND t.type IN ('purchase', 'opening_stock') AND ti.unit_price>0 AND DATE(t.transaction_date)<='{$as_of}'), 0) as avg_cost,
           COALESCE((SELECT SUM(CASE 
                                WHEN t.type IN ('purchase', 'opening_stock', 'adjustment') THEN ti.quantity 
                                WHEN t.type = 'sale' THEN -ti.quantity 
                                WHEN t.type = 'return' THEN (CASE WHEN EXISTS(SELECT 1 FROM entity_list WHERE id = t.entity_id AND entity_type = 'Supplier') THEN -ti.quantity ELSE ti.quantity END)
                                ELSE 0 END) 
                      FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id 
                      WHERE ti.item_id=i.id AND DATE(t.transaction_date)<='{$as_of}'), 0) as qty_on_hand
    FROM item_list i
    LEFT JOIN category_list c ON i.category_id=c.id
    WHERE i.status=1
    ORDER BY i.name ASC
");

$t_value = 0; $rows = [];
while ($r = $qry->fetch_assoc()) {
    $r['stock_value'] = max(0, $r['qty_on_hand']) * $r['avg_cost'];
    $t_value += $r['stock_value'];
    $rows[] = $r;
}
?>
<div class="card card-outline card-success shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-dollar-sign mr-1"></i>Stock Valuation Report</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/reports/report_api.php?action=export_excel&report=stock_current" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Export</a>
            <button class="btn btn-sm btn-info print-btn"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET">
            <input type="hidden" name="page" value="reports/stock_valuation">
            <div class="row align-items-end">
                <div class="form-group col-md-3">
                    <label class="small font-weight-bold">As of Date</label>
                    <input type="date" name="as_of" value="<?php echo $as_of ?>" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-2">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-calculator"></i> Calculate</button>
                </div>
            </div>
        </form>
        <hr class="mt-0">

        <!-- Grand total card -->
        <div class="callout callout-success mb-3">
            <h5><i class="fas fa-warehouse mr-1"></i>Total Inventory Value as of <?php echo date('d-m-Y',strtotime($as_of)) ?></h5>
            <h2 class="font-weight-bold text-success"><?php echo number_format($t_value,2) ?></h2>
            <p class="mb-0 text-muted">Based on weighted average cost for <?php echo count($rows) ?> active items</p>
        </div>

        <div id="print-data">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th><th>Item Name</th><th>Category</th>
                            <th class="text-right">Qty on Hand</th>
                            <th class="text-right">Avg Cost</th>
                            <th class="text-right">Stock Value</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($rows)): ?><tr><td colspan="6" class="text-center text-muted py-4">No items found.</td></tr><?php endif; ?>
                    <?php $i=1; foreach($rows as $r): ?>
                        <tr <?php echo $r['qty_on_hand']<=0?'class="text-muted"':'' ?>>
                            <td><?php echo $i++ ?></td>
                            <td><?php echo htmlspecialchars($r['name']) ?></td>
                            <td><?php echo htmlspecialchars($r['category']??'N/A') ?></td>
                            <td class="text-right"><?php echo number_format(max(0,$r['qty_on_hand']),2) ?></td>
                            <td class="text-right"><?php echo number_format($r['avg_cost'],2) ?></td>
                            <td class="text-right font-weight-bold"><?php echo number_format($r['stock_value'],2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-success font-weight-bold">
                            <th colspan="5" class="text-right">TOTAL INVENTORY VALUE</th>
                            <th class="text-right"><?php echo number_format($t_value,2) ?></th>
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
