<?php 
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Stock Report</h3>
        <div class="card-tools">
            <button class="btn btn-flat btn-success print" type="button"><span class="fa fa-print"></span> Print</button>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid" id="print-data">
            <table class="table table-bordered table-striped">
                <colgroup>
                    <col width="5%">
                    <col width="25%">
                    <col width="20%">
                    <col width="20%">
                    <col width="15%">
                    <col width="15%">
                </colgroup>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Vendor</th>
                        <th>Available Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                     $qry = $conn->query("
                         SELECT i.*, c.name as category, s.display_name as vendor,
                        (SELECT SUM(CASE 
                                    WHEN t.type IN ('purchase', 'opening_stock') THEN ti.quantity 
                                    WHEN t.type = 'sale' THEN -ti.quantity 
                                    WHEN t.type = 'adjustment' THEN ti.quantity 
                                    WHEN t.type = 'return' THEN (CASE WHEN t.entity_id IN (SELECT id FROM entity_list WHERE entity_type='Supplier') THEN -ti.quantity ELSE ti.quantity END)
                                    ELSE 0 END) 
                         FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id 
                         WHERE ti.item_id = i.id) as available 
                        FROM `item_list` i 
                        LEFT JOIN category_list c ON i.category_id = c.id 
                        LEFT JOIN entity_list s ON i.vendor_id = s.id AND s.entity_type = 'Supplier'
                    ");
                    while($row = $qry->fetch_assoc()){
                        $row['available'] = (isset($row['available']) ? $row['available'] : 0);
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($row['name']) ?></td>
                        <td><?php echo htmlspecialchars($row['category'] ?? 'N/A') ?></td>
                         <td><?php echo htmlspecialchars($row['vendor'] ?? 'N/A') ?></td>
                        <td class="text-right"><?php echo number_format($row['available'], 2) ?></td>
                        <td class="text-center">
                            <?php if($row['status'] == 1): ?>
                                <span class="badge badge-success rounded-pill">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger rounded-pill">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php } ?>
                    <?php if($qry->num_rows <= 0): ?>
                    <tr>
                        <td class="text-center" colspan="6">No Data...</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    $(document).ready(function(){
        $('.print').click(function(){
            start_loader();
            var _h = $('head').clone();
            var _p = $('#print-data').clone();
            var _el = $('<div>');
            _h.find('title').text('Stock Report - Print View');
            _el.append(_h);
            _el.append(_p);
            var nw = window.open("", "_blank", "width=1000,height=900,top=50,left=200");
            nw.document.write(_el.html());
            nw.document.close();
            setTimeout(function(){
                nw.print();
                setTimeout(function(){
                    nw.close();
                }, 200);
            }, 500);
        });
    });
</script>
