<?php

class SearchService {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Perform global search across all groups
     */
    public function search($query, $limit_per_group = 5) {
        $q = trim($query);
        if (strlen($q) < 2) return [];

        $results = [
            'customers' => [],
            'vendors' => [],
            'items' => [],
            'transactions' => [],
            'users' => [],
            'reports' => []
        ];

        // 1. Search Database Index
        $db_results = $this->searchDatabase($q);
        
        foreach ($db_results as $row) {
            $type = $row['type'];
            $group = $this->mapTypeToGroup($type);
            
            if (isset($results[$group]) && count($results[$group]) < $limit_per_group) {
                $row['title_highlighted'] = $this->highlight($row['title'], $q);
                $row['subtitle_highlighted'] = $this->highlight($row['subtitle'], $q);
                $results[$group][] = $row;
            }
        }

        // 2. Search Static Reports (if query matches report types)
        $results['reports'] = $this->searchReports($q, $limit_per_group);

        // Remove empty groups
        return array_filter($results);
    }

    /**
     * Database search using ranking logic
     */
    private function searchDatabase($q) {
        $like_start = "$q%";
        $like_any = "%$q%";
        $boolean_q = (strpos($q, ' ') !== false || strpos($q, '-') !== false) ? '"' . $q . '"' : "$q*";

        $sql = "SELECT source_id, title, subtitle, amount, type, view_url, edit_url, transaction_date,
                (CASE 
                    WHEN title = ? THEN 1000 
                    WHEN title LIKE ? THEN 500
                    ELSE 0 
                 END) + (MATCH(title, subtitle, search_data) AGAINST(? IN BOOLEAN MODE) * 10) as score 
                FROM `search_index` 
                WHERE title = ? 
                OR title LIKE ? 
                OR MATCH(title, subtitle, search_data) AGAINST(? IN BOOLEAN MODE)
                OR subtitle LIKE ?
                ORDER BY score DESC, transaction_date DESC 
                LIMIT 50";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssss", $q, $like_start, $boolean_q, $q, $like_start, $boolean_q, $like_any);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        while($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Simple static report search
     */
    private function searchReports($q, $limit) {
        $reports = [
            ['title' => 'Sales Report', 'url' => 'reports/sales', 'keywords' => 'sales invoices revenue'],
            ['title' => 'Purchase Report', 'url' => 'reports/purchase', 'keywords' => 'purchase bills vendors'],
            ['title' => 'Profit & Loss Statement', 'url' => 'reports/profit', 'keywords' => 'profit loss income expense'],
            ['title' => 'Stock Report', 'url' => 'reports/stock', 'keywords' => 'inventory stock items ledger'],
            ['title' => 'Customer Outstanding', 'url' => 'reports/customer_outstanding', 'keywords' => 'customer balance due receivable'],
            ['title' => 'Vendor Outstanding', 'url' => 'reports/vendor_outstanding', 'keywords' => 'vendor balance due payable'],
            ['title' => 'Expense Summary', 'url' => 'reports/daily_expense', 'keywords' => 'expense spending cashout'],
            ['title' => 'VAT Summary', 'url' => 'reports/vat_summary', 'keywords' => 'tax vat compliance output input']
        ];

        $matched = [];
        $q_lower = strtolower($q);

        foreach ($reports as $r) {
            if (strpos(strtolower($r['title']), $q_lower) !== false || strpos(strtolower($r['keywords']), $q_lower) !== false) {
                $matched[] = [
                    'source_id' => 0,
                    'title' => $r['title'],
                    'title_highlighted' => $this->highlight($r['title'], $q),
                    'subtitle' => 'System Report',
                    'subtitle_highlighted' => 'System Report',
                    'type' => 'report',
                    'view_url' => $r['url'],
                    'edit_url' => $r['url']
                ];
            }
            if (count($matched) >= $limit) break;
        }
        return $matched;
    }

    private function mapTypeToGroup($type) {
        $map = [
            'customer' => 'customers',
            'vendor' => 'vendors',
            'item' => 'items',
            'user' => 'users'
        ];
        if (in_array($type, ['sale', 'purchase', 'expense', 'payment', 'return', 'adjustment', 'transfer'])) {
            return 'transactions';
        }
        return isset($map[$type]) ? $map[$type] : 'others';
    }

    private function highlight($text, $query) {
        if (!$text) return '';
        $query = preg_quote($query, '/');
        return preg_replace("/($query)/i", "<b>$1</b>", $text);
    }
}
