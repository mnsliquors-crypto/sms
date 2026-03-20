<?php 
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d"); 
$to = isset($_GET['to']) ? $_GET['to'] : date("Y-m-d"); 
?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Purchase Report</h3>
	</div>
	<div class="card-body">
        <form action="" id="filter-form">
            <input type="hidden" name="page" value="reports/purchase">
            <div class="row align-items-end">
                <div class="form-group col-md-3">
                    <label for="from" class="control-label">Date From</label>
                    <input type="date" name="from" id="from" value="<?php echo $from ?>" class="form-control rounded-0">
                </div>
                <div class="form-group col-md-3">
                    <label for="to" class="control-label">Date To</label>
                    <input type="date" name="to" id="to" value="<?php echo $to ?>" class="form-control rounded-0">
                </div>
                <div class="form-group col-md-3">
                    <button class="btn btn-flat btn-primary"><span class="fa fa-filter"></span> Filter</button>
                    <button class="btn btn-flat btn-success print" type="button"><span class="fa fa-print"></span> Print</button>
                </div>
            </div>
        </form>
        <hr>
		<div class="container-fluid" id="print-data">
			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>#</th>
						<th>Date</th>
						<th>Code</th>
						<th>Vendor</th>
						<th>Payment Status</th>
						<th>Total Amount</th>
                        <th>Paid Amount</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
                    $total = 0;
                    $t_paid = 0;
						$qry = $conn->query("
                            SELECT t.id, t.transaction_date as date_created, t.reference_code as po_code, t.total_amount as amount, s.display_name as vendor,
                                   COALESCE((SELECT SUM(p.total_amount) FROM transactions p WHERE p.parent_id = t.id AND p.type = 'payment'), 0) as paid_amount
                            FROM `transactions` t 
                            LEFT JOIN entity_list s ON t.entity_id = s.id AND s.entity_type = 'Supplier'
                            WHERE t.type = 'purchase' AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}' 
                            ORDER BY t.transaction_date DESC
                        ");
						while($row = $qry->fetch_assoc()):
                            $row['payment_status'] = 0; // Default Unpaid
                            $balance = max(0, $row['amount'] - $row['paid_amount']);
                            if($balance <= 0) $row['payment_status'] = 1; // Paid
                            elseif($balance < $row['amount']) $row['payment_status'] = 2; // Partial
                            
                            $total += $row['amount'];
                            $t_paid += $row['paid_amount'];
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td><?php echo date("d-m-Y",strtotime($row['date_created'])) ?></td>
							<td><a href="<?php echo base_url ?>admin/?page=purchases/view_purchase&id=<?php echo $row['id'] ?>" target="_blank"><?php echo $row['po_code'] ?></a></td>
							<td><?php echo $row['vendor'] ?></td>
                             <td class="text-center">
                                <?php if($row['payment_status'] == 1): ?>
                                    <span class="badge badge-success">Paid</span>
                                <?php elseif($row['payment_status'] == 2): ?>
                                    <span class="badge badge-warning">Partial</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Unpaid</span>
                                <?php endif; ?>
                            </td>
							<td class="text-right"><?php echo number_format($row['amount'],2) ?></td>
                             <td class="text-right"><?php echo number_format($row['paid_amount'],2) ?></td>
						</tr>
					<?php endwhile; ?>
                    <?php if($qry->num_rows <= 0): ?>
                        <tr>
                            <td class="text-center" colspan="7">No Data...</td>
                        </tr>
                    <?php endif; ?>
				</tbody>
                <tfoot>
                    <tr>
                        <th class="text-right" colspan="5">Total</th>
                        <th class="text-right"><?php echo number_format($total,2) ?></th>
                        <th class="text-right"><?php echo number_format($t_paid,2) ?></th>
                    </tr>
                </tfoot>
			</table>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('.print').click(function(){
            start_loader()
            var _h = $('head').clone()
            var _p = $('#print-data').clone()
            var _el = $('<div>')
            _h.find('title').text('Purchase Report - Print View')
            _el.append(_h)
            _el.append(_p)
            var nw = window.open("","_blank","width=1000,height=900,top=50,left=200")
            nw.document.write(_el.html())
            nw.document.close()
            setTimeout(() => {
                nw.print()
                setTimeout(() => {
                    nw.close()
                    end_loader()
                }, 200);
            }, 500);
        })
	})
</script>
