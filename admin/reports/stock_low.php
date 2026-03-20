<?php
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$and_search = $search ? " AND i.name LIKE '%{$search}%'" : '';

$qry = $conn->query("
    SELECT i.id, i.name,
           COALESCE((SELECT SUM(CASE 
                                WHEN t.type IN ('purchase', 'opening_stock', 'adjustment') THEN ti.quantity 
                                WHEN t.type = 'sale' THEN -ti.quantity 
                                WHEN t.type = 'return' THEN (CASE WHEN EXISTS(SELECT 1 FROM entity_list WHERE id = t.entity_id AND entity_type = 'Supplier') THEN -ti.quantity ELSE ti.quantity END)
                                ELSE 0 END) 
                      FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id 
                      WHERE ti.item_id=i.id),0) as available
    FROM item_list i
    WHERE i.status=1 {$and_search}
    HAVING available < 10
    ORDER BY available ASC
");
$rows=[];
while($r=$qry->fetch_assoc()) $rows[]=$r;
?>
<div class="card card-outline card-danger shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i>Low Stock Report</h3>
        <div class="card-tools">
            <button class="btn btn-sm btn-info print-btn"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET">
            <input type="hidden" name="page" value="reports/stock_low">
            <div class="row align-items-end">
                <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Search Item</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search) ?>" class="form-control" placeholder="Search item…">
                        <div class="input-group-append"><button class="btn btn-primary"><i class="fas fa-search"></i></button></div>
                    </div>
                </div>
                <div class="form-group col-md-2">
                    <a href="<?php echo base_url ?>admin/?page=reports/stock_low" class="btn btn-sm btn-secondary btn-block"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        <div class="alert alert-warning"><i class="fas fa-info-circle"></i> Showing items with stock below <strong>10 units</strong>. Total low-stock items: <strong><?php echo count($rows) ?></strong></div>
        <div id="print-data">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark">
                        <tr><th>#</th><th>Item Name</th><th class="text-right">Available Stock</th><th class="text-center">Status</th></tr>
                    </thead>
                    <tbody>
                    <?php if(empty($rows)): ?>
                        <tr><td colspan="4" class="text-center text-success py-5"><i class="fas fa-check-circle fa-2x mb-2"></i><br>All items are well stocked!</td></tr>
                    <?php endif; ?>
                    <?php $i=1; foreach($rows as $r): ?>
                        <tr>
                            <td><?php echo $i++ ?></td>
                            <td class="font-weight-bold"><?php echo htmlspecialchars($r['name']) ?></td>
                            <td class="text-right">
                                <div class="progress" style="height:18px;">
                                    <div class="progress-bar <?php echo $r['available']<=0?'bg-danger':'bg-warning' ?>" style="width:<?php echo min(100,max(1,$r['available']*10)) ?>%">
                                        <?php echo number_format($r['available'],2) ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if($r['available']<=0): ?>
                                    <span class="badge badge-danger badge-pill">Out of Stock</span>
                                <?php elseif($r['available']<=3): ?>
                                    <span class="badge badge-danger badge-pill">Critical Low</span>
                                <?php else: ?>
                                    <span class="badge badge-warning badge-pill">Low Stock</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>$(function(){
    $('.print-btn').click(function(){
        start_loader();
        var nw=window.open('','_blank','width=900,height=700');
        nw.document.write('<html>'+$('head').clone()[0].outerHTML+'<body>'+$('#print-data').html()+'</body></html>');
        nw.document.close();
        setTimeout(function(){nw.print();setTimeout(function(){nw.close();end_loader();},200);},500);
    });
});</script>
