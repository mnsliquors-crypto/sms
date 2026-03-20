<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
		<div class="card-header">
			<h3 class="card-title">List of Expenses</h3>
			<div class="card-tools">
				<a href="<?php echo base_url ?>admin/ajax/export_data.php?type=expenses" class="btn btn-flat btn-success"><span class="fas fa-download"></span>  Export</a>
	
				<a href="<?php echo base_url ?>admin/?page=transactions/expenses/manage_expense" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Create New</a>
			</div>
		</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-bordered table-striped">
				<colgroup>
					<col width="5%">
					<col width="10%">
					<col width="10%">
					<col width="10%">
					<col width="15%">
					<col width="10%">
					<col width="10%">
                    <col width="15%">
                    <col width="10%">
					<col width="10%">
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Date</th>
						<th>Code</th>
						<th>User</th>
						<th>Account</th>
                        <th>Amount</th>
                        <th>Remarks</th>
						<th>Status</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody id="expenses-list-body">
				</tbody>
			</table>
		</div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('.table td,.table th').addClass('py-1 px-2 align-middle')
		$('.table').DataTable({
            columnDefs: [
                { orderable: false, targets: [8] }
            ],
            order: [[0, 'asc']],
            processing: true,
            serverSide: true,
            ajax: {
                url: _base_url_ + "admin/modules/transactions/expenses/dt_expenses.php",
                type: "POST"
            },
            columns: [
                { data: "index" },
                { data: "date" },
                { data: "code" },
                { data: "user" },
                { data: "account" },
                { data: "amount", className: "text-right" },
                { data: "remarks" },
                { data: "status", className: "text-center" },
                { data: "action", className: "text-center" }
            ],
            drawCallback: function() {
                $('.pay_data').unbind('click').click(function(){
                    _conf("Are you sure to mark this expense as paid?","pay_expense",[$(this).attr('data-id')])
                })
                $('.delete_data').unbind('click').click(function(){
                    _conf("Are you sure to delete this Expense permanently?","delete_expense",[$(this).attr('data-id')])
                })
                $('.view_data').unbind('click').click(function(){
                    location.href = "<?php echo base_url ?>admin/?page=transactions/expenses/view_expense&id="+$(this).attr('data-id');
                })
            }
        });
	})
    function pay_expense($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=pay_expense",
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
	function delete_expense($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_expense",
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
