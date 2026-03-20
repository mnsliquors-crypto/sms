<?php
require_once('../../../../config.php');

if(!isset($_GET['id'])){
    echo "No ID provided.";
    exit;
}

$id = $_GET['id'];

// 1. Fetch Company Info
$system_info = [];
$sys_qry = $conn->query("SELECT * FROM system_info");
while($row = $sys_qry->fetch_assoc()){
    $system_info[$row['meta_field']] = $row['meta_value'];
}

// 2. Fetch Sale Info
$sale_qry = $conn->query("SELECT t.*, c.display_name as customer_name, c.contact as customer_contact, c.address as customer_address, c.tax_id as customer_pan
                      FROM transactions t 
                      LEFT JOIN entity_list c ON t.entity_id = c.id
                      WHERE t.id = '{$id}' AND t.type = 'sale'");

if($sale_qry->num_rows <= 0){
    echo "Sale not found.";
    exit;
}

$sale = $sale_qry->fetch_assoc();

// 3. Fetch Items
$items = $conn->query("SELECT ti.*, i.name as item_name 
                      FROM `transaction_items` ti 
                      INNER JOIN item_list i ON ti.item_id = i.id 
                      WHERE ti.transaction_id = '{$id}'");

// Calculations
$grand_total = floatval($sale['total_amount']);
$discount = floatval($sale['discount'] ?? 0);
$tax_perc = isset($sale['tax_perc']) ? floatval($sale['tax_perc']) : 0;
$tax_amount = isset($sale['tax']) ? floatval($sale['tax']) : 0;

if($tax_perc > 0){
    $taxable_total = round($grand_total / (1 + ($tax_perc / 100)), 2);
    $vat_amount = round($grand_total - $taxable_total, 2);
} else if ($tax_amount > 0) {
    $taxable_total = round($grand_total - $tax_amount, 2);
    $vat_amount = $tax_amount;
    $tax_perc = round(($vat_amount / ($taxable_total ?: 1)) * 100, 2);
} else {
    // If no tax in record, use 0
    $taxable_total = $grand_total;
    $vat_amount = 0;
    $tax_perc = 0;
}
$subtotal = round($taxable_total + $discount, 2);

function numberToWords($number) {
    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
    $negative    = 'negative ';
    $decimal     = ' point ';
    $dictionary  = array(
        0                   => 'zero',
        1                   => 'one',
        2                   => 'two',
        3                   => 'three',
        4                   => 'four',
        5                   => 'five',
        6                   => 'six',
        7                   => 'seven',
        8                   => 'eight',
        9                   => 'nine',
        10                  => 'ten',
        11                  => 'eleven',
        12                  => 'twelve',
        13                  => 'thirteen',
        14                  => 'fourteen',
        15                  => 'fifteen',
        16                  => 'sixteen',
        17                  => 'seventeen',
        18                  => 'eighteen',
        19                  => 'nineteen',
        20                  => 'twenty',
        30                  => 'thirty',
        40                  => 'fourty',
        50                  => 'fifty',
        60                  => 'sixty',
        70                  => 'seventy',
        80                  => 'eighty',
        90                  => 'ninety',
        100                 => 'hundred',
        1000                => 'thousand',
        1000000             => 'million',
        1000000000          => 'billion',
        1000000000000       => 'trillion',
        1000000000000000    => 'quadrillion',
        1000000000000000000 => 'quintillion'
    );

    if (!is_numeric($number)) return false;
    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        trigger_error('numberToWords only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX, E_USER_WARNING);
        return false;
    }

    if ($number < 0) return $negative . numberToWords(abs($number));

    $string = $fraction = null;
    if (strpos($number, '.') !== false) {
        $parts = explode('.', $number);
        $number = $parts[0];
        $fraction = $parts[1];
    }

    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
            $string = $dictionary[$tens];
            if ($units) $string .= $hyphen . $dictionary[$units];
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[(int) $hundreds] . ' ' . $dictionary[100];
            if ($remainder) $string .= $conjunction . numberToWords($remainder);
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = numberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= numberToWords($remainder);
            }
            break;
    }

    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $number) {
            $words[] = $dictionary[$number];
        }
        $string .= implode(' ', $words);
    }

    return $string;
}

