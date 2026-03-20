<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Cash Counting (Closing) Records</h3>
        <div class="card-tools">
			<a href="<?php echo base_url ?>admin/?page=denominations/manage_denomination" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  New Count</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<table class="table table-bordered table-stripped" id="denomination-table">
                    <colgroup>
                        <col width="4%">
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                        <col width="8%">
                        <col width="8%">
                        <col width="30%">
                        <col width="10%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Code</th>
                            <th>User</th>
                            <th>Total Counted</th>
                            <th>Diff</th>
                            <th>Status</th>
                            <th>Account Balances</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $qry = $conn->query("SELECT d.*, u.firstname, u.lastname FROM `cash_denominations` d inner join users u on d.user_id = u.id order by d.`date` desc, d.`date_created` desc");
                        while($row = $qry->fetch_assoc()):
                            // Fetch daily account balances for this date
                            $rec_date = $row['date'] ?: date('Y-m-d', strtotime($row['date_created']));
                            $dal_qry = $conn->query("SELECT account_name, balance FROM daily_account_balances WHERE balance_date = '{$rec_date}' ORDER BY account_name ASC");
                            $daily_balances = [];
                            if($dal_qry && $dal_qry->num_rows > 0){
                                while($dr = $dal_qry->fetch_assoc()) $daily_balances[] = $dr;
                            }
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td><?php echo date("d-m-Y", strtotime($rec_date)) ?></td>
                                <td><?php echo $row['cd_code'] ?></td>
                                <td><?php echo $row['firstname'].' '.$row['lastname'] ?></td>
                                <td class="text-right"><?php echo number_format($row['total_amount'],2) ?></td>
                                <td class="text-right <?php echo $row['difference'] < 0 ? 'text-danger' : 'text-success' ?>">
                                    <?php echo number_format($row['difference'],2) ?>
                                </td>
                                <td class="text-center">
                                    <?php if(intval($row['reconciliation_status']) === 1): ?>
                                    <span class="badge badge-success"><i class="fas fa-lock mr-1"></i>Finalized</span>
                                    <?php else: ?>
                                    <span class="badge badge-warning">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!empty($daily_balances)): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach($daily_balances as $db): 
                                            $text_c = 'text-primary';
                                            if(stripos($db['account_name'],'Cash') !== false) $text_c = 'text-success';
                                            if(stripos($db['account_name'],'QR') !== false) $text_c = 'text-info';
                                        ?>
                                        <span class="badge badge-light border mr-1 mb-1 px-2 py-1 <?php echo $text_c ?>" title="<?php echo htmlspecialchars($db['account_name']) ?>">
                                            <small class="text-muted"><?php echo htmlspecialchars($db['account_name']) ?>:</small>
                                            <strong><?php echo number_format($db['balance'], 2) ?></strong>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td align="center">
                                    <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                            Action
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <div class="dropdown-menu" role="menu">
                                        <a class="dropdown-item" href="./?page=denominations/view_denomination&id=<?php echo $row['id'] ?>"><span class="fa fa-eye text-primary"></span> View</a>
                                        <?php if(intval($row['reconciliation_status']) !== 1): ?>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="./?page=denominations/manage_denomination&id=<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
                                        <?php endif; ?>
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
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this cash denomination record permanently?","delete_denomination",[$(this).attr('data-id')])
		})
		$('.table td,.table th').addClass('py-1 px-2 align-middle')
		$('#denomination-table').dataTable();
	})
	function delete_denomination($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_denomination",
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
