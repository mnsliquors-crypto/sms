<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">List of Return</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/ajax/export_data.php?type=returns" class="btn btn-flat btn-success"><span class="fas fa-download"></span>  Export</a>
			<a href="<?php echo base_url ?>admin/?page=transactions/returns/manage_return" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Create New</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-bordered table-stripped">
                    <colgroup>
                        <col width="5%">
                        <col width="15%">
                        <col width="15%">
                        <col width="25%">
                        <col width="10%">
                        <col width="15%">
                        <col width="15%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Return Code</th>
                            <th>Entity (Vendor/Customer)</th>
                            <th class="text-center">Items</th>
                            <th class="text-right">Total Amount</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $qry = $conn->query("SELECT t.id, t.transaction_date as date_created, t.reference_code as return_code, t.total_amount, 
                        e.display_name as entity_name, 
                        (SELECT COUNT(*) FROM transaction_items WHERE transaction_id = t.id) as items FROM `transactions` t 
                        LEFT JOIN entity_list e ON t.entity_id = e.id AND (
                             (t.entity_type = 'vendor' AND e.entity_type = 'Supplier') OR
                             (t.entity_type = 'customer' AND e.entity_type = 'Customer')
                        )
                        WHERE t.type='return' ORDER BY t.`transaction_date` DESC");
                        while($row = $qry->fetch_assoc()):
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td><?php echo date("d-m-Y",strtotime($row['date_created'])) ?></td>
                                <td><?php echo $row['return_code'] ?></td>
                                <td><?php echo $row['entity_name'] ?></td>
                                <td class="text-center"><?php echo number_format($row['items']) ?></td>
                                <td class="text-right"><b><?php echo number_format($row['total_amount'], 2) ?></b></td>
                                <td align="center">
                                    <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                             Action
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <div class="dropdown-menu" role="menu">
                                        <a class="dropdown-item" href="<?php echo base_url ?>admin/?page=transactions/returns/view_return&id=<?php echo $row['id'] ?>"><span class="fa fa-eye text-dark"></span> View</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="<?php echo base_url ?>admin/?page=transactions/returns/manage_return&id=<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
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
</div>
<script>
	$(document).ready(function(){
		$('.view_data').click(function(){
			uni_modal("Return Details","return/view_return.php?id="+$(this).attr('data-id'),"mid-large")
		})
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this Return Record permanently?","delete_return",[$(this).attr('data-id')])
		})
		$('.table td,.table th').addClass('py-1 px-2 align-middle')
		$('.table').dataTable();
	})
	function delete_return($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_return",
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