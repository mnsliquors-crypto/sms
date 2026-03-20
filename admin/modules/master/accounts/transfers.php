<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Fund Transfers</h3>
		<div class="card-tools">
			<button class="btn btn-flat btn-primary" type="button" id="create_new"><span class="fas fa-plus"></span>  Create New</button>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-bordered table-stripped" id="transfer-table">
				<colgroup>
					<col width="5%">
					<col width="15%">
					<col width="15%">
					<col width="20%">
					<col width="20%">
					<col width="15%">
					<col width="10%">
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Date</th>
						<th>Code</th>
						<th>From Account</th>
						<th>To Account</th>
						<th>Amount</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
						$qry = $conn->query("SELECT t.* FROM `transactions` t WHERE t.type = 'transfer' ORDER BY t.transaction_date DESC");
						while($row = $qry->fetch_assoc()):
                            $from_acc = $conn->query("SELECT a.name FROM transaction_list tl JOIN account_list a ON tl.account_id = a.id WHERE tl.ref_table = 'transactions' AND tl.ref_id = '{$row['id']}' AND tl.type = 2")->fetch_assoc()['name'] ?? 'N/A';
                            $to_acc = $conn->query("SELECT a.name FROM transaction_list tl JOIN account_list a ON tl.account_id = a.id WHERE tl.ref_table = 'transactions' AND tl.ref_id = '{$row['id']}' AND tl.type = 1")->fetch_assoc()['name'] ?? 'N/A';
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td><?php echo date("d-m-Y",strtotime($row['transaction_date'])) ?></td>
							<td><?php echo $row['reference_code'] ?></td>
							<td><?php echo $from_acc ?></td>
							<td><?php echo $to_acc ?></td>
							<td class="text-right"><?php echo number_format($row['total_amount'],2) ?></td>
							<td align="center">
								 <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
				                  		Action
				                    <span class="sr-only">Toggle Dropdown</span>
				                  </button>
				                  <div class="dropdown-menu" role="menu">
				                    <a class="dropdown-item" href="./?page=master/accounts/view_transfer&id=<?php echo $row['id'] ?>"><span class="fa fa-eye text-dark"></span> View</a>
				                    <div class="dropdown-divider"></div>
				                    <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
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
		$('#create_new').click(function(){
			uni_modal("<i class='fa fa-plus'></i> New Fund Transfer","modules/master/accounts/manage_transfer.php","mid-large")
		})
		$('.edit_data').click(function(){
			uni_modal("<i class='fa fa-edit'></i> Update Transfer","modules/master/accounts/manage_transfer.php?id="+$(this).attr('data-id'),"mid-large")
		})
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this transfer record permanently?","delete_transfer",[$(this).attr('data-id')])
		})
		$('.table td,.table th').addClass('py-1 px-2 align-middle')
		$('.table').dataTable({
            columnDefs: [
                { orderable: false, targets: [6] }
            ],
            order: [0, 'asc']
        });
	})
	function delete_transfer($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_transfer",
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
