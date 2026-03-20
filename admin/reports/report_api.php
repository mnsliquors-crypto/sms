<?php
require_once('../../config.php');

// Restrict access
/*
if (!isset($_SESSION['login_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
*/

$action = isset($_GET['action']) ? $_GET['action'] : '';
$from   = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to     = isset($_GET['to'])   ? $_GET['to']   : date('Y-m-d');
$days   = isset($_GET['days']) ? intval($_GET['days']) : 7;

header('Content-Type: application/json');

switch ($action) {

    // ─── Dashboard Charts ─────────────────────────────────────────────────────

    case 'daily_sales_trend':
        $labels = []; $data = [];
        $limit = max(1, min(365, $days));
        $qry = $conn->query("
            SELECT metric_date as d, total_sales as total
            FROM summary_daily_metrics
            WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL {$limit} DAY)
              AND metric_date <= CURDATE()
            ORDER BY metric_date ASC
        ");
        while ($row = $qry->fetch_assoc()) {
            $labels[] = date('d M', strtotime($row['d']));
            $data[]   = floatval($row['total']);
        }
        echo json_encode(['labels' => $labels, 'data' => $data]);
        break;

    case 'monthly_sales_purchase':
        require_once('../../classes/Cache.php');
        $cache = new Cache(3600); // 1 hour cache for historical charts
        $cache_key = 'monthly_sales_purchase_chart';
        $data = $cache->get($cache_key);
        
        if(!$data){
            $labels = []; $sales = []; $purchases = [];
            $months = [];
            for ($m = 11; $m >= 0; $m--) {
                $mon = date('Y-m', strtotime("-{$m} months"));
                $months[$mon] = ['label' => date('M Y', strtotime($mon . '-01')), 's' => 0, 'p' => 0];
            }

            $sm_qry = $conn->query("SELECT metric_year, metric_month, total_sales, total_purchases 
                                    FROM summary_monthly_metrics 
                                    WHERE STR_TO_DATE(CONCAT(metric_year,'-',metric_month,'-01'), '%Y-%m-%d') >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)");
            while($r = $sm_qry->fetch_assoc()){
                $mon = $r['metric_year'] . '-' . sprintf("%02d", $r['metric_month']);
                if(isset($months[$mon])) {
                    $months[$mon]['s'] = floatval($r['total_sales']);
                    $months[$mon]['p'] = floatval($r['total_purchases']);
                }
            }

            foreach($months as $m_data){
                $labels[] = $m_data['label'];
                $sales[] = $m_data['s'];
                $purchases[] = $m_data['p'];
            }
            $data = ['labels' => $labels, 'sales' => $sales, 'purchases' => $purchases];
            $cache->set($cache_key, $data);
        }
        echo json_encode($data);
        break;

    case 'payment_mode_dist':
        $rows = $conn->query("
            SELECT a.name as payment_method, COALESCE(SUM(p.total_amount),0) as total
            FROM transactions p
            JOIN account_list a ON p.account_id = a.id
            WHERE p.type = 'payment' AND DATE(p.transaction_date) BETWEEN '{$from}' AND '{$to}'
            GROUP BY p.account_id
        ");
        $labels = []; $data = [];
        while ($r = $rows->fetch_assoc()) {
            $labels[] = $r['payment_method'];
            $data[]   = floatval($r['total']);
        }
        echo json_encode(['labels' => $labels, 'data' => $data]);
        break;

    case 'top_items':
        $rows = $conn->query("
            SELECT i.name, COALESCE(SUM(ti.quantity),0) as qty_sold
            FROM transaction_items ti
            INNER JOIN transactions t ON ti.transaction_id = t.id
            INNER JOIN item_list i ON ti.item_id = i.id
            WHERE t.type = 'sale'
              AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'
            GROUP BY ti.item_id
            ORDER BY qty_sold DESC
            LIMIT 10
        ");
        $labels = []; $data = [];
        while ($r = $rows->fetch_assoc()) {
            $labels[] = $r['name'];
            $data[]   = floatval($r['qty_sold']);
        }
        echo json_encode(['labels' => $labels, 'data' => $data]);
        break;

    // ─── KPI Card Data ────────────────────────────────────────────────────────

    case 'dashboard_kpis':
        require_once('../../classes/Cache.php');
        $cache = new Cache(300); // 5-minute cache
        $data = $cache->get('dashboard_kpi_metrics_api');
        
        if(!$data){
            $today     = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $month_s   = date('Y-m-01');

            $get = function($sql) use ($conn) {
                return floatval($conn->query($sql)->fetch_array()[0] ?? 0);
            };
            $pct = function($today, $yest) {
                if ($yest == 0) return ['val' => $today > 0 ? 100 : 0, 'up' => true];
                $v = round((($today - $yest) / $yest) * 100, 1);
                return ['val' => abs($v), 'up' => $v >= 0];
            };

            // Today / Yesterday metrics from summary table
            $td = $conn->query("SELECT * FROM summary_daily_metrics WHERE metric_date='{$today}'")->fetch_assoc();
            $yd = $conn->query("SELECT * FROM summary_daily_metrics WHERE metric_date='{$yesterday}'")->fetch_assoc();
            
            $td_sales    = floatval($td['total_sales'] ?? 0);
            $yd_sales    = floatval($yd['total_sales'] ?? 0);
            $td_purchase = floatval($td['total_purchases'] ?? 0);
            $yd_purchase = floatval($yd['total_purchases'] ?? 0);
            $td_expense  = floatval($td['total_expenses'] ?? 0);
            $yd_expense  = floatval($yd['total_expenses'] ?? 0);
            $td_profit   = floatval($td['net_profit'] ?? 0);
            $yd_profit   = floatval($yd['net_profit'] ?? 0);
            $td_discount = floatval($td['total_discount'] ?? 0);

            // Monthly metrics from summary monthly table
            $cur_y = date('Y'); $cur_m = date('m');
            $ms = $conn->query("SELECT * FROM summary_monthly_metrics WHERE metric_year={$cur_y} AND metric_month={$cur_m}")->fetch_assoc();
            
            $month_sales    = floatval($ms['total_sales'] ?? 0);
            $month_purchase = floatval($ms['total_purchases'] ?? 0);
            $month_expense  = floatval($ms['total_expenses'] ?? 0);
            $month_discount = floatval($ms['total_discount'] ?? 0);
            $month_profit   = floatval($ms['net_profit'] ?? 0);

            // Cumulative from monthly summary (very fast)
            $total_m = $conn->query("SELECT SUM(total_sales) as s, SUM(net_profit) as p FROM summary_monthly_metrics")->fetch_assoc();
            $total_sales    = floatval($total_m['s'] ?? 0);
            $total_profit   = floatval($total_m['p'] ?? 0);

            // Accounts
            $accounts = [];
            $acct_q = $conn->query("SELECT id, name, balance FROM account_list WHERE status=1 ORDER BY name ASC");
            while($ar = $acct_q->fetch_assoc()){
                $ar['balance'] = floatval($ar['balance']);
                $accounts[] = $ar;
            }

            // Receivable / Payable
            $receivable = $get("SELECT COALESCE(SUM(t.total_amount - (SELECT COALESCE(SUM(p.total_amount),0) FROM transactions p WHERE p.parent_id = t.id AND p.type IN ('payment', 'return'))), 0) FROM transactions t WHERE t.type = 'sale'");
            $payable    = $get("SELECT COALESCE(SUM(t.total_amount - (SELECT COALESCE(SUM(p.total_amount),0) FROM transactions p WHERE p.parent_id = t.id AND p.type IN ('payment', 'return'))), 0) FROM transactions t WHERE t.type = 'purchase'");

            // Low Stock
            $low_stock_count = $get("SELECT COUNT(*) FROM (SELECT i.id, COALESCE((SELECT SUM(CASE WHEN t.type IN ('purchase', 'opening_stock', 'adjustment') THEN ti.quantity WHEN t.type = 'sale' THEN -ti.quantity WHEN t.type = 'return' THEN (CASE WHEN EXISTS(SELECT 1 FROM entity_list WHERE id = t.entity_id AND entity_type = 'Supplier') THEN -ti.quantity ELSE ti.quantity END) ELSE 0 END) FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE ti.item_id=i.id),0) as available FROM item_list i WHERE i.status=1 HAVING available < 10) as t");

            // Today's POS Sales
            $td_pos_sales = $get("SELECT COALESCE(SUM(total_amount), 0) FROM transactions WHERE type='sale' AND DATE(transaction_date) = '{$today}' AND remarks LIKE '%[POS]%'");

            $data = [
                'today_pos_sales'=> $td_pos_sales,
                'today_sales'    => $td_sales,    'sales_pct'    => $pct($td_sales,    $yd_sales),
                'today_purchase' => $td_purchase, 'purchase_pct' => $pct($td_purchase, $yd_purchase),
                'today_expense'  => $td_expense,  'expense_pct'  => $pct($td_expense,  $yd_expense),
                'today_profit'   => $td_profit,   'profit_pct'   => $pct($td_profit,   $yd_profit),
                'today_discount' => $td_discount,
                'month_sales'    => $month_sales,
                'month_purchase' => $month_purchase,
                'month_expense'  => $month_expense,
                'month_profit'   => $month_profit,
                'month_discount' => $month_discount,
                'total_profit'   => $total_profit,
                'accounts'       => $accounts,
                'receivable'     => $receivable,
                'payable'        => $payable,
                'low_stock_count'=> $low_stock_count,
                'updated_at'     => date('h:i:s A')
            ];
            $cache->set('dashboard_kpi_metrics_api', $data);
        }
        echo json_encode($data);
        break;

    // ─── CSV Exports ──────────────────────────────────────────────────────────

    case 'export_excel':
        $report = isset($_GET['report']) ? preg_replace('/[^a-z_]/', '', $_GET['report']) : '';
        $from   = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d');
        $to     = isset($_GET['to'])   ? $_GET['to']   : date('Y-m-d');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $report . '_' . $from . '_to_' . $to . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        if ($report === 'daily_sales') {
            fputcsv($out, ['#','Date','Bill No','Customer','Items','Gross Amt','Discount','VAT','Net Amt','Payment Mode','Received','Balance']);
            $i = 1;
            $qry = $conn->query("
                SELECT t.id, t.transaction_date, t.reference_code, t.total_amount, t.discount, COALESCE(c.display_name,'Walk-in') as customer_name,
                       (SELECT COUNT(*) FROM transaction_items WHERE transaction_id = t.id) as item_count,
                       COALESCE((SELECT SUM(p.total_amount) FROM transactions p WHERE p.parent_id = t.id AND p.type = 'payment'), 0) as paid_amount,
                       COALESCE((SELECT a.name FROM transactions p JOIN account_list a ON p.account_id = a.id WHERE p.parent_id = t.id AND p.type = 'payment' ORDER BY p.id DESC LIMIT 1), 'Credit') as payment_method
                FROM transactions t
                LEFT JOIN entity_list c ON t.entity_id = c.id AND c.entity_type = 'Customer'
                WHERE t.type = 'sale' AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'
                ORDER BY t.transaction_date DESC
            ");
            while ($r = $qry->fetch_assoc()) {
                $net  = $r['total_amount'];
                $disc = $r['discount'] ?? 0;
                $vat  = 0;
                $gross = $net + $disc;
                fputcsv($out, [$i++, date('d-m-Y',strtotime($r['transaction_date'])), $r['reference_code'], $r['customer_name'],
                    $r['item_count'], number_format($gross,2), number_format($disc,2), number_format($vat,2),
                    number_format($net,2), $r['payment_method'] ?? '', number_format($r['paid_amount'],2), number_format(max(0,$r['total_amount']-$r['paid_amount']),2)]);
            }

        } elseif ($report === 'pos_sales') {
            fputcsv($out, ['#','Date','Bill No','Customer','Items','Gross Amt','Discount','Net Amt','Payment Mode','Received','Balance']);
            $i = 1;
            $qry = $conn->query("
                SELECT t.id, t.transaction_date, t.reference_code, t.total_amount, t.discount, COALESCE(c.display_name,'Walk-in') as customer_name,
                       (SELECT COUNT(*) FROM transaction_items WHERE transaction_id = t.id) as item_count,
                       COALESCE((SELECT SUM(p.total_amount) FROM transactions p WHERE p.parent_id = t.id AND p.type = 'payment'), 0) as paid_amount,
                       COALESCE((SELECT a.name FROM transactions p JOIN account_list a ON p.account_id = a.id WHERE p.parent_id = t.id AND p.type = 'payment' ORDER BY p.id DESC LIMIT 1), 'Cash') as payment_method
                FROM transactions t
                LEFT JOIN entity_list c ON t.entity_id = c.id AND c.entity_type = 'Customer'
                WHERE t.type = 'sale' AND t.remarks LIKE '%[POS]%' AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'
                ORDER BY t.transaction_date DESC
            ");
            while ($r = $qry->fetch_assoc()) {
                $net  = $r['total_amount'];
                $disc = $r['discount'] ?? 0;
                $gross = $net + $disc;
                fputcsv($out, [$i++, date('d-m-Y',strtotime($r['transaction_date'])), $r['reference_code'], $r['customer_name'],
                    $r['item_count'], number_format($gross,2), number_format($disc,2),
                    number_format($net,2), $r['payment_method'] ?? 'Cash', number_format($r['paid_amount'],2), number_format(max(0,$r['total_amount']-$r['paid_amount']),2)]);
            }

        } elseif ($report === 'daily_purchase') {
            fputcsv($out, ['#','Date','Bill No','Vendor','Total Amt','VAT Input','Paid','Pending','Status']);
            $i = 1;
            $qry = $conn->query("
                SELECT t.id, t.transaction_date, t.reference_code as po_code, t.total_amount as amount, v.display_name as vendor,
                       COALESCE((SELECT SUM(p.total_amount) FROM transactions p WHERE p.parent_id = t.id AND p.type = 'payment'), 0) as paid_amount
                FROM transactions t
                LEFT JOIN entity_list v ON t.entity_id = v.id AND v.entity_type = 'Supplier'
                WHERE t.type = 'purchase' AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'
                ORDER BY t.transaction_date DESC
            ");
            while ($r = $qry->fetch_assoc()) {
                $balance = max(0, $r['amount'] - $r['paid_amount']);
                $status = ($balance <= 0) ? 'Paid' : (($balance < $r['amount']) ? 'Partial' : 'Unpaid');
                fputcsv($out, [$i++, date('d-m-Y', strtotime($r['transaction_date'])), $r['po_code'], $r['vendor'],
                    number_format($r['amount'], 2), number_format(0, 2), number_format($r['paid_amount'], 2),
                    number_format($balance, 2), $status]);
            }

        } elseif ($report === 'daily_expense') {
            fputcsv($out, ['#','Date','Category','Payment Mode','Amount','Remarks']);
            $i = 1;
            $qry = $conn->query("
                SELECT t.transaction_date, t.total_amount as amount, t.remarks, a.name as payment_method, 'Expense' as category
                FROM transactions t
                LEFT JOIN account_list a ON t.account_id = a.id
                WHERE t.type = 'expense' AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'
                ORDER BY t.transaction_date DESC
            ");
            while ($r = $qry->fetch_assoc()) {
                fputcsv($out,[$i++,date('d-m-Y',strtotime($r['transaction_date'])),$r['category'],$r['payment_method'],number_format($r['amount'],2),$r['remarks']]);
            }

        } elseif ($report === 'stock_current') {
            fputcsv($out,['#','Item','Category','Purchase Qty','Sales Qty','Current Stock','Avg Cost','Selling Price','Stock Value']);
            $i=1;
            $qry=$conn->query("SELECT i.name, c.name as cat,
                  (SELECT COALESCE(SUM(ti.quantity), 0) FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE ti.item_id = i.id AND t.type = 'purchase') as pur_qty,
                  (SELECT COALESCE(SUM(ti.quantity), 0) FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE ti.item_id = i.id AND t.type = 'sale') as sal_qty,
                  (SELECT COALESCE(SUM(CASE 
                                    WHEN t.type IN ('purchase', 'opening_stock') THEN ti.quantity 
                                    WHEN t.type = 'sale' THEN -ti.quantity 
                                    WHEN t.type = 'adjustment' THEN ti.quantity 
                                    WHEN t.type = 'return' THEN (CASE WHEN t.entity_id IN (SELECT id FROM entity_list WHERE entity_type='Supplier') THEN -ti.quantity ELSE ti.quantity END)
                                    ELSE 0 END), 0) 
                   FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id 
                   WHERE ti.item_id = i.id) as cur_stock,
                  (SELECT COALESCE(AVG(ti.unit_price), 0) FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE ti.item_id = i.id AND t.type = 'purchase' AND ti.unit_price > 0) as avg_cost,
                  i.price as sell_price
                  FROM item_list i LEFT JOIN category_list c ON i.category_id=c.id WHERE i.status=1 ORDER BY i.name");
            while($r=$qry->fetch_assoc()){
                $val = $r['cur_stock'] * $r['avg_cost'];
                fputcsv($out,[$i++,$r['name'],$r['cat']??'N/A',number_format($r['pur_qty'],2),number_format($r['sal_qty'],2),
                    number_format($r['cur_stock'],2),number_format($r['avg_cost'],2),number_format($r['sell_price'],2),number_format($val,2)]);
            }

        } elseif ($report === 'customer_outstanding') {
            $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
            $status_f    = isset($_GET['status_f']) ? $_GET['status_f'] : 'outstanding';
            $and_cust   = $customer_id ? " AND t.entity_id={$customer_id}" : '';
            $and_date   = " AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'";
            $and_status = '';
            if ($status_f === 'outstanding') $and_status = " HAVING bal > 0";
            elseif ($status_f === 'paid')    $and_status = " HAVING bal <= 0";

            fputcsv($out,['#','Date','Invoice No','Customer','Total Amount','Received','Balance Due','Status']);
            $i=1;
            $qry=$conn->query("
                SELECT t.transaction_date, t.reference_code, COALESCE(c.display_name,'Walk-in') as cname, t.total_amount as ts, 
                (SELECT COALESCE(SUM(p.total_amount),0) FROM transactions p WHERE p.parent_id = t.id AND p.type='payment') as tr, 
                (t.total_amount - (SELECT COALESCE(SUM(p.total_amount),0) FROM transactions p WHERE p.parent_id = t.id AND p.type='payment')) as bal 
                FROM transactions t LEFT JOIN entity_list c ON t.entity_id=c.id AND c.entity_type = 'Customer'
                WHERE t.type='sale' {$and_cust} {$and_date} 
                {$and_status} 
                ORDER BY t.transaction_date ASC
            ");
            while($r=$qry->fetch_assoc()){
                $status = ($r['bal'] <= 0) ? 'Paid' : (($r['tr'] > 0) ? 'Partial' : 'Unpaid');
                fputcsv($out,[$i++,date('d-m-Y',strtotime($r['transaction_date'])),$r['reference_code'],$r['cname'],number_format($r['ts'],2),number_format($r['tr'],2),number_format($r['bal'],2),$status]);
            }

        } elseif ($report === 'vendor_outstanding') {
            $vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
            $status_f    = isset($_GET['status_f']) ? $_GET['status_f'] : 'outstanding';
            $and_vendor = $vendor_id ? " AND t.entity_id={$vendor_id}" : '';
            $and_date   = " AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}'";
            $and_status = '';
            if ($status_f === 'outstanding') $and_status = " HAVING bal > 0";
            elseif ($status_f === 'paid')    $and_status = " HAVING bal <= 0";

            fputcsv($out,['#','Date','Bill No.','Vendor','Total Amount','Paid','Pending Amount','Status']);
            $i=1;
            $qry=$conn->query("
                SELECT t.transaction_date, t.reference_code, COALESCE(v.display_name,'Unknown') as vname, t.total_amount as tp, 
                (SELECT COALESCE(SUM(p.total_amount),0) FROM transactions p WHERE p.parent_id = t.id AND p.type='payment') as tpd, 
                (t.total_amount - (SELECT COALESCE(SUM(p.total_amount),0) FROM transactions p WHERE p.parent_id = t.id AND p.type='payment')) as bal 
                FROM transactions t LEFT JOIN entity_list v ON t.entity_id=v.id AND v.entity_type = 'Supplier'
                WHERE t.type='purchase' {$and_vendor} {$and_date} 
                {$and_status} 
                ORDER BY t.transaction_date ASC
            ");
            while($r=$qry->fetch_assoc()){
                $status = ($r['bal'] <= 0) ? 'Paid' : (($r['tpd'] > 0) ? 'Partial' : 'Unpaid');
                fputcsv($out,[$i++,date('d-m-Y',strtotime($r['transaction_date'])),$r['reference_code'],$r['vname'],number_format($r['tp'],2),number_format($r['tpd'],2),number_format($r['bal'],2),$status]);
            }

        } elseif ($report === 'vat_sales') {
            fputcsv($out,['#','Date','Bill No','Customer','Taxable Amt','VAT Rate','VAT Amt','Total']);
            $i=1;
            $qry=$conn->query("SELECT t.id, t.transaction_date, t.reference_code, t.total_amount, t.tax, COALESCE(c.display_name,'Walk-in') as cname FROM transactions t LEFT JOIN entity_list c ON t.entity_id=c.id AND c.entity_type = 'Customer' WHERE t.type='sale' AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}' ORDER BY t.transaction_date DESC");
            while($r=$qry->fetch_assoc()){
                $taxable=(float)$r['total_amount']-(float)$r['tax'];
                $rate=($taxable>0?round(((float)$r['tax']/$taxable)*100,2):0);
                fputcsv($out,[$i++,date('d-m-Y',strtotime($r['transaction_date'])),$r['reference_code'],$r['cname'],
                    number_format($taxable,2),$rate.'%',number_format($r['tax'],2),number_format($r['total_amount'],2)]);
            }

        } elseif ($report === 'vat_purchase') {
            fputcsv($out,['#','Date','Bill No','Vendor','Taxable Amt','Input VAT','Total']);
            $i=1;
            $qry=$conn->query("SELECT t.id, t.transaction_date, t.reference_code, t.total_amount, t.tax, v.display_name as vname FROM transactions t LEFT JOIN entity_list v ON t.entity_id=v.id AND v.entity_type = 'Supplier' WHERE t.type='purchase' AND DATE(t.transaction_date) BETWEEN '{$from}' AND '{$to}' ORDER BY t.transaction_date DESC");
            while($r=$qry->fetch_assoc()){
                $taxable=(float)$r['total_amount']-(float)$r['tax'];
                fputcsv($out,[$i++,date('d-m-Y',strtotime($r['transaction_date'])),$r['reference_code'],$r['vname'],
                    number_format($taxable,2),number_format($r['tax'],2),number_format($r['total_amount'],2)]);
            }
        }

        fclose($out);
        exit;

    default:
        echo json_encode(['error' => 'Unknown action']);
        break;
}
