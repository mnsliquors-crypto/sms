<?php
include '../../classes/DBConnection.php';
include '../../classes/Login.php';

if(!isset($_SESSION['userdata'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$type = $_GET['type'] ?? null;
$db = new DBConnection();

if(!$type) {
    exit('Invalid export type');
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $type . '_' . date('Y-m-d_His') . '.csv');

$output = fopen('php://output', 'w');

try {
    if($type === 'items') {
        fputcsv($output, ['Date Created', 'Name', 'Description', 'Vendor', 'Cost', 'Status']);
        $query = "SELECT i.date_created, i.name, i.description, s.display_name as vendor, i.cost, 
                         CASE WHEN i.status=1 THEN 'Active' ELSE 'Inactive' END as status 
                  FROM item_list i 
                  INNER JOIN entity_list s ON i.vendor_id = s.id 
                  ORDER BY i.name ASC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    else if($type === 'vendors') {
        fputcsv($output, ['Date Created', 'Name', 'Address', 'Contact Person', 'Contact', 'Status']);
        $query = "SELECT date_created, display_name as name, address, cperson, contact, 
                         CASE WHEN status=1 THEN 'Active' ELSE 'Inactive' END as status 
                  FROM entity_list 
                  WHERE entity_type = 'Supplier'
                  ORDER BY display_name ASC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    else if($type === 'customers') {
        fputcsv($output, ['Date Created', 'Name', 'Address', 'Contact', 'Status']);
        $query = "SELECT date_created, display_name as name, address, contact, 
                         CASE WHEN status=1 THEN 'Active' ELSE 'Inactive' END as status 
                  FROM entity_list 
                  WHERE entity_type = 'Customer'
                  ORDER BY display_name ASC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    else if($type === 'sales') {
        fputcsv($output, ['Date Created', 'Sales Code', 'Client', 'Amount', 'Remarks']);
        $query = "SELECT t.transaction_date, t.reference_code, COALESCE(c.display_name, 'Walk-in') as client, t.total_amount, t.remarks 
                  FROM transactions t 
                  LEFT JOIN entity_list c ON t.entity_id = c.id AND c.entity_type = 'Customer'
                  WHERE t.type = 'sale'
                  ORDER BY t.transaction_date DESC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    else if($type === 'purchases') {
        fputcsv($output, ['Date Created', 'Bill Code', 'Vendor', 'Amount', 'Status']);
        $query = "SELECT t.transaction_date, t.reference_code, s.display_name as vendor, t.total_amount, 
                         'N/A' as status 
                  FROM transactions t 
                  INNER JOIN entity_list s ON t.entity_id = s.id AND s.entity_type = 'Supplier'
                  WHERE t.type = 'purchase'
                  ORDER BY t.transaction_date DESC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    else if($type === 'stocks') {
        fputcsv($output, ['Date Created', 'Item', 'Quantity', 'Unit', 'Price', 'Total', 'Type']);
        $query = "SELECT t.transaction_date, i.name as item, ti.quantity, ti.unit, ti.unit_price, ti.total_price, 
                         CASE WHEN t.type='purchase' THEN 'IN' ELSE 'OUT' END as type 
                  FROM transaction_items ti 
                  INNER JOIN transactions t ON ti.transaction_id = t.id
                  INNER JOIN item_list i ON ti.item_id = i.id 
                  ORDER BY t.transaction_date DESC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    else if($type === 'payments') {
        fputcsv($output, ['Date Created', 'Reference Code', 'Parent Ref', 'Amount', 'Account', 'Remarks']);
        $query = "SELECT t.transaction_date, t.reference_code, (SELECT reference_code FROM transactions WHERE id=t.parent_id) as parent_ref, t.total_amount, a.name as account, t.remarks 
                  FROM transactions t
                  JOIN account_list a ON t.account_id = a.id
                  WHERE t.type = 'payment'
                  ORDER BY t.transaction_date DESC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    else if($type === 'users') {
        fputcsv($output, ['Username', 'First Name', 'Middle Name', 'Last Name', 'Type']);
        $query = "SELECT username, firstname, middlename, lastname, 
                         CASE WHEN type=1 THEN 'Admin' ELSE 'User' END as type 
                  FROM users 
                  ORDER BY firstname ASC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    else if($type === 'categories') {
        fputcsv($output, ['Date Created', 'Name', 'Description', 'Status']);
        $query = "SELECT date_created, name, description, 
                         CASE WHEN status=1 THEN 'Active' ELSE 'Inactive' END as status 
                  FROM category_list 
                  ORDER BY name ASC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    else if($type === 'accounts') {
        fputcsv($output, ['Date Created', 'Account Name', 'Account Code', 'Account Type', 'Balance', 'Status']);
        $query = "SELECT date_created, name as account_name, '' as account_code, description as account_type, balance, 
                         CASE WHEN status=1 THEN 'Active' ELSE 'Inactive' END as status 
                  FROM account_list 
                  ORDER BY name ASC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    else if($type === 'expenses') {
        fputcsv($output, ['Date Created', 'Account', 'Description', 'Amount']);
        $query = "SELECT t.transaction_date, a.name as account, t.remarks as description, t.total_amount 
                  FROM transactions t
                  JOIN account_list a ON t.account_id = a.id
                  WHERE t.type = 'expense'
                  ORDER BY t.transaction_date DESC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    else if($type === 'transfers') {
        fputcsv($output, ['Date Created', 'Code', 'From Account', 'To Account', 'Amount', 'Remarks']);
        $query = "SELECT t.transaction_date, t.reference_code,
                         (SELECT a.name FROM transaction_list tl JOIN account_list a ON tl.account_id = a.id WHERE tl.ref_table = 'transactions' AND tl.ref_id = t.id AND tl.type = 2 LIMIT 1) as from_account,
                         (SELECT a.name FROM transaction_list tl JOIN account_list a ON tl.account_id = a.id WHERE tl.ref_table = 'transactions' AND tl.ref_id = t.id AND tl.type = 1 LIMIT 1) as to_account,
                         t.total_amount, t.remarks 
                  FROM transactions t
                  WHERE t.type = 'transfer'
                  ORDER BY t.transaction_date DESC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    else if($type === 'opening_balances') {
        fputcsv($output, ['Date Created', 'Entity Type', 'Entity Name', 'Reference', 'Amount']);
        $query = "SELECT t.transaction_date, 
                         CASE WHEN t.type = 'opening_balance' THEN 'Account' ELSE 'Stock' END as entity_type,
                         COALESCE(a.name, i.name) as entity_name,
                         t.reference_code, t.total_amount
                  FROM transactions t
                  LEFT JOIN account_list a ON t.account_id = a.id
                  LEFT JOIN transaction_items ti ON t.id = ti.transaction_id
                  LEFT JOIN item_list i ON ti.item_id = i.id
                  WHERE t.type IN ('opening_balance', 'opening_stock')
                  ORDER BY t.transaction_date DESC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    else if($type === 'returns') {
        fputcsv($output, ['Date', 'Return Code', 'Entity', 'Total Amount', 'Remarks']);
        $query = "SELECT t.transaction_date, t.reference_code, COALESCE(v.display_name, c.display_name, 'N/A') as entity, t.total_amount, t.remarks 
                FROM transactions t 
                LEFT JOIN entity_list v ON t.entity_id = v.id AND v.entity_type = 'Supplier' 
                LEFT JOIN entity_list c ON t.entity_id = c.id AND c.entity_type = 'Customer' 
                WHERE t.type = 'return'
                ORDER BY t.transaction_date DESC";
        $result = $db->conn->query($query);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    
} catch(Exception $e) {
    fputcsv($output, ['Error: ' . $e->getMessage()]);
}

fclose($output);
?>
