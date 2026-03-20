<?php
require_once __DIR__ . '/../../../../config.php';
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `transactions` where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_array() as $k => $v){
            if(!is_numeric($k)) $$k = $v;
        }
        
        // Fetch from/to accounts
        $from_acc = $conn->query("SELECT account_id FROM transaction_list WHERE ref_table = 'transactions' AND ref_id = '{$id}' AND type = 2")->fetch_assoc()['account_id'] ?? 0;
        $to_acc = $conn->query("SELECT account_id FROM transaction_list WHERE ref_table = 'transactions' AND ref_id = '{$id}' AND type = 1")->fetch_assoc()['account_id'] ?? 0;
    }
}
?>
<div class="container-fluid">
    <form action="" id="transfer-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label text-info">Transfer Code</label>
                    <input type="text" id="transfer_code_preview" class="form-control form-control-sm rounded-0" value="<?php echo isset($reference_code) ? $reference_code : 'New' ?>" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="date" class="control-label">Date</label>
                    <input type="text" name="date" id="date" class="form-control form-control-sm rounded-0 datepicker" value="<?php echo isset($transaction_date) ? date("d-m-Y", strtotime($transaction_date)) : date("d-m-Y") ?>" required autocomplete="off">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="from_account_id" class="control-label">From Account</label>
                    <select name="from_account_id" id="from_account_id" class="form-control form-control-sm rounded-0 select2" required>
                        <option value="" disabled <?php echo !isset($from_acc) ? 'selected' : '' ?>></option>
                        <?php 
                        $accounts = $conn->query("SELECT * FROM `account_list` where status = 1 order by `name` asc");
                        while($row = $accounts->fetch_assoc()):
                        ?>
                        <option value="<?php echo $row['id'] ?>" <?php echo isset($from_acc) && $from_acc == $row['id'] ? 'selected' : '' ?>><?php echo $row['name'] ?> [Bal: <?php echo number_format($row['balance'],2) ?>]</option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="to_account_id" class="control-label">To Account</label>
                    <select name="to_account_id" id="to_account_id" class="form-control form-control-sm rounded-0 select2" required>
                        <option value="" disabled <?php echo !isset($to_acc) ? 'selected' : '' ?>></option>
                        <?php 
                        $accounts = $conn->query("SELECT * FROM `account_list` where status = 1 order by `name` asc");
                        while($row = $accounts->fetch_assoc()):
                        ?>
                        <option value="<?php echo $row['id'] ?>" <?php echo isset($to_acc) && $to_acc == $row['id'] ? 'selected' : '' ?>><?php echo $row['name'] ?> [Bal: <?php echo number_format($row['balance'],2) ?>]</option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="amount" class="control-label">Amount</label>
                    <input type="number" step="any" name="amount" id="amount" class="form-control form-control-sm rounded-0 text-right" value="<?php echo isset($total_amount) ? $total_amount : '' ?>" required>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="remarks" class="control-label">Remarks</label>
            <textarea rows="3" name="remarks" id="remarks" class="form-control form-control-sm rounded-0" placeholder="Optional notes..."><?php echo isset($remarks) ? $remarks : '' ?></textarea>
        </div>
    </form>
</div>
<script>
    $(function(){
        function get_next_code(){
            var date = $('#date').val();
            if(date == '' || $('[name="id"]').val() != '') return false;
            $.ajax({
                url:_base_url_+"classes/Master.php?f=get_next_ref_code",
                method:'POST',
                data:{type:'transfer', date:date},
                dataType:'json',
                error:err=>{
                    console.log(err)
                },
                success:resp=>{
                    if(resp.status == 'success'){
                        $('#transfer_code_preview').val(resp.code);
                    }
                }
            })
        }

        if($('[name="id"]').val() == ''){
            get_next_code();
        }

        $('#date').change(function(){
            get_next_code();
        })

        $('.select2').select2({
            placeholder: "Please select here",
            width: '100%',
            dropdownParent: $('#uni_modal')
        })
        $('.datepicker').datepicker({
            dateFormat: 'dd-mm-yy',
            changeMonth: true,
            changeYear: true
        })
        $('#transfer-form').submit(function(e){
            e.preventDefault();
            var _this = $(this)
            if($('#from_account_id').val() == $('#to_account_id').val()){
                alert_toast("Source and Destination accounts cannot be the same.",'warning')
                return false;
            }
            if($('#amount').val() <= 0){
                alert_toast("Amount must be greater than 0.",'warning')
                return false;
            }
            $('.err-msg').remove();
            start_loader();
            $.ajax({
                url:_base_url_+"classes/Master.php?f=save_transfer",
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
                        location.reload();
                    }else if(resp.status == 'failed' && !!resp.msg){
                        var el = $('<div>')
                            el.addClass("alert alert-danger err-msg").text(resp.msg)
                            _this.prepend(el)
                            el.show('slow')
                            $("html, body, .modal").animate({ scrollTop: 0 }, "fast");
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
