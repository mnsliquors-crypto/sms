<?php
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$and_search = $search ? " AND (i.name LIKE '%{$search}%' OR c.name LIKE '%{$search}%')" : '';

$qry = $conn->query("
    SELECT i.id, i.name, i.selling_price as sell_price,
           c.name as category,
           COALESCE((SELECT SUM(CASE 
                                WHEN t.type IN ('purchase', 'opening_stock', 'adjustment') THEN ti.quantity 
                                WHEN t.type = 'return' AND EXISTS(SELECT 1 FROM entity_list WHERE id = t.entity_id AND entity_type = 'Customer') THEN ti.quantity
                                ELSE 0 END) 
                      FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id 
                      WHERE ti.item_id=i.id),0) as pur_qty,
           COALESCE((SELECT SUM(CASE 
                                WHEN t.type = 'sale' THEN ti.quantity 
                                WHEN t.type = 'return' AND EXISTS(SELECT 1 FROM entity_list WHERE id = t.entity_id AND entity_type = 'Supplier') THEN ti.quantity
                                ELSE 0 END) 
                      FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id 
                      WHERE ti.item_id=i.id),0) as sal_qty,
           i.cost as avg_cost
    FROM item_list i
    LEFT JOIN category_list c ON i.category_id = c.id
    WHERE i.status=1
    {$and_search}
    ORDER BY i.name ASC
");

$t_value = $t_cur_stock = 0;
$rows = [];
while ($r = $qry->fetch_assoc()) {
    $r['cur_stock'] = $r['pur_qty'] - $r['sal_qty'];
    $r['stock_value'] = $r['cur_stock'] * $r['avg_cost'];
    $t_value     += $r['stock_value'];
    $t_cur_stock += $r['cur_stock'];
    $rows[] = $r;
}
?>
<div class="card card-outline card-info shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cubes mr-1"></i>Current Stock Report</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/reports/report_api.php?action=export_excel&report=stock_current&from=<?php echo date('Y-m-d') ?>&to=<?php echo date('Y-m-d') ?>" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Export</a>
            <button class="btn btn-sm btn-info print-btn"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <div id="print-data">
        <form method="GET">
            <input type="hidden" name="page" value="reports/stock_current">
            <div class="row align-items-end">
                <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Search Item / Category</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search) ?>" class="form-control" placeholder="Search...">
                        <div class="input-group-append"><button class="btn btn-primary"><i class="fas fa-search"></i></button></div>
                    </div>
                </div>
                <div class="form-group col-md-2">
                    <a href="<?php echo base_url ?>admin/?page=reports/stock_current" class="btn btn-sm btn-secondary btn-block"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        <hr class="mt-0">

        <!-- Summary -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="info-box bg-light shadow-sm">
                    <span class="info-box-icon bg-info"><i class="fas fa-boxes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Items</span>
                        <span class="info-box-number"><?php echo count($rows) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box bg-light shadow-sm">
                    <span class="info-box-icon bg-success"><i class="fas fa-layer-group"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Units in Stock</span>
                        <span class="info-box-number"><?php echo number_format($t_cur_stock,2) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box bg-light shadow-sm">
                    <span class="info-box-icon bg-warning"><i class="fas fa-coins"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Inventory Value</span>
                        <span class="info-box-number"><?php echo number_format($t_value,2) ?></span>
                    </div>
                </div>
            </div>
        </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm" id="stockTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th><th>Item Name</th><th>Category</th>
                            <th class="text-right">Purchased Qty</th>
                            <th class="text-right">Sold Qty</th>
                            <th class="text-right">Current Stock</th>
                            <th class="text-right">Avg Cost</th>
                            <th class="text-right">Selling Price</th>
                            <th class="text-right">Stock Value</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($rows)): ?><tr><td colspan="10" class="text-center text-muted py-4">No items found.</td></tr><?php endif; ?>
                    <?php $i=1; foreach($rows as $r): ?>
                        <tr class="<?php echo $r['cur_stock']<=0?'table-danger':($r['cur_stock']<10?'table-warning':'') ?>">
                            <td><?php echo $i++ ?></td>
                            <td class="font-weight-bold"><?php echo htmlspecialchars($r['name']) ?></td>
                            <td><?php echo htmlspecialchars($r['category']??'N/A') ?></td>
                            <td class="text-right"><?php echo number_format($r['pur_qty'],2) ?></td>
                            <td class="text-right"><?php echo number_format($r['sal_qty'],2) ?></td>
                            <td class="text-right font-weight-bold <?php echo $r['cur_stock']<=0?'text-danger':($r['cur_stock']<10?'text-warning':'text-success') ?>"><?php echo number_format($r['cur_stock'],2) ?></td>
                            <td class="text-right"><?php echo number_format($r['avg_cost'],2) ?></td>
                            <td class="text-right"><?php echo number_format($r['sell_price'],2) ?></td>
                            <td class="text-right font-weight-bold"><?php echo number_format($r['stock_value'],2) ?></td>
                            <td class="text-center"><?php
                                if($r['cur_stock']<=0) echo '<span class="badge badge-danger">Out of Stock</span>';
                                elseif($r['cur_stock']<10) echo '<span class="badge badge-warning">Low Stock</span>';
                                else echo '<span class="badge badge-success">In Stock</span>';
                            ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark font-weight-bold">
                            <th colspan="5" class="text-right">TOTALS</th>
                            <th class="text-right"><?php echo number_format($t_cur_stock,2) ?></th>
                            <th colspan="2"></th>
                            <th class="text-right"><?php echo number_format($t_value,2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<script>$(function(){
    if($.fn.DataTable) $('#stockTable').DataTable({paging:false,info:false,searching:false});
    $('.print-btn').click(function(){
        start_loader();
        var nw=window.open('','_blank','width=1200,height=900');
        nw.document.write('<html>'+$('head').clone()[0].outerHTML+'<body>'+$('#print-data').html()+'</body></html>');
        nw.document.close();
        setTimeout(function(){nw.print();setTimeout(function(){nw.close();end_loader();},200);},500);
    });
});</script>
