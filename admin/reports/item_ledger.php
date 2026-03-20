<?php
$from    = isset($_GET['from'])    ? $_GET['from']    : date("Y-m-01");
$to      = isset($_GET['to'])      ? $_GET['to']      : date("Y-m-d");
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

$rows = [];
$running = 0;
$t_in = $t_out = 0;
$item_name = '';

if ($item_id) {
    $item_res = $conn->query("SELECT name FROM item_list WHERE id={$item_id} LIMIT 1")->fetch_assoc();
    $item_name = $item_res['name'] ?? '';

    // Unified opening balance before 'from'
    $opening_bal = floatval($conn->query("
        SELECT COALESCE(SUM(CASE 
            WHEN t.type IN ('purchase', 'opening_stock', 'adjustment') THEN ti.quantity 
            WHEN t.type = 'sale' THEN -ti.quantity 
            WHEN t.type = 'return' THEN (CASE WHEN EXISTS (SELECT 1 FROM entity_list WHERE id = t.entity_id AND entity_type = 'Supplier') THEN -ti.quantity ELSE ti.quantity END)
            ELSE 0 END), 0)
        FROM transaction_items ti 
        JOIN transactions t ON ti.transaction_id = t.id 
        WHERE ti.item_id = '{$item_id}' AND DATE(t.transaction_date) < '{$from}'
    ")->fetch_array()[0]);
    $running = $opening_bal;

    $txns = $conn->query("
        SELECT ti.*, t.transaction_date as date_created, t.reference_code as ref_code, t.type as txn_type_raw,
            (SELECT entity_type FROM entity_list WHERE id = t.entity_id LIMIT 1) as entity_type
        FROM transaction_items ti
        JOIN transactions t ON ti.transaction_id = t.id
        WHERE ti.item_id={$item_id}
          AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'
        ORDER BY t.transaction_date ASC
    ");
    while ($r = $txns->fetch_assoc()) {
        if ($r['txn_type_raw'] == 'purchase') {
            $r['qty_in'] = $r['quantity']; $r['qty_out'] = 0; $running += $r['quantity']; $t_in += $r['quantity']; $r['txn_type'] = "Purchase";
        } elseif ($r['txn_type_raw'] == 'sale') {
            $r['qty_in'] = 0; $r['qty_out'] = $r['quantity']; $running -= $r['quantity']; $t_out += $r['quantity']; $r['txn_type'] = "Sale";
        } elseif ($r['txn_type_raw'] == 'return') {
            if ($r['entity_type'] == 'Supplier') { // Vendor Return: OUT
                $r['qty_in'] = 0; $r['qty_out'] = $r['quantity']; $running -= $r['quantity']; $t_out += $r['quantity']; $r['txn_type'] = "Purchase Return";
            } else { // Customer Return: IN
                $r['qty_in'] = $r['quantity']; $r['qty_out'] = 0; $running += $r['quantity']; $t_in += $r['quantity']; $r['txn_type'] = "Sales Return";
            }
        } else {
            $delta = $r['quantity'];
            $r['qty_in'] = $delta > 0 ? $delta : 0; $r['qty_out'] = $delta < 0 ? abs($delta) : 0; $running += $delta;
            if($delta > 0) $t_in += $delta; else $t_out += abs($delta);
            $r['txn_type'] = "Adjustment";
        }
        $r['running_balance'] = $running;
        $rows[] = $r;
    }
}

$items = $conn->query("SELECT id,name FROM item_list WHERE status=1 ORDER BY name");
?>
<div class="card card-outline card-warning shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-list mr-1"></i>Item Ledger</h3>
        <div class="card-tools">
            <?php if($item_id): ?>
            <button class="btn btn-sm btn-info print-btn"><i class="fas fa-print"></i> Print</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <form method="GET">
            <input type="hidden" name="page" value="reports/item_ledger">
            <div class="row align-items-end">
                <div class="form-group col-md-3">
                    <label class="small font-weight-bold">Select Item</label>
                    <select name="item_id" class="form-control form-control-sm select2" required>
                        <option value="">-- Select Item --</option>
                        <?php while($it=$items->fetch_assoc()): ?>
                            <option value="<?php echo $it['id'] ?>" <?php echo $item_id==$it['id']?'selected':'' ?>><?php echo htmlspecialchars($it['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label class="small font-weight-bold">Date From</label>
                    <input type="date" name="from" value="<?php echo $from ?>" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-2">
                    <label class="small font-weight-bold">Date To</label>
                    <input type="date" name="to" value="<?php echo $to ?>" class="form-control form-control-sm">
                </div>
                <div class="form-group col-md-3">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i> View Ledger</button>
                    <a href="<?php echo base_url ?>admin/?page=reports/item_ledger" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Clear</a>
                </div>
            </div>
        </form>
        <hr class="mt-0">
        <?php if(!$item_id): ?>
            <div class="text-center text-muted py-5"><i class="fas fa-clipboard-list fa-3x mb-3 d-block text-light"></i>Please select an item to view its ledger.</div>
        <?php else: ?>
        <div id="print-data">
            <h5 class="font-weight-bold"><?php echo htmlspecialchars($item_name) ?> — Ledger</h5>
            <p class="text-muted small"><?php echo date('d-m-Y',strtotime($from)) ?> to <?php echo date('d-m-Y',strtotime($to)) ?></p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th><th>Date</th><th>Ref No</th><th>Type</th>
                            <th class="text-right text-success">Qty In</th>
                            <th class="text-right text-danger">Qty Out</th>
                            <th class="text-right">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                    <tr class="table-secondary">
                        <td colspan="4" class="font-weight-bold">Opening Balance (before <?php echo date('d-m-Y',strtotime($from)) ?>)</td>
                        <td></td><td></td>
                        <td class="text-right font-weight-bold <?php echo $opening_bal<0?'text-danger':'' ?>"><?php echo number_format($opening_bal, 2) ?></td>
                    </tr>
                    <?php if(empty($rows)): ?><tr><td colspan="7" class="text-center text-muted py-4">No transactions found for this period.</td></tr><?php endif; ?>
                    <?php $i=1; foreach($rows as $r): ?>
                        <tr>
                            <td><?php echo $i++ ?></td>
                            <td><?php echo date('d-m-Y',strtotime($r['date_created'])) ?></td>
                            <td><?php echo htmlspecialchars($r['ref_code']) ?></td>
                            <td>
                                <?php $bc=['Purchase'=>'badge-success','Sale'=>'badge-danger','Adjustment'=>'badge-info','Sales Return'=>'badge-primary','Purchase Return'=>'badge-warning'];
                                echo '<span class="badge '.($bc[$r['txn_type']]??'badge-secondary').'">'.$r['txn_type'].'</span>'; ?>
                            </td>
                            <td class="text-right text-success"><?php echo $r['qty_in']>0?number_format($r['qty_in'],2):'–' ?></td>
                            <td class="text-right text-danger"><?php echo $r['qty_out']>0?number_format($r['qty_out'],2):'–' ?></td>
                            <td class="text-right font-weight-bold <?php echo $r['running_balance']<0?'text-danger':'' ?>"><?php echo number_format($r['running_balance'],2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark font-weight-bold">
                            <th colspan="4" class="text-right">Period Totals</th>
                            <th class="text-right text-success"><?php echo number_format($t_in,2) ?></th>
                            <th class="text-right text-danger"><?php echo number_format($t_out,2) ?></th>
                            <th class="text-right"><?php echo number_format($running,2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>$(function(){
    if($.fn.select2) $('.select2').select2({theme:'bootstrap4',width:'100%'});
    $('.print-btn').click(function(){
        start_loader();
        var nw=window.open('','_blank','width=1100,height=900');
        nw.document.write('<html>'+$('head').clone()[0].outerHTML+'<body>'+$('#print-data').html()+'</body></html>');
        nw.document.close();
        setTimeout(function(){nw.print();setTimeout(function(){nw.close();end_loader();},200);},500);
    });
});</script>
