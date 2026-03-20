<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">List of Item</h3>
		<div class="card-tools">
			<a href="<?php echo base_url ?>admin/?page=master/items/manage" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Create New</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-hovered table-striped">
				<colgroup>
					<col width="5%">
					<col width="15%">
					<col width="5%">
					<col width="10%">
					<col width="10%">
					<col width="10%">
					<col width="10%">
					<col width="10%">
					<col width="10%">
					<col width="5%">
					<col width="10%">
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Name</th>
						<th>Unit</th>
						<th>Category</th>
						<th>Cost Price</th>
						<th>Last Purchase Price</th>
						<th>Remaining Qty</th>
						<th>Last Selling Price</th>
						<th>Restock Level</th>
						<th>Status</th>
						<th>Action</th>
					</tr>
				</thead>
						<tbody id="item-list-body">
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('.table td,.table th').addClass('py-1 px-2 align-middle')
		$('.table').DataTable({
			columnDefs: [
				{ orderable: false, targets: [0, 10] }
			],
			order: [
				[1, 'asc']
			],
			processing: true,
			serverSide: true,
			ajax: {
				url: _base_url_ + "admin/modules/master/items/dt_items.php",
				type: "POST"
			},
			columns: [
				{ data: "index" },
				{ data: "name" },
				{ data: "unit" },
				{ data: "category" },
				{ data: "cost", className: "text-right" },
				{ data: "last_p", className: "text-right" },
				{ data: "quantity", className: "text-right" },
				{ data: "last_s", className: "text-right" },
				{ data: "reorder", className: "text-right" },
				{ data: "status", className: "text-center" },
				{ data: "action", className: "text-center" }
			],
            drawCallback: function() {
                $('.delete_data').unbind('click').click(function(){
                    _conf("Are you sure to delete this Item permanently?","delete_item",[$(this).attr('data-id')])
                })
            }
		});
	})
	function delete_item($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_item",
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