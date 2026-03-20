<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if(!isset($_SESSION['userdata'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file']) || !isset($_POST['type'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid request']);
    exit;
}

$type = $_POST['type'];
$file = $_FILES['file'];
$db = new DBConnection();

// Validate file
if($file['error'] !== UPLOAD_ERR_OK || $file['size'] === 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid file uploaded']);
    exit;
}

// Read CSV file
$handle = fopen($file['tmp_name'], 'r');
if(!$handle) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Unable to read file']);
    exit;
}

$headers = fgetcsv($handle);
// normalize headers: trim, remove BOM, lowercase
if($headers){
    foreach($headers as &$h){
        $h = trim($h);
        // remove BOM if present
        $h = preg_replace('/\x{FEFF}/u', '', $h);
        $h = strtolower($h);
    }
    unset($h);
}
$imported = 0;
$errors = [];
$import_id = isset($_POST['import_id']) ? $_POST['import_id'] : uniqid();
$progress_file = __DIR__ . "/../../uploads/progress_{$import_id}.json";

function update_progress($file, $current, $total) {
    $progress = ($total > 0) ? round(($current / $total) * 100) : 0;
    file_put_contents($file, json_encode(['progress' => $progress, 'current' => $current, 'total' => $total]));
}

// Count total rows for progress tracking
$total_rows = 0;
$count_handle = fopen($file['tmp_name'], 'r');
while(fgetcsv($count_handle) !== false) {
    $total_rows++;
}
fclose($count_handle);
$total_rows = max(0, $total_rows - 1); // exclude header

update_progress($progress_file, 0, $total_rows);
$current_row = 0;

