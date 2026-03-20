<?php
require_once('config.php');

function getRandomDate($startDate, $endDate) {
    $min = strtotime($startDate);
    $max = strtotime($endDate);
    $val = rand($min, $max);
    return date('Y-m-d H:i:s', $val);
}

// Opening balance
$date = '2024-03-01 08:00:00';
$conn->query("INSERT INTO transactions (reference_code, type, total_amount, status, transaction_date, date_created) VALUES ('OPEN-1', 'opening_balance', 500000, 1, '2024-03-01', '$date')");
$tx_id = $conn->insert_id;
$conn->query("INSERT INTO transaction_list (ref_table, ref_id, account_id, amount, type, date_created) VALUES ('transactions', $tx_id, 2, 500000, 1, '$date')");


$customers = [1, 2, 3];
$suppliers = [4, 5];
$accounts = [1, 2, 3];
$items = [
    1 => ['cost' => 120, 'price' => 199],
    2 => ['cost' => 180, 'price' => 250],
    3 => ['cost' => 55,  'price' => 90],
    4 => ['cost' => 25,  'price' => 45],
    5 => ['cost' => 12,  'price' => 20]
];

$startDate = '2024-03-01';
$endDate = '2024-03-17';

// 15 Purchases
for ($i=1; $i<=15; $i++) {
    $date = getRandomDate($startDate, $endDate);
    $tx_date = date('Y-m-d', strtotime($date));
    $supplier = $suppliers[array_rand($suppliers)];
    $account = $accounts[array_rand($accounts)];
    
    // items
    $num_items = rand(1, 3);
    $total_amount = 0;
    $tx_items = [];
    $keys = array_rand($items, $num_items);
    if (!is_array($keys)) $keys = [$keys];
    
    foreach ($keys as $item_id) {
        $qty = rand(10, 50);
        $cost = $items[$item_id]['cost'];
        $total_price = $qty * $cost;
        $total_amount += $total_price;
        $tx_items[] = "INSERT INTO transaction_items (transaction_id, item_id, quantity, unit_price, cost_price, total_price, date_created) VALUES ('{tx_id}', $item_id, $qty, $cost, $cost, $total_price, '$date')";
    }
    
    $tax = $total_amount * 0.12;
    $total_amount += $tax;
    
    $po_code = "PO-SEED-" . str_pad($i, 4, '0', STR_PAD_LEFT);
    $conn->query("INSERT INTO transactions (reference_code, type, entity_id, account_id, total_amount, tax_perc, tax, payment_terms, status, transaction_date, date_created) VALUES ('$po_code', 'purchase', $supplier, $account, $total_amount, 12, $tax, 'Net 30', 1, '$tx_date', '$date')");
    $tx_id = $conn->insert_id;
    foreach ($tx_items as $query) {
        $query = str_replace('{tx_id}', $tx_id, $query);
        $conn->query($query);
    }
    
    // Add payment (fully paid)
    $pay_code = "PAY-PUR-" . str_pad($i, 4, '0', STR_PAD_LEFT);
    $conn->query("INSERT INTO transactions (reference_code, type, entity_id, account_id, parent_id, total_amount, payment_terms, status, transaction_date, date_created) VALUES ('$pay_code', 'payment', $supplier, $account, $tx_id, $total_amount, 'Cash', 1, '$tx_date', '$date')");
}

// 20 Sales
for ($i=1; $i<=20; $i++) {
    $date = getRandomDate($startDate, $endDate);
    $tx_date = date('Y-m-d', strtotime($date));
    $customer = $customers[array_rand($customers)];
    $account = $accounts[array_rand($accounts)];
    
    // items
    $num_items = rand(1, 4);
    $total_amount = 0;
    $tx_items = [];
    $keys = array_rand($items, $num_items);
    if (!is_array($keys)) $keys = [$keys];
    
    foreach ($keys as $item_id) {
        $qty = rand(1, 10);
        $cost = $items[$item_id]['cost'];
        $price = $items[$item_id]['price'];
        $total_price = $qty * $price;
        $profit = $total_price - ($qty * $cost);
        $total_amount += $total_price;
        $tx_items[] = "INSERT INTO transaction_items (transaction_id, item_id, quantity, unit_price, cost_price, total_price, profit, date_created) VALUES ('{tx_id}', $item_id, $qty, $price, $cost, $total_price, $profit, '$date')";
    }
    
    $sale_code = "SALE-SEED-" . str_pad($i, 4, '0', STR_PAD_LEFT);
    $conn->query("INSERT INTO transactions (reference_code, type, entity_id, account_id, total_amount, payment_terms, status, transaction_date, date_created) VALUES ('$sale_code', 'sale', $customer, $account, $total_amount, 'Cash', 1, '$tx_date', '$date')");
    $tx_id = $conn->insert_id;
    foreach ($tx_items as $query) {
        $query = str_replace('{tx_id}', $tx_id, $query);
        $conn->query($query);
    }
    
    // Add payment (fully paid)
    $pay_code = "PAY-SALE-" . str_pad($i, 4, '0', STR_PAD_LEFT);
    $conn->query("INSERT INTO transactions (reference_code, type, entity_id, account_id, parent_id, total_amount, payment_terms, status, transaction_date, date_created) VALUES ('$pay_code', 'payment', $customer, $account, $tx_id, $total_amount, 'Cash', 1, '$tx_date', '$date')");
}


// 15 Expenses
$expense_types = ['Office Supplies', 'Utilities', 'Salaries', 'Rent', 'Miscellaneous'];
for ($i=1; $i<=15; $i++) {
    $date = getRandomDate($startDate, $endDate);
    $tx_date = date('Y-m-d', strtotime($date));
    $account = $accounts[array_rand($accounts)];
    $amount = rand(500, 5000);
    $remark = $expense_types[array_rand($expense_types)] . " Expense";
    
    $exp_code = "EXP-SEED-" . str_pad($i, 4, '0', STR_PAD_LEFT);
    $conn->query("INSERT INTO transactions (reference_code, type, account_id, total_amount, remarks, payment_terms, status, transaction_date, date_created) VALUES ('$exp_code', 'expense', $account, $amount, '$remark', 'Cash', 1, '$tx_date', '$date')");
}

echo "Seed data created successfully!\n";
?>
