<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">List of Stocks</h3>
        <div class="card-tools">
			<a href="<?php echo base_url ?>admin/ajax/export_data.php?type=stocks" class="btn btn-flat btn-success"><span class="fas fa-download"></span>  Export</a>
			<a href="<?php echo base_url ?>admin/import/import_index.php?type=stocks" class="btn btn-flat btn-info"><span class="fas fa-upload"></span>  Import</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-bordered table-stripped" id="stocks-table">
                    <colgroup>
                        <col width="4%">
                        <col width="15%">
                        <col width="12%">
                        <col width="12%">
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                        <col width="12%">
                        <col width="12%">
                    </colgroup>
                    <thead>
                        <tr class="bg-navy">
                            <th class="text-center">#</th>
                            <th>Item Name</th>
                            <th>Vendor</th>
                            <th>Avg. Cost</th>
                            <th>Selling Price</th>
                            <th>Margin <small>per unit</small></th>
                            <th class="text-right">In Stock</th>
                            <th class="text-right">Stock Value</th>
                            <th class="text-right">Margin %</th>
                            <th class="text-right">Potential Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        // Use LEFT JOIN to include items without vendors
                        $qry = $conn->query("SELECT i.*,COALESCE(s.display_name,'N/A') as vendor FROM `item_list` i LEFT JOIN entity_list s on i.vendor_id = s.id order by i.`name` desc");
                        while($row = $qry->fetch_assoc()):
                            $available = floatval($row['quantity']);
                            
                            $avg_cost = floatval($row['average_cost'] ?? $row['cost']);
                            $selling_price = floatval($row['selling_price'] ?? 0);
                            $margin = $selling_price - $avg_cost;
                            $margin_pct = ($avg_cost > 0) ? (($margin / $avg_cost) * 100) : 0;
                            $stock_value = $available * $avg_cost;
                            $potential_profit = $margin * $available;
                        ?>
                            <tr>
                                <td class="text-center align-middle"><?php echo $i++; ?></td>
                                <td class="align-middle"><?php echo $row['name'] ?></td>
                                <td class="align-middle"><small><?php echo htmlspecialchars($row['vendor']) ?></small></td>
                                <td class="text-right align-middle"><strong><?php echo number_format($avg_cost, 2) ?></strong></td>
                                <td class="text-right align-middle"><?php echo number_format($selling_price, 2) ?></td>
                                <td class="text-right align-middle <?php echo $margin >= 0 ? 'text-success' : 'text-danger' ?>"><strong><?php echo number_format($margin, 2) ?></strong></td>
                                <td class="text-right align-middle"><?php echo number_format($available) ?></td>
                                <td class="text-right align-middle"><?php echo number_format($stock_value, 2) ?></td>
                                <td class="text-right align-middle <?php echo $margin_pct >= 0 ? 'text-success' : 'text-danger' ?>"><strong><?php echo number_format($margin_pct, 1) ?>%</strong></td>
                                <td class="text-right align-middle <?php echo $potential_profit >= 0 ? 'text-success' : 'text-danger' ?>"><strong><?php echo number_format($potential_profit, 2) ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
		</div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('.table td,.table th').addClass('py-1 px-2 align-middle')
		$('#stocks-table').dataTable({
			order: [[1, 'asc']],
			pageLength: 25
		});
	})
</script>