$amount_in_words = ucwords(numberToWords($grand_total));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - <?php echo $sale['reference_code'] ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 10pt; line-height: 1.2; color: #000; margin: 0; padding: 0; }
        .page { width: 210mm; min-height: 297mm; padding: 10mm; margin: 10mm auto; background: white; box-shadow: 0 0 5px rgba(0,0,0,0.1); position: relative; border: 1px solid #eee; }
        @media print {
            body { margin: 0; padding: 0; }
            .page { margin: 0; box-shadow: none; border: 1px solid #000; page-break-after: always; }
            .no-print { display: none; }
        }
        
        /* Header Fix: Logo Left, Company Center */
        .header { position: relative; margin-bottom: 20px; min-height: 100px; display: flex; align-items: flex-start; }
        .logo-box { width: 150px; }
        .logo { max-height: 80px; width: auto; }
        .company-info { flex: 1; text-align: center; padding-right: 150px; /* Offset for symmetry */ }
        .company-name { font-size: 16pt; font-weight: bold; text-transform: uppercase; display: block; }
        
        .invoice-title-box { text-align: center; border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 5px; }
        .invoice-title { font-size: 14pt; font-weight: bold; text-decoration: underline; }
        .copy-label { position: absolute; top: 10mm; right: 10mm; font-size: 8pt; font-weight: bold; border: 1px solid #000; padding: 2px 5px; }
        
        .info-section { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .customer-info { flex: 1; border: 1px solid #000; padding: 8px; margin-right: 10px; }
        .invoice-info { width: 250px; border: 1px solid #000; padding: 8px; }
        .info-label { display: inline-block; width: 90px; font-weight: bold; }

        /* Table Styling: Clean border, no internal lines */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1px solid #000; }
        .items-table th { border-bottom: 1px solid #000; padding: 5px; text-align: left; background: #f2f2f2; }
        .items-table td { padding: 4px 5px; text-align: left; border: none; } /* Remove row/col lines */
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .summary-wrapper { display: flex; justify-content: space-between; }
        .remarks-box { flex: 1; border: 1px solid #000; padding: 8px; margin-right: 10px; font-size: 9pt; min-height: 60px; }
        .totals-box { width: 280px; }
        .total-row { display: flex; justify-content: space-between; padding: 2px 5px; border: 1px solid #000; border-top: none; }
        .total-row:first-child { border-top: 1px solid #000; }
        .total-row.grand-total { font-weight: bold; font-size: 11pt; background: #eee; }

        .footer { margin-top: 30px; display: flex; justify-content: space-between; }
        .signature-box { width: 200px; border-top: 1px solid #000; text-align: center; padding-top: 5px; }
        
        .print-btn-float { position: fixed; bottom: 20px; right: 20px; background: #28a745; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 12pt; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        
        /* Repeating Header Logic */
        @media print {
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
    </style>
</head>
<body>

<button class="print-btn-float no-print" onclick="window.print()">Print This Invoice</button>

<?php 
$print_count = isset($system_info['print_invoice_count']) ? $system_info['print_invoice_count'] : 1;
$copies = [];
for($i = 0; $i < $print_count; $i++){
    if($i == 0){
        $copies[] = ['title' => 'TAX INVOICE', 'label' => 'Original'];
    } else {
        $copies[] = ['title' => 'INVOICE', 'label' => 'Copy ' . $i];
    }
}

foreach($copies as $index => $copy):
?>
<div class="page">
    <?php if(!empty($copy['label'])): ?>
        <div class="copy-label"><?php echo $copy['label'] ?></div>
    <?php endif; ?>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <td>
                    <div class="header">
                        <div class="logo-box">
                            <?php if(!empty($system_info['logo'])): ?>
                                <img src="<?php echo base_url . $system_info['logo'] ?>" alt="Logo" class="logo">
                            <?php endif; ?>
                        </div>
                        <div class="company-info">
                            <span class="company-name"><?php echo $system_info['name'] ?></span>
                            <div><?php echo $system_info['address'] ?></div>
                            <div>Phone: <?php echo $system_info['contact'] ?></div>
                            <div>Email: <?php echo $system_info['email'] ?></div>
                            <div style="font-weight: bold;">PAN/VAT No: <?php echo $system_info['pan_no'] ?></div>
                        </div>
                    </div>

                    <div class="invoice-title-box">
                        <span class="invoice-title"><?php echo $copy['title'] ?></span>
                    </div>

                    <div class="info-section">
                        <div class="customer-info">
                            <div style="font-weight: bold; border-bottom: 1px solid #000; margin-bottom: 5px;">Customer Details:</div>
                            <div><span class="info-label">Name:</span><?php echo $sale['customer_name'] ?: 'Walk-in' ?></div>
                            <div><span class="info-label">Address:</span><?php echo $sale['customer_address'] ?: 'N/A' ?></div>
                            <div><span class="info-label">Contact:</span><?php echo $sale['customer_contact'] ?: 'N/A' ?></div>
                            <?php if(!empty($sale['customer_pan'])): ?>
                                <div><span class="info-label">PAN/VAT No:</span><?php echo $sale['customer_pan'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="invoice-info">
                            <div style="font-weight: bold; border-bottom: 1px solid #000; margin-bottom: 5px;">Invoice Details:</div>
                            <div><span class="info-label">Invoice No:</span><?php echo $sale['reference_code'] ?></div>
                            <div><span class="info-label">Date:</span><?php echo date("d-M-Y", strtotime($sale['transaction_date'])) ?></div>
                            <div><span class="info-label">Method:</span><?php echo $sale['payment_terms'] ?: 'Cash' ?></div>
                        </div>
                    </div>

                    <table class="items-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 40px;">S.N</th>
                                <th>Item Description</th>
                                <th class="text-center" style="width: 80px;">Qty</th>
                                <th class="text-right" style="width: 100px;">Rate</th>
                                <th class="text-right" style="width: 120px;">Amount</th>
                            </tr>
                        </thead>
                    </table>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <table class="items-table" style="border-top: none; margin-top: -11px;">
                        <tbody>
                            <?php 
                            $items->data_seek(0);
                            $sn = 1;
                            while($row = $items->fetch_assoc()):
                            ?>
                            <tr>
                                <td class="text-center" style="width: 40px;"><?php echo $sn++ ?></td>
                                <td><?php echo $row['item_name'] ?></td>
                                <td class="text-center" style="width: 80px;"><?php echo number_format($row['quantity'], 2) ?></td>
                                <td class="text-right" style="width: 100px;"><?php echo number_format($row['unit_price'], 2) ?></td>
                                <td class="text-right" style="width: 120px;"><?php echo number_format($row['total_price'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <div class="summary-wrapper">
                        <div class="remarks-box">
                            <div style="font-weight: bold; text-decoration: underline;">Remarks:</div>
                            <div><?php echo nl2br($sale['remarks']) ?></div>
                        </div>
                        <div class="totals-box">
                            <div class="total-row">
                                <span>Total Amount:</span>
                                <span><?php echo number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="total-row">
                                <span>Discount:</span>
                                <span><?php echo number_format($discount, 2) ?></span>
                            </div>
                            <div class="total-row">
                                <span>Taxable Amount:</span>
                                <span><?php echo number_format($taxable_total, 2) ?></span>
                            </div>
                            <div class="total-row">
                                <span>VAT (<?php echo $tax_perc ?>%):</span>
                                <span><?php echo number_format($vat_amount, 2) ?></span>
                            </div>
                            <div class="total-row grand-total">
                                <span>Grand Total:</span>
                                <span><?php echo number_format($grand_total, 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 20px; font-weight: bold; font-style: italic;">
                        Amount in Words: <?php echo $amount_in_words ?> Only.
                    </div>

                    <div class="footer">
                        <div style="margin-top: 30px;">
                            Thank you for your business!<br>
                            <small>Goods once sold are not returnable.</small>
                        </div>
                        <div class="signature-box">
                            Authorized Signature
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

</body>
</html>
