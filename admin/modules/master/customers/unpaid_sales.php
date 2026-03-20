<?php
require_once('../../config.php');
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT *, display_name as name FROM `entity_list` where id = '{$_GET['id']}' AND entity_type = 'Customer' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-6">
            <h5 class="modal-title">Record Bulk Payment for <b><?php echo $name ?? 'Unknown' ?></b></h5>
        </div>
        <div class="col-6 text-right">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>
    
    <form action="" id="bulk-payment-form">
        <input type="hidden" name="customer_id" value="<?php echo $id ?>">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="cash_amount" class="control-label">Cash Amount</label>
                    <input type="number" step="any" name="cash_amount" id="cash_amount" class="form-control rounded-0 text-right payment-input" value="0">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="qr_amount" class="control-label">QR Amount</label>
                    <input type="number" step="any" name="qr_amount" id="qr_amount" class="form-control rounded-0 text-right payment-input" value="0">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="bank_amount" class="control-label">Bank Amount</label>
                    <input type="number" step="any" name="bank_amount" id="bank_amount" class="form-control rounded-0 text-right payment-input" value="0">
                </div>
            </div>
        </div>
        <div class="row">
             <div class="col-md-6">
                <div class="form-group">
                    <label for="amount" class="control-label">Total Payment</label>
                    <input type="number" step="any" name="amount" id="amount" class="form-control rounded-0 text-right font-weight-bold" value="0" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group text-right pt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Apply Payment</button>
                </div>
            </div>
        </div>
    </form>

    <hr>
    <h6>Unpaid Sales (Auto-Applied)</h6>
    <table class="table table-bordered table-striped">
        <colgroup>
            <col width="20%">
            <col width="30%">
            <col width="25%">
            <col width="25%">
        </colgroup>
        <thead>
            <tr>
                <th>Date</th>
                <th>Sale Code</th>
                <th class="text-right">Total Amount</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sales = $conn->query("SELECT *, reference_code as sales_code, transaction_date as date_created, total_amount as amount 
                                   FROM transactions where entity_id = '{$id}' AND type = 'sale' order by transaction_date asc");
            $has_unpaid = false;
            $total_outstanding = 0;
            while($row = $sales->fetch_assoc()):
                $paid = $conn->query("SELECT SUM(total_amount) as paid FROM transactions where parent_id = '{$row['id']}' and type = 'payment'")->fetch_assoc()['paid'];
                $paid = $paid > 0 ? $paid : 0;
                $balance = $row['amount'] - $paid;
                if($balance > 0.01):
                    $has_unpaid = true;
                    $total_outstanding += $balance;
            ?>
            <tr>
                <td><?php echo date("d-m-Y", strtotime($row['date_created'])) ?></td>
                <td><?php echo $row['sales_code'] ?></td>
                <td class="text-right"><?php echo number_format($row['amount'], 2) ?></td>
                <td class="text-right"><?php echo number_format($balance, 2) ?></td>
            </tr>
            <?php endif; endwhile; ?>
            <?php if(!$has_unpaid): ?>
            <tr>
                <td colspan="4" class="text-center">No unpaid sales found.</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="bg-light">
                <th colspan="3" class="text-right">Total Outstanding</th>
                <th class="text-right font-weight-bold text-danger"><?php echo number_format($total_outstanding, 2) ?></th>
            </tr>
        </tfoot>
    </table>
</div>
<script>
    $(function(){
        $('.payment-input').on('input', function(){
            var total = 0;
            $('.payment-input').each(function(){
                total += parseFloat($(this).val()) || 0;
            });
            $('#amount').val(total.toFixed(2));
        });

        $('#bulk-payment-form').submit(function(e){
            e.preventDefault();
            var total_pay = parseFloat($('#amount').val()) || 0;
            var max_pay = <?php echo floatval($total_outstanding) ?>;
            
            if(total_pay <= 0){
                alert_toast("Please enter a valid payment amount.", "warning");
                return false;
            }
            if(total_pay > max_pay + 0.01){
                alert_toast("Payment amount exceeds total outstanding balance.", "warning");
                return false;
            }

            start_loader();
            $.ajax({
                url: _base_url_+"classes/Master.php?f=save_bulk_payment",
                data: new FormData($(this)[0]),
                cache: false, contentType: false, processData: false, method: 'POST', type: 'POST', dataType: 'json',
                error: function(xhr, status, error){
                    console.error(xhr, status, error);
                    alert_toast("An error occured", 'error');
                    end_loader();
                },
                success: function(resp){
                    if(resp && resp.status == 'success'){
                        location.reload();
                    } else {
                        var msg = (resp && (resp.msg || resp.error)) ? (resp.msg || resp.error) : "An error occured";
                        alert_toast(msg, 'error'); end_loader();
                    }
                }
            })
        })
    })
</script>
