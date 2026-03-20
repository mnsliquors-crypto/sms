<?php
require_once __DIR__ . '/../../../../config.php';
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT t.*, ti.item_id, ti.quantity, ti.unit_price as price FROM `transactions` t INNER JOIN transaction_items ti ON t.id = ti.transaction_id WHERE t.id = '{$_GET['id']}' AND t.type = 'adjustment'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            if(!is_numeric($k)) $$k = $v;
        }
        $type = ($quantity > 0) ? 1 : 2;
        $quantity = abs($quantity);
    }
}
?>
<div class="container-fluid">
    <form action="" id="adjustment-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <div class="form-group">
            <label for="item_id" class="control-label">Item</label>
            <select name="item_id" id="item_id" class="custom-select select2" required>
                <option value="" disabled <?php echo !isset($item_id) ? 'selected' : '' ?>></option>
                <?php 
                $item = $conn->query("SELECT i.*, i.quantity as available FROM `item_list` i where status = 1 order by `name` asc");
                while($row=$item->fetch_assoc()):
                ?>
                <option value="<?php echo $row['id'] ?>" <?php echo isset($item_id) && $item_id == $row['id'] ? 'selected' : '' ?>><?php echo $row['name'] ?> (Available: <?php echo number_format($row['available']) ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="type" class="control-label">Adjustment Type</label>
            <select name="type" id="type" class="custom-select" required>
                <option value="1" <?php echo isset($type) && $type == 1 ? 'selected' : '' ?>>Addition (+)</option>
                <option value="2" <?php echo isset($type) && $type == 2 ? 'selected' : '' ?>>Subtraction (-)</option>
            </select>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="quantity" class="control-label">Quantity</label>
                    <input type="number" step="any" name="quantity" id="quantity" class="form-control rounded-0 text-right" value="<?php echo isset($quantity) ? $quantity : '' ?>" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="price" class="control-label">Rate</label>
                    <input type="number" step="any" name="price" id="price" class="form-control rounded-0 text-right" value="<?php echo isset($price) ? $price : '' ?>" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="amount" class="control-label">Amount</label>
                    <input type="number" step="any" name="amount" id="amount" class="form-control rounded-0 text-right bg-light" value="<?php echo isset($total_amount) ? $total_amount : (isset($amount) ? $amount : '') ?>" readonly required>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="remarks" class="control-label">Remarks / Reason</label>
            <textarea name="remarks" id="remarks" rows="2" class="form-control rounded-0" placeholder="e.g. Damaged, Correction, etc."><?php echo isset($remarks) ? $remarks : '' ?></textarea>
        </div>
    </form>
</div>
<script>
    $(function(){
        $('#item_id').select2({placeholder:"Please select item", width:'100%', dropdownParent: $('#uni_modal')})
        
        // Auto-calculate amount
        $('#quantity, #price').on('input', function(){
            var qty = $('#quantity').val() || 0;
            var price = $('#price').val() || 0;
            var amount = parseFloat(qty) * parseFloat(price);
            $('#amount').val(parseFloat(amount).toFixed(2));
        })

        $('#adjustment-form').submit(function(e){
            e.preventDefault();
            start_loader();
            $.ajax({
                url:_base_url_+"classes/Master.php?f=save_adjustment",
                data: new FormData($(this)[0]),
                cache: false, contentType: false, processData: false, method: 'POST', type: 'POST', dataType: 'json',
                success:function(resp){
                    if(resp.status == 'success'){
                        location.reload();
                    }else{
                        alert_toast(resp.msg || "An error occured",'error');
                        end_loader();
                    }
                }
            })
        })
    })
</script>
