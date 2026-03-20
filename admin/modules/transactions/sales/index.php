<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">List of Sales</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/ajax/export_data.php?type=sales" class="btn btn-flat btn-success"><span class="fas fa-download"></span>  Export</a>
            <a href="<?php echo base_url ?>admin/import/import_index.php?type=sales" class="btn btn-flat btn-info"><span class="fas fa-upload"></span>  Import</a>
			<a href="<?php echo base_url ?>admin/?page=transactions/sales/manage_sale" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Create New</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<table class="table table-bordered table-stripped">
                    <colgroup>
                        <col width="3%">
                        <col width="10%">
                        <col width="10%">
                        <col width="12%">
                        <col width="10%">
                        <col width="8%">
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                        <col width="9%">
                        <col width="8%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Code</th>
                            <th>Customer</th>
                            <th class="text-right">Sub-Total</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Grand Total</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Outstanding</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="sales-list-body">
                    </tbody>
                </table>
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
                [1, 'desc'], [2, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: {
                url: _base_url_ + "admin/modules/transactions/sales/dt_sales.php",
                type: "POST"
            },
            columns: [
                { data: "index" },
                { data: "date" },
                { data: "code" },
                { data: "customer" },
                { data: "sub_total", className: "text-right" },
                { data: "discount", className: "text-right" },
                { data: "total", className: "text-right" },
                { data: "paid", className: "text-right" },
                { data: "outstanding", className: "text-right" },
                { data: "status", className: "text-center" },
                { data: "action", className: "text-center" }
            ],
            drawCallback: function() {
                $('.record_payment').unbind('click').click(function(){
                    var id = $(this).attr('data-id');
                    var party_id = $(this).attr('data-party-id');
                    location.href = _base_url_ + "admin/?page=transactions/payments/manage_payment&type=1&party_id=" + party_id + "&transaction_id=" + id;
                })
                $('.delete_data').unbind('click').click(function(){
                    _conf("Are you sure to delete this Sales Record permanently?","delete_sale",[$(this).attr('data-id')])
                })
            }
        });
	})
	function delete_sale($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_sale",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			error:err=>{
				alert_toast("An error occured.",'error');
				end_loader();
			},
			success:function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    location.reload();
                }else{
                    var msg = resp.msg || resp.error || "An error occured.";
                    alert_toast(msg,'error');
                    end_loader();
                }
			}
		})
	}
</script>