try {
    if($type === 'items') {
        $expected = ['name','description','category','supplier','unit','cost','selling_price','reorder_level','tax_type','status'];
        if(array_values($headers) !== $expected) {
            throw new Exception('CSV headers do not match expected format: expected '.implode(',', $expected));
        }

        if(isset($_POST['validate_only']) && $_POST['validate_only'] == 1) {
            echo json_encode(['status' => 'success', 'message' => 'File validated successfully. Starting import...']);
            fclose($handle);
            exit;
        }
        
        while(($row = fgetcsv($handle)) !== false) {
            $current_row++;
            update_progress($progress_file, $current_row, $total_rows);
            
            if(empty(array_filter($row))) continue;
            
            $data = array_combine($headers, $row);
            
            if(empty($data['name']) || empty($data['cost'])) {
                $errors[] = "Row {$current_row} skipped: Missing required fields (name, cost)";
                continue;
            }

            // Check duplicate: item with same name already exists
            $dup_chk = $db->conn->prepare("SELECT id FROM item_list WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $dup_chk->bind_param("s", $data['name']);
            $dup_chk->execute();
            $dup_chk->store_result();
            if($dup_chk->num_rows > 0) {
                $errors[] = "Row {$current_row} skipped: Item '{$data['name']}' already exists";
                $dup_chk->close();
                continue;
            }
            $dup_chk->close();

            // Resolve supplier: accept name or numeric ID (optional)
            $supplier_val = trim($data['supplier']);
            $resolved_supplier_id = null;
            if(!empty($supplier_val)) {
                if(is_numeric($supplier_val)) {
                    $chk = $db->conn->prepare("SELECT id FROM entity_list WHERE id = ? AND entity_type = 'Supplier' LIMIT 1");
                    $chk->bind_param("i", $supplier_val);
                    $chk->execute(); $chk->store_result();
                    if($chk->num_rows > 0) $resolved_supplier_id = (int)$supplier_val;
                    $chk->close();
                }
                if($resolved_supplier_id === null) {
                    $chk = $db->conn->prepare("SELECT id FROM entity_list WHERE LOWER(display_name) = LOWER(?) AND entity_type = 'Supplier' LIMIT 1");
                    $chk->bind_param("s", $supplier_val);
                    $chk->execute(); $chk->bind_result($found_id);
                    if($chk->fetch()) $resolved_supplier_id = (int)$found_id;
                    $chk->close();
                }
                if($resolved_supplier_id === null) {
                    $errors[] = "Row {$current_row} skipped: Supplier '{$supplier_val}' not found for item '{$data['name']}'";
                    continue;
                }
            }

            // Resolve category: accept name or numeric ID (optional)
            $category_val = trim($data['category']);
            $resolved_category_id = null;
            if(!empty($category_val)) {
                if(is_numeric($category_val)) {
                    $chk = $db->conn->prepare("SELECT id FROM category_list WHERE id = ? LIMIT 1");
                    $chk->bind_param("i", $category_val);
                    $chk->execute(); $chk->store_result();
                    if($chk->num_rows > 0) $resolved_category_id = (int)$category_val;
                    $chk->close();
                }
                if($resolved_category_id === null) {
                    $chk = $db->conn->prepare("SELECT id FROM category_list WHERE LOWER(name) = LOWER(?) LIMIT 1");
                    $chk->bind_param("s", $category_val);
                    $chk->execute(); $chk->bind_result($found_id);
                    if($chk->fetch()) $resolved_category_id = (int)$found_id;
                    $chk->close();
                }
                if($resolved_category_id === null) {
                    $errors[] = "Row {$current_row} skipped: Category '{$category_val}' not found for item '{$data['name']}'";
                    continue;
                }
            }

            $cost          = floatval($data['cost']);
            $selling_price = isset($data['selling_price']) && is_numeric($data['selling_price']) ? floatval($data['selling_price']) : $cost;
            $opening_cost  = $cost;
            $average_cost  = $cost;
            $unit          = trim($data['unit'] ?? '');
            $reorder_level = isset($data['reorder_level']) && is_numeric($data['reorder_level']) ? floatval($data['reorder_level']) : 0;
            $tax_type      = isset($data['tax_type']) && is_numeric($data['tax_type']) ? (int)$data['tax_type'] : 1;
            $status        = isset($data['status']) && is_numeric($data['status']) ? (int)$data['status'] : 1;
            $description   = trim($data['description'] ?? '');
            
            $query = "INSERT INTO item_list 
                        (name, description, category_id, vendor_id, unit, cost, selling_price, opening_cost, average_cost, reorder_level, tax_type, status, date_created) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $db->conn->prepare($query);
            $stmt->bind_param(
                "ssiiidddddii",
                $data['name'], $description, $resolved_category_id, $resolved_supplier_id,
                $unit, $cost, $selling_price, $opening_cost, $average_cost,
                $reorder_level, $tax_type, $status
            );
            
            if($stmt->execute()) {
                $imported++;
            } else {
                $errors[] = "Row {$current_row} error importing '{$data['name']}': " . $stmt->error;
            }
            $stmt->close();
        }
    }
    else if($type === 'suppliers') {
        $expected = ['name','address','cperson','contact','status'];
        if(array_values($headers) !== $expected) {
            throw new Exception('CSV headers do not match expected format: expected '.implode(',', $expected));
        }

        if(isset($_POST['validate_only']) && $_POST['validate_only'] == 1) {
            echo json_encode(['status' => 'success', 'message' => 'File validated successfully. Starting import...']);
            fclose($handle);
            exit;
        }
        
        while(($row = fgetcsv($handle)) !== false) {
            $current_row++;
            update_progress($progress_file, $current_row, $total_rows);
            
            if(empty(array_filter($row))) continue;
            
            $data = array_combine($headers, $row);
            
            if(empty($data['name']) || empty($data['address']) || empty($data['cperson']) || empty($data['contact'])) {
                $errors[] = "Row skipped: Missing required fields";
                continue;
            }
            
            $status = isset($data['status']) ? (int)$data['status'] : 1;

            // Check duplicate: supplier with same name already exists
            $dup_chk = $db->conn->prepare("SELECT id FROM entity_list WHERE LOWER(display_name) = LOWER(?) AND entity_type = 'Supplier' LIMIT 1");
            $dup_chk->bind_param("s", $data['name']);
            $dup_chk->execute();
            $dup_chk->store_result();
            if($dup_chk->num_rows > 0) {
                $errors[] = "Row {$current_row} skipped: Supplier '{$data['name']}' already exists";
                $dup_chk->close();
                continue;
            }
            $dup_chk->close();
            
            $query = "INSERT INTO entity_list (display_name, address, cperson, contact, status, entity_type, date_created) 
                     VALUES (?, ?, ?, ?, ?, 'Supplier', NOW())";
            $stmt = $db->conn->prepare($query);
            $stmt->bind_param("ssssi", $data['name'], $data['address'], $data['cperson'], $data['contact'], $status);
            
            if($stmt->execute()) {
                $imported++;
            } else {
                $errors[] = "Error importing: " . $data['name'];
            }
            $stmt->close();
        }
    }
    else if($type === 'customers') {
        $expected = ['name','address','contact','email','status'];
        if(array_values($headers) !== $expected) {
            throw new Exception('CSV headers do not match expected format: expected '.implode(',', $expected));
        }

        if(isset($_POST['validate_only']) && $_POST['validate_only'] == 1) {
            echo json_encode(['status' => 'success', 'message' => 'File validated successfully. Starting import...']);
            fclose($handle);
            exit;
        }

        // Ensure entity_list table exists (it should, as part of the core migration)
        $checkTable = "SHOW TABLES LIKE 'entity_list'";
        $result = $db->conn->query($checkTable);
        if($result === false) $result = (object)['num_rows' => 0];
        if($result->num_rows === 0) {
            throw new Exception("entity_list table is missing. System integrity failure.");
        }
        
        while(($row = fgetcsv($handle)) !== false) {
            $current_row++;
            update_progress($progress_file, $current_row, $total_rows);
            if(empty(array_filter($row))) continue;
            
            $data = array_combine($headers, $row);
            
            if(empty($data['name']) || empty($data['address']) || empty($data['contact'])) {
                $errors[] = "Row skipped: Missing required fields";
                continue;
            }
            
            $status = isset($data['status']) ? (int)$data['status'] : 1;
            $email = isset($data['email']) ? $data['email'] : null;

            // Check duplicate: customer with same name already exists
            $dup_chk = $db->conn->prepare("SELECT id FROM entity_list WHERE LOWER(display_name) = LOWER(?) AND entity_type = 'Customer' LIMIT 1");
            $dup_chk->bind_param("s", $data['name']);
            $dup_chk->execute();
            $dup_chk->store_result();
            if($dup_chk->num_rows > 0) {
                $errors[] = "Row {$current_row} skipped: Customer '{$data['name']}' already exists";
                $dup_chk->close();
                continue;
            }
            $dup_chk->close();
            
            $query = "INSERT INTO entity_list (display_name, address, contact, status, entity_type, date_created) 
                     VALUES (?, ?, ?, ?, 'Customer', NOW())";
            $stmt = $db->conn->prepare($query);
            $stmt->bind_param("sssi", $data['name'], $data['address'], $data['contact'], $status);
            
            if($stmt->execute()) {
                $imported++;
            } else {
                $errors[] = "Error importing: " . $data['name'];
            }
            $stmt->close();
        }
    }
    else if($type === 'categories') {
        $expected = ['name','description','status'];
        if(array_values($headers) !== $expected) {
            throw new Exception('CSV headers do not match expected format: expected '.implode(',', $expected));
        }

        if(isset($_POST['validate_only']) && $_POST['validate_only'] == 1) {
            echo json_encode(['status' => 'success', 'message' => 'File validated successfully. Starting import...']);
            fclose($handle);
            exit;
        }

        // Ensure `category_list` table exists
        $checkTable = "SHOW TABLES LIKE 'category_list'";
        $result = $db->conn->query($checkTable);
        if($result === false) $result = (object)['num_rows' => 0];
        if($result->num_rows === 0) {
            $createTable = "CREATE TABLE category_list (
                id INT(30) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(250) NOT NULL,
                description TEXT DEFAULT NULL,
                status TINYINT(1) NOT NULL DEFAULT 1,
                date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                date_updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            if(!$db->conn->query($createTable)) {
                throw new Exception("Unable to create category_list table");
            }
        }
        
        while(($row = fgetcsv($handle)) !== false) {
            $current_row++;
            update_progress($progress_file, $current_row, $total_rows);
            if(empty(array_filter($row))) continue;
            
            $data = array_combine($headers, $row);
            
            if(empty($data['name'])) {
                $errors[] = "Row skipped: Missing category name";
                continue;
            }
            
            $status = isset($data['status']) ? (int)$data['status'] : 1;
            $description = isset($data['description']) ? $data['description'] : null;

            // Check duplicate: category with same name already exists
            $dup_chk = $db->conn->prepare("SELECT id FROM category_list WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $dup_chk->bind_param("s", $data['name']);
            $dup_chk->execute();
            $dup_chk->store_result();
            if($dup_chk->num_rows > 0) {
                $errors[] = "Row {$current_row} skipped: Category '{$data['name']}' already exists";
                $dup_chk->close();
                continue;
            }
            $dup_chk->close();
            
            // insert into category_list
            $query = "INSERT INTO category_list (name, description, status, date_created) 
                     VALUES (?, ?, ?, NOW())";
            $stmt = $db->conn->prepare($query);
            $stmt->bind_param("ssi", $data['name'], $description, $status);
            
            if($stmt->execute()) {
                $imported++;
            } else {
                $errors[] = "Error importing: " . $data['name'];
            }
            $stmt->close();
        }
    }
    else if($type === 'accounts') {
        $expected = ['account_name','account_code','account_type','balance','status'];
        if(array_values($headers) !== $expected) {
            throw new Exception('CSV headers do not match expected format: expected '.implode(',', $expected));
        }

        if(isset($_POST['validate_only']) && $_POST['validate_only'] == 1) {
            echo json_encode(['status' => 'success', 'message' => 'File validated successfully. Starting import...']);
            fclose($handle);
            exit;
        }

        // Ensure `account_list` table exists
        $checkTable = "SHOW TABLES LIKE 'account_list'";
        $result = $db->conn->query($checkTable);
        if($result === false) $result = (object)['num_rows' => 0];
        if($result->num_rows === 0) {
            $createTable = "CREATE TABLE account_list (
                id INT(30) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(250) NOT NULL,
                description TEXT DEFAULT NULL,
                type TINYINT(1) NOT NULL DEFAULT 1,
                balance FLOAT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 1,
                date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                date_updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            if(!$db->conn->query($createTable)) {
                throw new Exception("Unable to create account_list table");
            }
        }
        while(($row = fgetcsv($handle)) !== false) {
            $current_row++;
            update_progress($progress_file, $current_row, $total_rows);
            if(empty(array_filter($row))) continue;
            
            $data = array_combine($headers, $row);
            
            if(empty($data['account_name']) || empty($data['account_code']) || empty($data['account_type'])) {
                $errors[] = "Row skipped: Missing required fields";
                continue;
            }
            
            $balance = isset($data['balance']) ? (float)$data['balance'] : 0;
            $status = isset($data['status']) ? (int)$data['status'] : 1;

            // Check duplicate: account with same name already exists
            $dup_chk = $db->conn->prepare("SELECT id FROM account_list WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $dup_chk->bind_param("s", $data['account_name']);
            $dup_chk->execute();
            $dup_chk->store_result();
            if($dup_chk->num_rows > 0) {
                $errors[] = "Row {$current_row} skipped: Account '{$data['account_name']}' already exists";
                $dup_chk->close();
                continue;
            }
            $dup_chk->close();
            
            // insert into account_list (map CSV fields)
            $query = "INSERT INTO account_list (name, description, type, balance, status, date_created) 
                     VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $db->conn->prepare($query);
            // Use account_type as description and set a default type
            $acct_name = $data['account_name'];
            $acct_desc = $data['account_type'];
            $acct_type = 1;
            $stmt->bind_param("ssidi", $acct_name, $acct_desc, $acct_type, $balance, $status);
            
            if($stmt->execute()) {
                $imported++;
            } else {
                $errors[] = "Error importing: " . $data['account_name'];
            }
            $stmt->close();
        }
    }
    else if($type === 'transactions') {
        $expected = ['type','reference_code','entity','account','total_amount','discount_perc','tax_perc','remarks','transaction_date'];
        if(array_values($headers) !== $expected) {
            throw new Exception('CSV headers do not match expected format: expected '.implode(',', $expected));
        }

        if(isset($_POST['validate_only']) && $_POST['validate_only'] == 1) {
            echo json_encode(['status' => 'success', 'message' => 'File validated successfully. Starting import...']);
            fclose($handle);
            exit;
        }

        // Helper: resolve entity ID by name or numeric ID
        $resolve_entity = function($val, $tx_type) use ($db) {
            $val = trim($val);
            if(empty($val)) return null;
            $table = 'entity_list';
            if(is_numeric($val)) {
                $chk = $db->conn->prepare("SELECT id FROM {$table} WHERE id = ? LIMIT 1");
                $chk->bind_param("i", $val);
                $chk->execute();
                $chk->store_result();
                if($chk->num_rows > 0) { $chk->close(); return (int)$val; }
                $chk->close();
            }
            $e_type = ($tx_type === 'sale') ? 'Customer' : 'Supplier';
            $chk = $db->conn->prepare("SELECT id FROM {$table} WHERE LOWER(display_name) = LOWER(?) AND entity_type = ? LIMIT 1");
            $chk->bind_param("ss", $val, $e_type);
            $chk->execute();
            $chk->bind_result($found_id);
            $found = $chk->fetch() ? (int)$found_id : null;
            $chk->close();
            return $found;
        };

        // Helper: resolve account ID by name or numeric ID
        $resolve_account = function($val) use ($db) {
            $val = trim($val);
            if(empty($val)) return null;
            if(is_numeric($val)) {
                $chk = $db->conn->prepare("SELECT id FROM account_list WHERE id = ? LIMIT 1");
                $chk->bind_param("i", $val);
                $chk->execute();
                $chk->store_result();
                if($chk->num_rows > 0) { $chk->close(); return (int)$val; }
                $chk->close();
            }
            $chk = $db->conn->prepare("SELECT id FROM account_list WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $chk->bind_param("s", $val);
            $chk->execute();
            $chk->bind_result($found_id);
            $found = $chk->fetch() ? (int)$found_id : null;
            $chk->close();
            return $found;
        };

        $user_id = isset($_SESSION['userdata']['id']) ? (int)$_SESSION['userdata']['id'] : 1;

        while(($row = fgetcsv($handle)) !== false) {
            $current_row++;
            update_progress($progress_file, $current_row, $total_rows);

            if(empty(array_filter($row))) continue;

            $data = array_combine($headers, $row);

            // Validate transaction type
            $tx_type = strtolower(trim($data['type']));
            if(!in_array($tx_type, ['sale', 'purchase'])) {
                $errors[] = "Row {$current_row} skipped: Invalid type '{$data['type']}'. Must be 'sale' or 'purchase'";
                continue;
            }

            if(empty($data['total_amount']) || !is_numeric($data['total_amount'])) {
                $errors[] = "Row {$current_row} skipped: Missing or invalid total_amount";
                continue;
            }

            if(empty($data['transaction_date'])) {
                $errors[] = "Row {$current_row} skipped: Missing transaction_date";
                continue;
            }

            // Validate date
            $tx_date = date('Y-m-d', strtotime($data['transaction_date']));
            if($tx_date === '1970-01-01' && trim($data['transaction_date']) !== '1970-01-01') {
                $errors[] = "Row {$current_row} skipped: Invalid transaction_date '{$data['transaction_date']}'";
                continue;
            }

            // Check duplicate reference_code
            $ref_code = trim($data['reference_code']);
            if(!empty($ref_code)) {
                $chk_dup = $db->conn->prepare("SELECT id FROM transactions WHERE reference_code = ? LIMIT 1");
                $chk_dup->bind_param("s", $ref_code);
                $chk_dup->execute();
                $chk_dup->store_result();
                if($chk_dup->num_rows > 0) {
                    $errors[] = "Row {$current_row} skipped: Reference code '{$ref_code}' already exists";
                    $chk_dup->close();
                    continue;
                }
                $chk_dup->close();
            } else {
                // Auto-generate reference code
                $prefix = ($tx_type === 'sale') ? 'SALE' : 'BILL';
                $last = $db->conn->query("SELECT reference_code FROM transactions WHERE reference_code LIKE '{$prefix}-%' ORDER BY id DESC LIMIT 1")->fetch_assoc();
                $next_num = $last ? (intval(substr($last['reference_code'], strlen($prefix)+1)) + 1) : 1;
                $ref_code = $prefix . '-' . sprintf('%04d', $next_num);
            }

            // Resolve entity (customer for sale, supplier for purchase)
            $entity_val = trim($data['entity']);
            $entity_id = null;
            if(!empty($entity_val)) {
                $entity_id = $resolve_entity($entity_val, $tx_type);
                if($entity_id === null) {
                    $entity_label = ($tx_type === 'sale') ? 'Customer' : 'Supplier';
                    $errors[] = "Row {$current_row} skipped: {$entity_label} '{$entity_val}' not found";
                    continue;
                }
            }

            // Resolve account (optional)
            $account_val = trim($data['account']);
            $account_id = !empty($account_val) ? $resolve_account($account_val) : null;
            if(!empty($account_val) && $account_id === null) {
                $errors[] = "Row {$current_row} skipped: Account '{$account_val}' not found";
                continue;
            }

            // Calculate discount & tax amounts from percentages
            $total_amount = floatval($data['total_amount']);
            $disc_perc    = floatval($data['discount_perc'] ?? 0);
            $tax_perc     = floatval($data['tax_perc'] ?? 0);
            $discount     = round($total_amount * $disc_perc / 100, 4);
            $tax          = round($total_amount * $tax_perc / 100, 4);
            $remarks      = trim($data['remarks'] ?? '');
            $status       = 1; // default: active/completed

            $query = "INSERT INTO transactions 
                        (reference_code, type, entity_id, account_id, total_amount, discount_perc, discount, tax_perc, tax, remarks, status, transaction_date, created_by)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->conn->prepare($query);
            $stmt->bind_param(
                "ssiiidddddssi",
                $ref_code, $tx_type, $entity_id, $account_id,
                $total_amount, $disc_perc, $discount, $tax_perc, $tax,
                $remarks, $status, $tx_date, $user_id
            );

            if($stmt->execute()) {
                $imported++;
            } else {
                $errors[] = "Row {$current_row} error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    else {
        throw new Exception('Invalid import type');
    }
    
    fclose($handle);
    update_progress($progress_file, $total_rows, $total_rows);
    
    $message = "Successfully imported $imported records";
    if(count($errors) > 0) {
        $message .= " with " . count($errors) . " error(s). Download the error report below.";
    }
    
    echo json_encode(['status' => 'success', 'message' => $message, 'imported' => $imported, 'error_count' => count($errors), 'errors' => $errors]);
    
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
