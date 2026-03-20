<?php
$sel_year  = isset($_GET['year'])  ? intval($_GET['year'])  : intval(date('Y'));
$sel_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));

$from = sprintf('%04d-%02d-01', $sel_year, $sel_month);
$to   = date('Y-m-t', strtotime($from));

// Output VAT: transactions also has no separate 'tax' column currently - set to 0
$output_vat = 0.0;

// Input VAT (from Purchases) - currently 0 as we don't have a tax column in transactions yet
$input_vat = 0.0;

$net_vat_payable = $output_vat - $input_vat;

// Month-by-month breakdown for last 12 months
$monthly = [];
for ($i = 11; $i >= 0; $i--) {
    $mfrom = date('Y-m-01', strtotime("-{$i} months"));
    $mto   = date('Y-m-t',  strtotime($mfrom));
    $mlbl  = date('M Y',    strtotime($mfrom));
    $ov = 0.0;
    $iv = 0.0;
    $monthly[] = ['label'=>$mlbl,'output_vat'=>$ov,'input_vat'=>$iv,'net'=>$ov-$iv];
}

// Build year options
$years = [];
for ($y = intval(date('Y'))-3; $y <= intval(date('Y')+1); $y++) $years[] = $y;
?>
<div class="card card-outline card-olive shadow">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i>Monthly VAT Summary</h3>
        <div class="card-tools">
            <button class="btn btn-sm btn-info print-btn"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET">
            <input type="hidden" name="page" value="reports/vat_summary">
            <div class="row align-items-end">
                <div class="form-group col-md-2">
                    <label class="small font-weight-bold">Month</label>
                    <select name="month" class="form-control form-control-sm">
                        <?php for($m=1;$m<=12;$m++): ?><option value="<?php echo $m ?>" <?php echo $sel_month==$m?'selected':'' ?>><?php echo date('F',mktime(0,0,0,$m,1)) ?></option><?php endfor; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label class="small font-weight-bold">Year</label>
                    <select name="year" class="form-control form-control-sm">
                        <?php foreach($years as $y): ?><option value="<?php echo $y ?>" <?php echo $sel_year==$y?'selected':'' ?>><?php echo $y ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i> View</button>
                </div>
            </div>
        </form>
        <hr class="mt-0">

        <!-- Selected Month Summary -->
        <h5 class="font-weight-bold">VAT Summary for <?php echo date('F Y',strtotime($from)) ?></h5>
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="small-box bg-warning">
                    <div class="inner"><h4><?php echo number_format($output_vat,2) ?></h4><p>Output VAT (from Sales)</p></div>
                    <div class="icon"><i class="fas fa-arrow-up"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-primary">
                    <div class="inner"><h4><?php echo number_format($input_vat,2) ?></h4><p>Input VAT (from Purchases)</p></div>
                    <div class="icon"><i class="fas fa-arrow-down"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box <?php echo $net_vat_payable>=0?'bg-danger':'bg-success' ?>">
                    <div class="inner">
                        <h4><?php echo number_format(abs($net_vat_payable),2) ?></h4>
                        <p><?php echo $net_vat_payable>=0?'Net VAT Payable (Output – Input)':'VAT Credit (Input > Output)' ?></p>
                    </div>
                    <div class="icon"><i class="fas fa-balance-scale"></i></div>
                </div>
            </div>
        </div>

        <div class="callout <?php echo $net_vat_payable>=0?'callout-danger':'callout-success' ?> mb-3">
            <h6><strong>VAT Calculation:</strong></h6>
            <strong>Output VAT</strong> <?php echo number_format($output_vat,2) ?>
            &nbsp;–&nbsp; <strong>Input VAT</strong> <?php echo number_format($input_vat,2) ?>
            &nbsp;=&nbsp; <strong><?php echo $net_vat_payable>=0?'VAT Payable':'VAT Credit' ?>: <?php echo number_format(abs($net_vat_payable),2) ?></strong>
        </div>

        <!-- Last 12 months trend -->
        <div id="print-data">
            <h6 class="font-weight-bold mt-3">Last 12 Months VAT Trend</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>Month</th>
                            <th class="text-right">Output VAT</th>
                            <th class="text-right">Input VAT</th>
                            <th class="text-right">Net VAT Payable</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($monthly as $m): ?>
                        <tr>
                            <td class="font-weight-bold"><?php echo $m['label'] ?></td>
                            <td class="text-right text-warning"><?php echo number_format($m['output_vat'],2) ?></td>
                            <td class="text-right text-primary"><?php echo number_format($m['input_vat'],2) ?></td>
                            <td class="text-right font-weight-bold <?php echo $m['net']>=0?'text-danger':'text-success' ?>"><?php echo number_format($m['net'],2) ?></td>
                            <td class="text-center">
                                <?php if($m['output_vat']==0&&$m['input_vat']==0): ?><span class="badge badge-secondary">No VAT</span>
                                <?php elseif($m['net']>0): ?><span class="badge badge-danger">Payable</span>
                                <?php elseif($m['net']<0): ?><span class="badge badge-success">Credit</span>
                                <?php else: ?><span class="badge badge-info">Nil</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>$(function(){
    $('.print-btn').click(function(){
        start_loader();
        var nw=window.open('','_blank','width=1000,height=800');
        nw.document.write('<html>'+$('head').clone()[0].outerHTML+'<body>'+$('#print-data').html()+'</body></html>');
        nw.document.close();
        setTimeout(function(){nw.print();setTimeout(function(){nw.close();end_loader();},200);},500);
    });
});</script>
