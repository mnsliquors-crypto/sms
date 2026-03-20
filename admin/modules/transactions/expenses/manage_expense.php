<?php 
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT *, transaction_date as date_created, total_amount as amount from `transactions` where id = '{$_GET['id']}' AND type = 'expense' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
$user_name = isset($created_by) ? $conn->query("SELECT concat(firstname,' ',lastname) FROM users where id = '{$created_by}'")->fetch_array()[0] : $_settings->userdata('firstname').' '.$_settings->userdata('lastname');
$date_val = isset($date_created) ? date("Y-m-d", strtotime($date_created)) : date("Y-m-d");
?>
<div class="card card-outline card-info">
	<div class="card-header">
		<h3 class="card-title"><?php echo isset($id) ? "Update ": "Create New " ?> Expense</h3>
	</div>
	<div class="card-body">
		<form action="" id="expense-form">
			<input type="hidden" name ="id" value="<?php echo isset($id) ? $id : '' ?>">
            <input type="hidden" name ="user_id" value="<?php echo isset($created_by) ? $created_by : $_settings->userdata('id') ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label">Code</label>
                        <input type="text" id="expense_code_preview" class="form-control rounded-0" value="<?php echo isset($reference_code) ? $reference_code : "New" ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="date_created" class="control-label">Date</label>
                        <input type="date" name="date_created" id="date_created" class="form-control rounded-0" value="<?php echo $date_val ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                 <div class="col-md-6">
                     <div class="form-group">
                        <label class="control-label">User</label>
                        <input type="text" class="form-control rounded-0" value="<?php echo htmlspecialchars($user_name) ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="account_id" class="control-label">Account</label>
                        <select name="account_id" id="account_id" class="custom-select select2" required>
                            <option <?php echo !isset($account_id) ? 'selected' : '' ?> disabled></option>
                            <?php 
                            $account = $conn->query("SELECT * FROM `account_list` where status = 1 order by `name` asc");
                            while($row=$account->fetch_assoc()):
                            ?>
                            <option value="<?php echo $row['id'] ?>" <?php echo isset($account_id) && $account_id == $row['id'] ? "selected" : "" ?> ><?php echo $row['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="amount" class="control-label">Amount</label>
                        <input type="number" step="any" name="amount" id="amount" class="form-control rounded-0 text-right" value="<?php echo isset($amount) ? $amount : 0 ?>" required>
                    </div>
                </div>
                <!-- Status field hidden or kept? User list didn't mention it in form but it's needed for logic. I'll keep it but maybe default to Paid or move it. User said "in expense entry form code, date, user, account, amount, remarks, type". Status is missing. I'll Put Status at the end. Or assume Paid? But logic needs it. I'll keep it. -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="status" class="control-label">Payment Status</label>
                        <select name="status" id="status" class="custom-select rounded-0">
                            <option value="0" <?php echo isset($status) && $status == 0 ? "selected" : "" ?>>Pending / Unpaid</option>
                            <option value="1" <?php echo isset($status) && $status == 1 ? "selected" : "" ?>>Paid</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
				<label for="remarks" class="control-label">Remarks</label>
                <textarea name="remarks" id="remarks" rows="3" class="form-control rounded-0"><?php echo isset($remarks) ? $remarks : '' ?></textarea>
			</div>

			</div>

		</form>
	</div>
	<div class="card-footer">
		<button class="btn btn-flat btn-primary" type="submit" form="expense-form">Save</button>
		<a class="btn btn-flat btn-default" href="?page=transactions/expenses">Cancel</a>
	</div>
</div>
<script>
	$(document).ready(function(){
        function get_next_code(){
            var date = $('#date_created').val();
            if(date == '' || $('[name="id"]').val() != '') return false;
            $.ajax({
                url:_base_url_+"classes/Master.php?f=get_next_ref_code",
                method:'POST',
                data:{type:'expense', date:date},
                dataType:'json',
                error:err=>{
                    console.log(err)
                },
                success:resp=>{
                    if(resp.status == 'success'){
                        $('#expense_code_preview').val(resp.code);
                    }
                }
            })
        }

        if($('[name="id"]').val() == ''){
            get_next_code();
        }

        $('#date_created').change(function(){
            get_next_code();
        })

		$('#expense-form').submit(function(e){
			e.preventDefault();
            var _this = $(this)
			 $('.err-msg').remove();
			start_loader();
			$.ajax({
				url:_base_url_+"classes/Master.php?f=save_expense",
				data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
				error:err=>{
					console.log(err)
					alert_toast("An error occured",'error');
					end_loader();
				},
				success:function(resp){
					if(typeof resp =='object' && resp.status == 'success'){
						location.href = "./?page=expenses";
					}else if(resp.status == 'failed' && !!resp.msg){
                        var el = $('<div>')
                            el.addClass("alert alert-danger err-msg").text(resp.msg)
                            _this.prepend(el)
                            el.show('slow')
                            $("html, body").animate({ scrollTop: _this.closest('.card').offset().top }, "fast");
                            end_loader()
                    }else{
						alert_toast("An error occured",'error');
						end_loader();
                        console.log(resp)
					}
				}
			})
		})
	})
</script>
