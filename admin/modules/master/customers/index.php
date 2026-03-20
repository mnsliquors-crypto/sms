<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">List of Customers</h3>
		<div class="card-tools">
			<a href="<?php echo base_url ?>admin/ajax/export_data.php?type=customers" class="btn btn-flat btn-success"><span class="fas fa-download"></span>  Export</a>
			<a href="<?php echo base_url ?>admin/?page=system/import&type=customers" class="btn btn-flat btn-info"><span class="fas fa-upload"></span>  Import</a>
			<a href="<?php echo base_url ?>admin/?page=master/customers/manage" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Create New</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-bordered table-striped">
				<colgroup>
					<col width="5%">
					<col width="20%">
					<col width="20%">
					<col width="15%">
					<col width="15%">
					<col width="10%">
					<col width="15%">
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Customer Name</th>
						<th>Contact</th>
						<th>Total Sales</th>
						<th>Pending Amount</th>
						<th>Status</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
						$qry = $conn->query("SELECT c.id, c.display_name as name, c.contact, c.email, c.status,
                                            (SELECT COALESCE(SUM(total_amount), 0) FROM transactions t WHERE t.entity_id = c.id AND t.type = 'sale') as total_sales,
                                            (SELECT COALESCE(SUM(tp.total_amount), 0) FROM transactions tp INNER JOIN transactions ts ON tp.parent_id = ts.id WHERE ts.entity_id = c.id AND tp.type = 'payment' AND ts.type = 'sale') as total_paid
                                            from `entity_list` c WHERE c.entity_type = 'Customer' order by c.display_name asc ");
						while($row = $qry->fetch_assoc()):
                            $pending = $row['total_sales'] - $row['total_paid'];
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td><?php echo $row['name'] ?></td>
							<td>
                                <p class="m-0">
                                    <small><?php echo $row['contact'] ?></small><br>
                                    <small><?php echo $row['email'] ?></small>
                                </p>
                            </td>
                            <td class="text-right"><?php echo number_format($row['total_sales'], 2) ?></td>
                            <td class="text-right"><?php echo number_format($pending, 2) ?></td>
							<td class="text-center">
                                <?php if($row['status'] == 1): ?>
                                    <span class="badge badge-success rounded-pill">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger rounded-pill">Inactive</span>
                                <?php endif; ?>
                            </td>
							<td align="center">
								 <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
				                  		Action
				                    <span class="sr-only">Toggle Dropdown</span>
				                  </button>
				                  <div class="dropdown-menu" role="menu">
				                    <a class="dropdown-item" href="<?php echo base_url ?>admin/?page=master/customers/view&id=<?php echo $row['id'] ?>"><span class="fa fa-eye text-dark"></span> View</a>
				                    <div class="dropdown-divider"></div>
				                    <a class="dropdown-item" href="<?php echo base_url ?>admin/?page=master/customers/manage&id=<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
				                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item accept_payment" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-money-bill text-success"></span> Accept Payment</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo base_url ?>admin/?page=reports/customer_outstanding&customer_id=<?php echo $row['id'] ?>"><span class="fa fa-file-invoice text-info"></span> Outstanding</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo base_url ?>admin/?page=reports/customer_statement&customer_id=<?php echo $row['id'] ?>"><span class="fa fa-file-invoice-dollar text-primary"></span> Statement</a>
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
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this Customer permanently?","delete_customer",[$(this).attr('data-id')])
		})
		$('.table td,.table th').addClass('py-1 px-2 align-middle')
		$('.table').dataTable();
        $('.accept_payment').click(function(){
			uni_modal("<i class='fa fa-money-bill'></i> Accept Payment","modules/master/customers/unpaid_sales.php?id="+$(this).attr('data-id'),"mid-large")
		})
	})
	function delete_customer($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_customer",
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
