<?php if(
        $_settings->chk_flashdata('success')): ?>
<script>
    alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Accounts Dashboard</h3>
    </div>
    <div class="card-body">
        <form id="filter-form" class="form-inline mb-2">
            <div class="form-group mr-2">
                <label for="date" class="mr-1">As of:</label>
                <input type="date" id="date" name="date" class="form-control form-control-sm" value="<?php echo date('Y-m-d') ?>">
            </div>
            <button class="btn btn-sm btn-primary" id="btn-fetch" type="button">Fetch</button>
            <span id="from_daily_record" class="ml-2 badge badge-info" style="display:none;">Using recorded balances</span>
            <span class="ml-2">Net Movement (Cash): <strong id="net_movement">0.00</strong></span>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="accounts-dashboard-table">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<script>
$(document).ready(function(){
    var tbl = $('#accounts-dashboard-table').dataTable({
        ordering:false,
        paging:false,
        searching:false,
        info:false
    });
    function fetchBalances(date){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=get_balances_by_date",
            method:'POST',
            data:{date:date},
            dataType:'json',
            error:function(err){
                console.error(err);
                alert_toast("Failed to fetch balances.",'error');
                end_loader();
            },
            success:function(resp){
                end_loader();
                if(resp && !resp.error && resp.accounts){
                    // rebuild table rows
                    var rows = [];
                    Object.keys(resp.accounts).forEach(function(id){
                        var acc = resp.accounts[id];
                        var bal = parseFloat(acc.balance).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
                        var acc_link = '<a href="'+_base_url_+'admin/?page=reports/account_ledger&account_id='+id+'" class="text-primary font-weight-bold">'+acc.name+'</a>';
                        rows.push([acc_link, bal]);
                    });
                    tbl.DataTable().clear().rows.add(rows).draw();
                    if(resp.net_movement !== undefined){
                        $('#net_movement').text(parseFloat(resp.net_movement).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}));
                    }
                    if(resp.from_daily_record === true){
                        $('#from_daily_record').show();
                    } else {
                        $('#from_daily_record').hide();
                    }
                } else {
                    var msg = (resp && resp.msg)?resp.msg:"No data found.";
                    alert_toast(msg,'warning');
                }
            }
        });
    }
    var today = $('#date').val();
    fetchBalances(today);
    $('#btn-fetch').click(function(){
        fetchBalances($('#date').val());
    });
    $('#date').change(function(){
        fetchBalances($(this).val());
    });
});
</script>