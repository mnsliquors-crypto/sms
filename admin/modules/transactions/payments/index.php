<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">List of Payments</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/ajax/export_data.php?type=payments" class="btn btn-flat btn-success"><span class="fas fa-download"></span>  Export</a>
            <a href="<?php echo base_url ?>admin/import/import_index.php?type=payments" class="btn btn-flat btn-info"><span class="fas fa-upload"></span>  Import</a>
			<a href="<?php echo base_url ?>admin/?page=transactions/payments/manage_payment" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Record Payment</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<table class="table table-bordered table-stripped">
                    <colgroup>
                        <col width="5%">
                        <col width="15%">
                        <col width="15%">
                        <col width="25%">
                        <col width="15%">
                        <col width="15%">
                        <col width="10%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $i = 1;
                        $qry = $conn->query("SELECT 
                                                t.reference_code as payment_code, 
                                                MAX(t.id) as id, 
                                                MAX(t.transaction_date) as date_created, 
                                                MAX(CASE WHEN b.type = 'sale' THEN 1 ELSE 2 END) as type, 
                                                SUM(t.total_amount) as amount, 
                                                MAX(e.display_name) as party_name,
                                                MAX(t.remarks) as remarks
                                            FROM `transactions` t 
                                            LEFT JOIN transactions b ON t.parent_id = b.id
                                            LEFT JOIN entity_list e ON t.entity_id = e.id AND (
                                                (b.type = 'sale' AND e.entity_type = 'Customer') OR
                                                (b.type = 'purchase' AND e.entity_type = 'Supplier')
                                            )
                                            WHERE t.type = 'payment'
                                            GROUP BY t.reference_code 
                                            ORDER BY MAX(t.transaction_date) DESC");
                        while($row = $qry->fetch_assoc()):
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td><?php echo date("d-m-Y",strtotime($row['date_created'])) ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['payment_code']) ?>
                                    <?php if(strpos($row['remarks'] ?? '', '[POS]') !== false): ?>
                                        <span class="badge badge-info shadow-sm" title="POS Entry">POS</span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo htmlspecialchars($row['party_name'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if($row['type'] == 1): ?>
                                        <span class="badge badge-success">Customer</span>
                                    <?php else: ?>
                                        <span class="badge badge-primary">Vendor</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right"><?php echo number_format($row['amount'],2) ?></td>
                                <td align="center">
                                    <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                             Action
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <div class="dropdown-menu" role="menu">
                                        <a class="dropdown-item" href="<?php echo base_url.'admin?page=transactions/payments/view_payment&id='.$row['id'] ?>"><span class="fa fa-eye text-dark"></span> View</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="<?php echo base_url.'admin?page=transactions/payments/manage_payment&id='.$row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
        $('.view_data').click(function(){
            uni_modal("Payment Details","payments/view_payment.php?id="+$(this).attr('data-id'))
        })

        
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this payment record? This will reverse the account balance update.","delete_payment",[$(this).attr('data-id')])
		})
		$('.table td,.table th').addClass('py-1 px-2 align-middle')
		$('.table').dataTable();
	})
	function delete_payment($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_payment",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			error:err=>{
				console.log(err)
				alert_toast("An error occured.",'error');
				end_loader();
			},
			success:function(resp){
				if(typeof resp== 'object' && resp.status == 'success'){
					location.reload();
				}else{
					alert_toast("An error occured.",'error');
					end_loader();
				}
			}
		})
	}
</script>
