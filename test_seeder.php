<?php
require_once('config.php');
require_once('classes/Master.php');

$Master = new Master();

// Mock environment for CLI execution
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
if(!isset($_SESSION)) $_SESSION = [];
$_SESSION['login_id'] = 1;

// Clean up existing test data to start fresh
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("TRUNCATE TABLE transactions");
$conn->query("TRUNCATE TABLE transaction_items");
$conn->query("TRUNCATE TABLE transaction_list");
$conn->query("TRUNCATE TABLE summary_daily_metrics");
$conn->query("TRUNCATE TABLE summary_monthly_metrics");
$conn->query("TRUNCATE TABLE search_index");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

function mockPost($data) {
    $_POST = $data;
}

// Actual IDs found in the database
$customers = [117, 119, 121, 123, 125];
$suppliers = [116, 118, 120, 122, 124, 126];
$accounts = [1, 2, 3];
$items = [1, 2, 3, 4, 5];

$today = date('Y-m-d');
$startDate = date('Y-m-d', strtotime("-7 days"));

echo "Starting intelligent seeding for 7 days with VALID IDs...\n";

for ($i = 0; $i <= 7; $i++) {
    $currentDate = date('Y-m-d', strtotime("$startDate +$i days"));
    echo "Processing Date: $currentDate\n";

    // 5 Purchases per day
    for ($p = 1; $p <= 5; $p++) {
        $supplier_id = $suppliers[array_rand($suppliers)];
        $num_items = rand(1, 3);
        $p_items = array_rand(array_flip($items), $num_items);
        if(!is_array($p_items)) $p_items = [$p_items];
        
        $item_ids = []; $qtys = []; $prices = []; $totals = [];
        $grand_total = 0;
        foreach($p_items as $iid){
            $q = rand(10, 100);
            $pr = rand(50, 500);
            $tot = $q * $pr;
            $item_ids[] = $iid;
            $qtys[] = $q;
            $prices[] = $pr;
            $totals[] = $tot;
            $grand_total += $tot;
        }

        mockPost([
            'id' => '',
            'entity_id' => $supplier_id,
            'date_po' => $currentDate,
            'payment_terms' => 'Net 30',
            'remarks' => "Bulk Test Purchase $currentDate-$p",
            'item_id' => $item_ids,
            'qty' => $qtys,
            'price' => $prices,
            'total' => $totals,
            'amount' => $grand_total,
            'tax_perc' => 0,
            'tax' => 0,
            'discount_perc' => 0,
            'discount' => 0
        ]);
        $res = json_decode($Master->save_purchase(), true);
        if($res && isset($res['status']) && $res['status'] == 'success') {
            $purchase_id = $res['id'];
            // 50% chance of making a payment
            if(rand(0, 100) > 50){
                mockPost([
                    'id' => '',
                    'parent_id' => $purchase_id,
                    'type' => 'purchase',
                    'entity_id' => $supplier_id,
                    'account_id' => 1, // Cash
                    'cash_amount' => $grand_total,
                    'transaction_date' => $currentDate,
                    'remarks' => "Payment for $purchase_id",
                    'payment_terms' => 'Cash'
                ]);
                $Master->save_payment();
            }
        }
    }

    // 5 Sales per day
    for ($s = 1; $s <= 5; $s++) {
        $customer_id = $customers[array_rand($customers)];
        $num_items = rand(1, 4);
        $s_items = array_rand(array_flip($items), $num_items);
        if(!is_array($s_items)) $s_items = [$s_items];

        $item_ids = []; $qtys = []; $prices = []; $totals = [];
        $grand_total = 0;
        foreach($s_items as $iid){
            $q = rand(1, 10);
            $pr = rand(100, 1000);
            $tot = $q * $pr;
            $item_ids[] = $iid;
            $qtys[] = $q;
            $prices[] = $pr;
            $totals[] = $tot;
            $grand_total += $tot;
        }

        mockPost([
            'id' => '',
            'customer_id' => $customer_id,
            'date_sale' => $currentDate,
            'payment_terms' => 'Cash',
            'remarks' => "Bulk Test Sale $currentDate-$s",
            'item_id' => $item_ids,
            'qty' => $qtys,
            'price' => $prices,
            'total_price' => $totals,
            'amount' => $grand_total,
            'tax_perc' => 0,
            'tax' => 0,
            'discount_perc' => 0,
            'discount' => 0
        ]);
        $res = json_decode($Master->save_sale(), true);
        if($res && isset($res['status']) && $res['status'] == 'success') {
            $sale_id = $res['id'];
            // 80% chance of making a payment
            if(rand(0, 100) > 20){
                mockPost([
                    'id' => '',
                    'parent_id' => $sale_id,
                    'type' => 'sale',
                    'entity_id' => $customer_id,
                    'account_id' => 1, // Cash
                    'cash_amount' => $grand_total,
                    'transaction_date' => $currentDate,
                    'remarks' => "Payment for $sale_id",
                    'payment_terms' => 'Cash'
                ]);
                $Master->save_payment();
            }
        }
    }

    // 5 Expenses per day
    for ($e = 1; $e <= 5; $e++) {
        mockPost([
            'id' => '',
            'account_id' => $accounts[array_rand($accounts)],
            'amount' => rand(100, 2000),
            'remarks' => "Test Expense $currentDate-$e",
            'date_created' => $currentDate,
            'status' => 1
        ]);
        $Master->save_expense();
    }

    // Update summaries for the day
    $Master->update_summary_metrics($currentDate);
}

echo "Seeding completed successfully!\n";
