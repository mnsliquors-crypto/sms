<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">List of Purchase Bills</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/ajax/export_data.php?type=purchases" class="btn btn-flat btn-success"><span class="fas fa-download"></span>  Export</a>
            <a href="<?php echo base_url ?>admin/import/import_index.php?type=purchases" class="btn btn-flat btn-info"><span class="fas fa-upload"></span>  Import</a>
			<a href="<?php echo base_url ?>admin/?page=transactions/purchases/manage_purchase" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Create New</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<table class="table table-bordered table-stripped">
                    <colgroup>
                        <col width="5%">
                        <col width="10%">
                        <col width="10%">
                        <col width="15%">
                        <col width="10%">
                        <col width="5%">
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                        <col width="5%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Code</th>
                            <th>Vendor</th>
                            <th class="text-right">Sub Total</th>
                            <th class="text-right">VAT</th>
                            <th class="text-right">Grand Total</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Outstanding</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="purchases-list-body">
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
                { orderable: false, targets: [10] }
            ],
            order: [[0, 'asc']],
            processing: true,
            serverSide: true,
            ajax: {
                url: _base_url_ + "admin/modules/transactions/purchases/dt_purchases.php",
                type: "POST"
            },
            columns: [
                { data: "index" },
                { data: "date" },
                { data: "code" },
                { data: "vendor" },
                { data: "sub_total", className: "text-right" },
                { data: "vat", className: "text-right" },
                { data: "total", className: "text-right" },
                { data: "paid", className: "text-right" },
                { data: "outstanding", className: "text-right" },
                { data: "status", className: "text-center" },
                { data: "action", className: "text-center" }
            ],
            drawCallback: function() {
                $('.view_data').unbind('click').click(function(){
                    location.href = "<?php echo base_url ?>admin/?page=transactions/purchases/view_purchase&id="+$(this).attr('data-id');
                })
                $('.record_payment').unbind('click').click(function(){
                    var id = $(this).attr('data-id');
                    var party_id = $(this).attr('data-party-id');
                    location.href = _base_url_ + "admin/?page=transactions/payments/manage_payment&type=2&party_id=" + party_id + "&transaction_id=" + id;
                })
                $('.delete_data').unbind('click').click(function(){
                    _conf("Are you sure to delete this Purchase permanently?","delete_po",[$(this).attr('data-id')])
                })
            }
        });
	})
	function delete_po($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_po",
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
                                    var msg = resp.msg || resp.error || "An error occured.";
                                    alert_toast(msg,'error');
                                    end_loader();
                                }
			}
		})
	}
</script>
