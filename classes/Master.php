<?php
require_once __DIR__ . '/../config.php';
Class Master extends DBConnection {
    private $settings;
    private $cache;
    
    public function __construct(){
        global $_settings;
        $this->settings = $_settings;
        parent::__construct();
        require_once __DIR__ . '/Cache.php';
        $this->cache = new Cache();
    }
    
    public function clear_cache($key = 'dashboard'){
        if(isset($this->cache)) $this->cache->delete($key);
    }
    
    public function __destruct(){
        parent::__destruct();
    }
    
    function capture_err(){
        if(!$this->conn->error)
            return false;
        else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
            return json_encode($resp);
        }
    }
    
    // ==================== VENDOR FUNCTIONS ====================
    function save_vendor(){
        extract($_POST);
        $user_id = $this->settings->userdata('id') ?: 1;
        $ip = $_SERVER['REMOTE_ADDR'];

        $check_stmt = $this->conn->prepare("SELECT id FROM `entity_list` WHERE `display_name` = ? AND `entity_type` = 'Supplier' ".(!empty($id) ? " AND id != ?" : ""));
        if(!empty($id)) $check_stmt->bind_param("si", $name, $id);
        else $check_stmt->bind_param("s", $name);
        
        $check_stmt->execute();
        $check_stmt->store_result();
        if($check_stmt->num_rows > 0){
            $check_stmt->close();
            return json_encode(['status' => 'failed', 'msg' => "Vendor Name already exists."]);
        }
        $check_stmt->close();

        if(empty($id)){
        $sql = "INSERT INTO `entity_list` (`entity_type`, `display_name`, `address`, `city`, `state`, `cperson`, `contact`, `alternate_contact`, `status`, `tax_id`, `excise_license_no`, `credit_limit`, `credit_days`, `opening_balance`, `created_by`, `ip_address`) VALUES ('Supplier', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $tax_id = $tax_id ?? null;
        $excise_license_no = $excise_license_no ?? null;
        $city = $city ?? null;
        $state = $state ?? null;
        $alternate_contact = $alternate_contact ?? null;
        $credit_limit = $credit_limit ?? 0;
        $credit_days = $credit_days ?? 0;
        $opening_balance = $opening_balance ?? 0;
        $stmt->bind_param("ssssssssissiddis", $name, $address, $city, $state, $cperson, $contact, $alternate_contact, $status, $tax_id, $excise_license_no, $credit_limit, $credit_days, $opening_balance, $user_id, $ip);
    }else{
        $sql = "UPDATE `entity_list` SET `display_name` = ?, `address` = ?, `city` = ?, `state` = ?, `cperson` = ?, `contact` = ?, `alternate_contact` = ?, `status` = ?, `tax_id` = ?, `excise_license_no` = ?, `credit_limit` = ?, `credit_days` = ?, `opening_balance` = ?, `updated_by` = ?, `ip_address` = ? WHERE id = ? AND `entity_type` = 'Supplier'";
        $stmt = $this->conn->prepare($sql);
        $tax_id = $tax_id ?? null;
        $excise_license_no = $excise_license_no ?? null;
        $city = $city ?? null;
        $state = $state ?? null;
        $alternate_contact = $alternate_contact ?? null;
        $credit_limit = $credit_limit ?? 0;
        $credit_days = $credit_days ?? 0;
        $opening_balance = $opening_balance ?? 0;
        $stmt->bind_param("ssssssssissiddisi", $name, $address, $city, $state, $cperson, $contact, $alternate_contact, $status, $tax_id, $excise_license_no, $credit_limit, $credit_days, $opening_balance, $user_id, $ip, $id);
    }

    if($stmt->execute()){
        $vendor_id = empty($id) ? $this->conn->insert_id : $id;
        
        // Handle Opening Balance Transaction
        if(empty($id) && $opening_balance > 0){
            $vcode = 'OBV-' . date('YmdHis') . rand(100,999);
            $this->conn->query("INSERT INTO `transactions` (`reference_code`, `type`, `entity_id`, `total_amount`, `remarks`, `created_by`, `transaction_date`) VALUES ('{$vcode}', 'purchase', '{$vendor_id}', '{$opening_balance}', 'Opening Balance (Auto)', '{$user_id}', NOW())");
        }
            $resp['status'] = 'success';
            $resp['id'] = $vendor_id;
            $resp['msg'] = empty($id) ? "New Vendor successfully saved." : "Vendor successfully updated.";
            $this->settings->log_action($user_id, (empty($id) ? "Created" : "Updated")." Vendor", "Vendors", $vendor_id);
            $this->settings->set_flashdata('success', $resp['msg']);
            $this->clear_cache('dashboard');
            
            // Search Index Update
            $search_data = implode(' ', [$name, $contact, $address, $tax_id]);
            $this->update_search_index('entity_list', $vendor_id, 'vendor', $name, $contact, $search_data, 0, null, "master/vendors/view&id=$vendor_id", "master/vendors/manage&id=$vendor_id");
        }else{
            $resp['status'] = 'failed';
            $resp['msg'] = $this->conn->error;
        }
        $stmt->close();
        return json_encode($resp);
    }
    
    private function delete_entity_record($id, $entity_type, $success_msg){
        $id = intval($id);
        $stmt = $this->conn->prepare("DELETE FROM `entity_list` WHERE id = ? AND `entity_type` = ?");
        $stmt->bind_param("is", $id, $entity_type);
        if($stmt->execute()){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', $success_msg);
            $this->delete_from_index('entity_list', $id);
            $this->clear_cache('dashboard');
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        $stmt->close();
        return json_encode($resp);
    }
    
    function delete_vendor(){
        extract($_POST);
        return $this->delete_entity_record($id ?? 0, 'Supplier', "Vendor successfully deleted.");
    }
    
    // ==================== CUSTOMER FUNCTIONS ====================
    function save_customer(){
        extract($_POST);
        $user_id = $this->settings->userdata('id') ?: 1;
        $ip = $_SERVER['REMOTE_ADDR'];

        $check_stmt = $this->conn->prepare("SELECT id FROM `entity_list` WHERE `display_name` = ? AND `entity_type` = 'Customer' ".(!empty($id) ? " AND id != ?" : ""));
        if(!empty($id)) $check_stmt->bind_param("si", $name, $id);
        else $check_stmt->bind_param("s", $name);
        
        $check_stmt->execute();
        $check_stmt->store_result();
        if($check_stmt->num_rows > 0){
            $check_stmt->close();
            return json_encode(['status' => 'failed', 'msg' => "Customer Name already exists."]);
        }
        $check_stmt->close();

        if(empty($id)){
        $sql = "INSERT INTO `entity_list` (`entity_type`, `display_name`, `contact`, `alternate_contact`, `email`, `address`, `city`, `state`, `status`, `credit_limit`, `credit_days`, `opening_balance`, `tax_id`, `created_by`, `ip_address`) VALUES ('Customer', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $tax_id = $tax_id ?? null;
        $city = $city ?? null;
        $state = $state ?? null;
        $alternate_contact = $alternate_contact ?? null;
        $credit_limit = $credit_limit ?? 0;
        $credit_days = $credit_days ?? 0;
        $opening_balance = $opening_balance ?? 0;
        $stmt->bind_param("sssssssissiddsi", $name, $contact, $alternate_contact, $email, $address, $city, $state, $status, $credit_limit, $credit_days, $opening_balance, $tax_id, $user_id, $ip);
    }else{
        $sql = "UPDATE `entity_list` SET `display_name` = ?, `contact` = ?, `alternate_contact` = ?, `email` = ?, `address` = ?, `city` = ?, `state` = ?, `status` = ?, `credit_limit` = ?, `credit_days` = ?, `opening_balance` = ?, `tax_id` = ?, `updated_by` = ?, `ip_address` = ? WHERE id = ? AND `entity_type` = 'Customer'";
        $stmt = $this->conn->prepare($sql);
        $tax_id = $tax_id ?? null;
        $city = $city ?? null;
        $state = $state ?? null;
        $alternate_contact = $alternate_contact ?? null;
        $credit_limit = $credit_limit ?? 0;
        $credit_days = $credit_days ?? 0;
        $opening_balance = $opening_balance ?? 0;
        $stmt->bind_param("sssssssissiddsisi", $name, $contact, $alternate_contact, $email, $address, $city, $state, $status, $credit_limit, $credit_days, $opening_balance, $tax_id, $user_id, $ip, $id);
    }

    if($stmt->execute()){
        $customer_id = empty($id) ? $this->conn->insert_id : $id;

        // Handle Opening Balance Transaction
        if(empty($id) && $opening_balance > 0){
            $ccode = 'OBC-' . date('YmdHis') . rand(100,999);
            $ob_stmt = $this->conn->prepare("INSERT INTO `transactions` (`reference_code`, `type`, `entity_id`, `total_amount`, `remarks`, `created_by`, `transaction_date`) VALUES (?, 'sale', ?, ?, 'Opening Balance (Auto)', ?, NOW())");
            $ob_stmt->bind_param("sidi", $ccode, $customer_id, $opening_balance, $user_id);
            $ob_stmt->execute();
            $ob_stmt->close();
        }
            $resp['status'] = 'success';
            $resp['id'] = $customer_id;
            $resp['msg'] = empty($id) ? "New Customer successfully saved." : "Customer successfully updated.";
            $this->settings->log_action($user_id, (empty($id) ? "Created" : "Updated")." Customer", "Customers", $customer_id);
            $this->settings->set_flashdata('success', $resp['msg']);
            
            // Search Index Update
            $search_data = implode(' ', [$name, $contact, $email, $address, $tax_id]);
            $this->update_search_index('entity_list', $customer_id, 'customer', $name, $contact, $search_data, 0, null, "master/customers/view&id=$customer_id", "master/customers/manage&id=$customer_id");
        }else{
            $resp['status'] = 'failed';
            $resp['msg'] = $this->conn->error;
        }
        $stmt->close();
        return json_encode($resp);
    }
    
    function delete_customer(){
        extract($_POST);
        return $this->delete_entity_record($id ?? 0, 'Customer', "Customer successfully deleted.");
    }

    function get_customer_details(){
        extract($_POST);
        $customer_id = intval($id);
        $resp = array();
        if($customer_id > 0){
            $stmt = $this->conn->prepare("SELECT id, display_name, email, contact, alternate_contact, tax_id, address, city, state, credit_limit, credit_days, opening_balance, status FROM `entity_list` WHERE id = ? AND `entity_type` = 'Customer'");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $qry = $stmt->get_result();
            if($qry->num_rows > 0){
                $res = $qry->fetch_assoc();
                $res['name'] = $res['display_name']; // Backwards compatibility with form fields
                $resp['status'] = 'success';
                $resp['data'] = $res;
                
                // Calculate new outstanding for party (Customer: Total Sales - Total Payments received)
                $out_stmt = $this->conn->prepare("
                    SELECT COALESCE(SUM(total_amount), 0) - COALESCE(
                        (SELECT SUM(total_amount) FROM transactions p 
                            WHERE p.parent_id IN (SELECT id FROM transactions WHERE entity_id = ? AND type='sale') AND p.type = 'payment'), 0
                    ) as outstanding
                    FROM transactions WHERE entity_id = ? AND type='sale'
                ");
                $out_stmt->bind_param("ii", $customer_id, $customer_id);
                $out_stmt->execute();
                $party_outstanding = $out_stmt->get_result()->fetch_assoc()['outstanding'] ?? 0;
                $out_stmt->close();
                
                $resp['data']['outstanding'] = floatval($party_outstanding);
            $resp['data']['credit_limit'] = floatval($res['credit_limit'] ?? 0);
            $resp['data']['remaining_credit'] = ($resp['data']['credit_limit'] > 0) ? ($resp['data']['credit_limit'] - $resp['data']['outstanding']) : 999999999; 
            } else {
                $resp['status'] = 'failed';
                $resp['msg'] = "Customer not found.";
            }
            $stmt->close();
        } else {
            $resp['status'] = 'success';
            $resp['data'] = array(
                'contact' => 'N/A', 
                'outstanding' => 0, 
                'credit_limit' => 0, 
                'remaining_credit' => 999999999
            );
        }
        return json_encode($resp);
    }
    
    // ==================== ITEM FUNCTIONS ====================
    function save_item(){
        extract($_POST);
        $user_id = $this->settings->userdata('id') ?: 1;
        $ip = $_SERVER['REMOTE_ADDR'];

        // Check if item exists
        $check_sql = "SELECT id FROM `item_list` WHERE `name` = ?";
        if(!empty($id)) $check_sql .= " AND id != ?";
        
        $check_stmt = $this->conn->prepare($check_sql);
        if(!empty($id)) $check_stmt->bind_param("si", $name, $id);
        else $check_stmt->bind_param("s", $name);
        
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if($check_stmt->num_rows > 0){
            $check_stmt->close();
            return json_encode(['status' => 'failed', 'msg' => "Item already exists."]);
        }
        $check_stmt->close();

        // Set default values
    $cost = isset($cost) && !empty($cost) ? floatval($cost) : 0;
    $selling_price = isset($selling_price) && !empty($selling_price) ? floatval($selling_price) : 0;
    $mrp = isset($mrp) && !empty($mrp) ? floatval($mrp) : 0;
    $opening_cost = isset($opening_cost) && !empty($opening_cost) ? floatval($opening_cost) : $cost;

    $average_cost = $opening_cost;
    $unit = isset($unit) ? $unit : '';
    $reorder_level = isset($reorder_level) ? floatval($reorder_level) : 0;
    
    // Make vendor_id NULL if empty (optional field)
    $vendor_id = isset($vendor_id) && !empty($vendor_id) ? intval($vendor_id) : NULL;
    $category_id = isset($category_id) && !empty($category_id) ? intval($category_id) : NULL;

    if(empty($id)){
        $sql = "INSERT INTO `item_list` (`name`, `description`, `category_id`, `vendor_id`, `cost`, `selling_price`, `mrp`, `opening_cost`, `average_cost`, `unit`, `reorder_level`, `tax_type`, `status`, `created_by`, `ip_address`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if(!$stmt) return json_encode(['status' => 'failed', 'msg' => "Prepare failed: " . $this->conn->error]);

        
        $tax_type = isset($tax_type) ? intval($tax_type) : 1;
        $status = isset($status) ? intval($status) : 1;
        
        // Bind with types: s=string, i=int, d=double. unit(s), reorder(d)
        $stmt->bind_param("ssiidddddsdiiis", $name, $description, $category_id, $vendor_id, $cost, $selling_price, $mrp, $opening_cost, $average_cost, $unit, $reorder_level, $tax_type, $status, $user_id, $ip);

    }else{
        $sql = "UPDATE `item_list` SET `name` = ?, `description` = ?, `category_id` = ?, `vendor_id` = ?, `cost` = ?, `selling_price` = ?, `mrp` = ?, `opening_cost` = ?, `average_cost` = ?, `unit` = ?, `reorder_level` = ?, `tax_type` = ?, `status` = ?, `updated_by` = ?, `ip_address` = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if(!$stmt) return json_encode(['status' => 'failed', 'msg' => "Prepare failed: " . $this->conn->error]);

        
        $tax_type = isset($tax_type) ? intval($tax_type) : 1;
        $status = isset($status) ? intval($status) : 1;
        $id = intval($id);
        
        $stmt->bind_param("ssiidddddsdiiisi", $name, $description, $category_id, $vendor_id, $cost, $selling_price, $mrp, $opening_cost, $average_cost, $unit, $reorder_level, $tax_type, $status, $user_id, $ip, $id);

    }

        if($stmt->execute()){
            $item_id = empty($id) ? $this->conn->insert_id : $id;
            $resp['status'] = 'success';
            $resp['msg'] = empty($id) ? "New Item successfully saved." : "Item successfully updated.";
            
            if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
                $fname = 'uploads/item-'.$item_id.'.png';
                $dir_path = base_app . $fname;
                $upload = $_FILES['img']['tmp_name'];
                $type = mime_content_type($upload);
                $allowed = array('image/png','image/jpeg');
                if(in_array($type, $allowed)){
                    $new_height = 200; 
                    $new_width = 200; 
                    list($width, $height) = getimagesize($upload);
                    $t_image = imagecreatetruecolor($new_width, $new_height);
                    imagealphablending($t_image, false);
                    imagesavealpha($t_image, true);
                    $gdImg = ($type == 'image/png') ? imagecreatefrompng($upload) : imagecreatefromjpeg($upload);
                    if($gdImg){
                        if(is_file($dir_path)) unlink($dir_path);
                        imagecopyresampled($t_image, $gdImg, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                        imagepng($t_image, $dir_path);
                        imagedestroy($gdImg);
                        imagedestroy($t_image);
                        $upd_img = $this->conn->prepare("UPDATE item_list SET `image_path` = CONCAT(?,'?v=',unix_timestamp(CURRENT_TIMESTAMP)) WHERE id = ?");
                        $upd_img->bind_param("si", $fname, $item_id);
                        $upd_img->execute();
                        $upd_img->close();
                    }
                }
            }
            $this->settings->log_action($user_id, (empty($id) ? "Created" : "Updated")." Item", "Items", $item_id);
            $this->settings->set_flashdata('success', $resp['msg']);
            $this->clear_cache('dashboard');
            
            // Search Index Update
            $cat_stmt = $this->conn->prepare("SELECT name FROM category_list WHERE id = ?");
            $cat_stmt->bind_param("i", $category_id);
            $cat_stmt->execute();
            $cat_name = $cat_stmt->get_result()->fetch_assoc()['name'] ?? 'Product';
            $cat_stmt->close();
            $search_data = implode(' ', [$name, $description, $unit, $cat_name]);
            $this->update_search_index('item_list', $item_id, 'item', $name, $cat_name, $search_data, $selling_price, null, "master/items/view&id=$item_id", "master/items/manage&id=$item_id");
        }else{
            $resp['status'] = 'failed';
            $resp['msg'] = $this->conn->error;
        }
        $stmt->close();
        return json_encode($resp);
    }
    
    function delete_item(){
        extract($_POST);
        $id = intval($id);
        $stmt = $this->conn->prepare("DELETE FROM `item_list` where id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Item successfully deleted.");
            $this->delete_from_index('item_list', $id);
            $this->clear_cache('dashboard');
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        $stmt->close();
        return json_encode($resp);
    }

    public function update_item_stock($item_id){
        if(empty($item_id)) return false;
        $item_id = intval($item_id);
        
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(CASE 
                WHEN t.type IN ('purchase', 'opening_stock', 'adjustment') THEN ti.quantity 
                WHEN t.type = 'sale' THEN -ti.quantity 
                WHEN t.type = 'return' THEN (CASE WHEN t.entity_type = 'vendor' THEN -ti.quantity ELSE ti.quantity END)
                ELSE 0 END), 0) as available
            FROM transaction_items ti 
            JOIN transactions t ON ti.transaction_id = t.id 
            WHERE ti.item_id = ?
        ");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $available = $stmt->get_result()->fetch_assoc()['available'] ?? 0;
        $stmt->close();
        
        $upd = $this->conn->prepare("UPDATE item_list SET quantity = ? WHERE id = ?");
        $upd->bind_param("di", $available, $item_id);
        $upd->execute();
        $upd->close();
        $this->clear_cache('dashboard');
        return $available;
    }

    /**
     * Recalculate and update the weighted average cost for an item.
     * Considers all 'Inward' movements: Purchases, Opening Stock, and Addition Adjustments.
     */
    public function update_item_cost($item_id){
        if(empty($item_id)) return false;
        $item_id = intval($item_id);

        $stmt = $this->conn->prepare("
            SELECT SUM(ti.total_price) as total_val, SUM(ti.quantity) as total_qty 
            FROM transaction_items ti 
            JOIN transactions t ON ti.transaction_id = t.id 
            WHERE ti.item_id = ? 
            AND (
                t.type IN ('purchase', 'opening_stock') 
                OR (t.type = 'adjustment' AND ti.quantity > 0)
            )
            AND ti.unit_price > 0
        ");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if($res){
            $total_val = floatval($res['total_val']);
            $total_qty = floatval($res['total_qty']);
            
            if($total_qty > 0){
                $avg_cost = $total_val / $total_qty;
                $upd = $this->conn->prepare("UPDATE item_list SET cost = ? WHERE id = ?");
                $upd->bind_param("di", $avg_cost, $item_id);
                $upd->execute();
                $upd->close();
                $this->clear_cache('dashboard');
                return $avg_cost;
            }
        }
        return false;
    }
    
    // ==================== PURCHASE FUNCTIONS ====================
    function save_purchase(){
        if(empty($_POST['id'])){
            $date = isset($_POST['date_created']) ? $_POST['date_created'] : date('Y-m-d');
            $code = $this->generate_reference_code('purchase', $date);
            if(!$code){
                return json_encode(['status'=>'failed', 'msg'=>'No active reference code setting for Purchases.']);
            }
            $_POST['po_code'] = $code;
        }
        
        extract($_POST);
        $resp = [];
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $user_id = $this->settings->userdata('id') ?: 1;

        // Backend Validation
        if(empty($entity_id)){
            return json_encode(['status'=>'failed','msg'=>'Vendor is mandatory.']);
        }
        $trans_date = isset($date_po) ? $date_po : date('Y-m-d');
        if(empty($trans_date)){
            return json_encode(['status'=>'failed','msg'=>'Bill date is mandatory.']);
        }
        if(!isset($item_id) || !is_array($item_id) || count($item_id) <= 0){
            return json_encode(['status'=>'failed','msg'=>'Please add at least 1 item.']);
        }
        foreach($qty as $k => $v){
            if(floatval($v) < 1){
                return json_encode(['status'=>'failed','msg'=>'All items must have a quantity of at least 1.']);
            }
        }
        $calc_amount = 0;
        if(isset($total) && is_array($total)){
            foreach($total as $t){
                $calc_amount += floatval($t);
            }
        }
        // Override amount with calculated total if items exist
        if($calc_amount > 0) {
            $amount = $calc_amount;
            // Recalculate Tax if percentage is set
             if(isset($tax_perc)){
                 $tax = ($amount * floatval($tax_perc)) / 100;
             }
        }

        $this->conn->begin_transaction();
        try {
            if(empty($id)){
                $sql = "INSERT INTO `transactions` (`reference_code`, `type`, `entity_type`, `entity_id`, `total_amount`, `tax_perc`, `tax`, `remarks`, `transaction_date`, `created_by`) VALUES (?, 'purchase', 'Supplier', ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $vendor_invoice_no = isset($vendor_invoice_no) ? $vendor_invoice_no : '';
                $date_po = isset($date_po) ? $date_po : date('Y-m-d');
                $tax_perc = isset($tax_perc) ? floatval($tax_perc) : 0;
                $tax = isset($tax) ? floatval($tax) : 0;
                $amount = isset($amount) ? floatval($amount) : 0;
                $vendor_id = intval($entity_id ?? ($vendor_id ?? 0));
                // Append invoice number to remarks for now
                $remarks = isset($remarks) ? $remarks : '';
                if (!empty($vendor_invoice_no)) {
                     $remarks .= "\nInvoice No: " . $vendor_invoice_no;
                }

                $stmt->bind_param("sidddssi", $po_code, $vendor_id, $amount, $tax_perc, $tax, $remarks, $date_po, $user_id);
            }else{
                $sql = "UPDATE `transactions` SET `reference_code` = ?, `entity_id` = ?, `total_amount` = ?, `tax_perc` = ?, `tax` = ?, `remarks` = ?, `transaction_date` = ?, `updated_by` = ? WHERE id = ? AND type='purchase'";
                $stmt = $this->conn->prepare($sql);
                $vendor_invoice_no = isset($vendor_invoice_no) ? $vendor_invoice_no : '';
                $date_po = isset($date_po) ? $date_po : date('Y-m-d');
                $tax_perc = isset($tax_perc) ? floatval($tax_perc) : 0;
                $tax = isset($tax) ? floatval($tax) : 0;
                $amount = isset($amount) ? floatval($amount) : 0;
                $vendor_id = intval($entity_id ?? ($vendor_id ?? 0));
                $id = intval($id);
                // Append invoice number to remarks for now
                $remarks = isset($remarks) ? $remarks : '';
                if (!empty($vendor_invoice_no) && strpos($remarks, "Invoice No:") === false) {
                     $remarks .= "\nInvoice No: " . $vendor_invoice_no;
                }
                
                $stmt->bind_param("sidddssii", $po_code, $vendor_id, $amount, $tax_perc, $tax, $remarks, $date_po, $user_id, $id);
            }
            if(!$stmt->execute()) throw new Exception($this->conn->error);
            $po_id = empty($id) ? $this->conn->insert_id : $id;
            $stmt->close();

            // Delete old po_items (now transaction_items)
            if(!empty($id)){
                $del_items = $this->conn->prepare("DELETE FROM transaction_items WHERE transaction_id = ?");
                $del_items->bind_param("i", $po_id);
                $del_items->execute();
                $del_items->close();
            }

            $po_item_stmt = $this->conn->prepare("INSERT INTO `transaction_items` (transaction_id, item_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            
            foreach($item_id as $k => $v){
                $p_qty = floatval($qty[$k]);
                $p_price = floatval($price[$k]);
                $p_total = floatval($total[$k]);

                // Save PO Item
                $po_item_stmt->bind_param("iiddd", $po_id, $v, $p_qty, $p_price, $p_total);
                $po_item_stmt->execute();
            }
            $po_item_stmt->close();

            // Sync item stock quantities and average cost
            if(isset($item_id) && is_array($item_id)){
                foreach(array_unique($item_id) as $iid) {
                    $this->update_item_stock($iid);
                    $this->update_item_cost($iid);
                }
            }

            $this->conn->commit();
            $this->update_summary_metrics($date_po);
            $this->clear_cache('dashboard');
            $this->settings->log_action($user_id, (empty($id) ? "Created" : "Updated")." Purchase Order", "Purchases", $po_id);
            $this->settings->set_flashdata('success', "Purchase successfully saved.");
            
            // Search Index Update
            $pty_stmt = $this->conn->prepare("SELECT display_name FROM entity_list WHERE id = ?");
            $pty_stmt->bind_param("i", $vendor_id);
            $pty_stmt->execute();
            $party = $pty_stmt->get_result()->fetch_assoc()['display_name'] ?? 'Unknown';
            $pty_stmt->close();
            $search_data = implode(' ', [$po_code, $remarks, $party]);
            $this->update_search_index('transactions', $po_id, 'purchase', $po_code, $party, $search_data, $amount, $date_po, "transactions/purchases/view_purchase&id=$po_id", "transactions/purchases/manage_purchase&id=$po_id");
            $resp['status'] = 'success';
            $resp['id'] = $po_id;
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            // Log the error for debugging
            try{
                $logDir = __DIR__ . '/../logs';
                if(!is_dir($logDir)) @mkdir($logDir, 0755, true);
                $logFile = $logDir . '/payment_errors.log';
                $entry = "[".date('Y-m-d H:i:s')."] save_purchase error: " . $e->getMessage() . "\n";
                file_put_contents($logFile, $entry, FILE_APPEND);
            }catch(Throwable $ignore){}
        }
        return json_encode($resp);
    }
    
    function delete_po(){
        extract($_POST);
        $resp = [];
        $id = intval($id);
        $this->conn->begin_transaction();
        try {
            // Prevent deletion if payments are linked - Fixed: ref_id to parent_id
            $pay_stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM transactions WHERE parent_id = ? AND type = 'payment'");
            $pay_stmt->bind_param("i", $id);
            $pay_stmt->execute();
            $payments_count = $pay_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
            $pay_stmt->close();

            if($payments_count > 0){
                throw new Exception("Cannot delete purchase order because payments are linked to it.");
            }

            $date_stmt->close();

            // Get item IDs to update stock later
            $items_stmt = $this->conn->prepare("SELECT DISTINCT item_id FROM transaction_items WHERE transaction_id = ?");
            $items_stmt->bind_param("i", $id);
            $items_stmt->execute();
            $items_qry = $items_stmt->get_result();
            $affected_items = [];
            while($irow = $items_qry->fetch_assoc()) $affected_items[] = $irow['item_id'];
            $items_stmt->close();

            // Delete transaction_items
            $del_items = $this->conn->prepare("DELETE FROM transaction_items WHERE transaction_id = ?");
            $del_items->bind_param("i", $id);
            $del_items->execute();
            $del_items->close();

            // Delete purchase order in transactions
            $del_txn = $this->conn->prepare("DELETE FROM `transactions` WHERE id = ? AND type='purchase'");
            $del_txn->bind_param("i", $id);
            $del_txn->execute();
            $del_txn->close();
            
            // Sync item stock quantities
            foreach($affected_items as $iid) $this->update_item_stock($iid);

            $this->conn->commit();
            if(isset($trans_date)) $this->update_summary_metrics($trans_date);
            $this->clear_cache('dashboard');
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Purchase successfully deleted.");
            $this->delete_from_index('transactions', $id);
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['error'] = $e->getMessage();
        }
        return json_encode($resp);
    }
    // ==================== POS FUNCTIONS ====================
    function save_pos(){
        extract($_POST);
        $user_id = $this->settings->userdata('id') ?: 1;
        
        $customer_id = !empty($customer_id) ? intval($customer_id) : null;
        $pos_date = isset($pos_date) && !empty($pos_date) ? $pos_date : date('Y-m-d');
        
        // 1. Identify Cash Account dynamically
        $cash_id = 0;
        $cash_qry = $this->conn->query("SELECT id FROM account_list WHERE name = 'Cash' LIMIT 1");
        if($cash_qry->num_rows > 0) $cash_id = $cash_qry->fetch_assoc()['id'];

        $paid_amount = 0;
        $payment_breakdown = "";
        $actual_account_payments = array();
        $overpaid_adjustments = array();

        if(isset($account_amount) && is_array($account_amount)){
            // Get account names for breakdown
            $acc_ids = array_keys($account_amount);
            $acc_names = array();
            if(!empty($acc_ids)){
                $acc_qry = $this->conn->query("SELECT id, name FROM account_list WHERE id IN (".implode(',', $acc_ids).")");
                if($acc_qry){
                    while($ar = $acc_qry->fetch_assoc()) $acc_names[$ar['id']] = $ar['name'];
                }
            }

            // Calculate cart total for capping
            $session_cart_total = 0;
            if(isset($total_price) && is_array($total_price)){
                foreach($total_price as $t) $session_cart_total += floatval($t);
            }

            $remaining_cart = $session_cart_total;
            $total_tendered = 0;

            // Sort accounts to process non-cash first, Cash last
            // This ensures non-cash accounts pay the bill first, minimizing overpayment transfers
            uksort($account_amount, function($a, $b) use ($cash_id) {
                if($a == $cash_id) return 1;
                if($b == $cash_id) return -1;
                return 0;
            });

            foreach($account_amount as $acc_id => $amt){
                $amt = floatval($amt);
                if($amt != 0){
                    $total_tendered += $amt;
                    $p_name = $acc_names[$acc_id] ?? "Account $acc_id";
                    
                    // Sales payment is capped to remaining cart
                    $sale_payment = min($amt, $remaining_cart);
                    if($sale_payment < 0) $sale_payment = 0; 
                    
                    $overpayment = $amt - $sale_payment;
                    
                    if($sale_payment != 0){
                        $paid_amount += $sale_payment;
                        $actual_account_payments[$acc_id] = $sale_payment;
                        $payment_breakdown .= ($payment_breakdown ? ", " : "") . "$p_name: $sale_payment";
                    }
                    
                    if($overpayment > 0 && $acc_id != $cash_id){
                        $overpaid_adjustments[$acc_id] = $overpayment;
                    }
                    
                    $remaining_cart -= $sale_payment;
                }
            }
        }
        
        // Update account_amount to reflect only the SALE portion for ledger entries below
        $account_amount = $actual_account_payments;
        $session_change = max(0, $total_tendered - $session_cart_total);


        $this->conn->begin_transaction();
        try {
            // STEP 1: Find or Create Master Sale for this date AND customer
            $sale_stmt = $this->conn->prepare("SELECT id, reference_code, total_amount FROM `transactions` WHERE type='sale' AND DATE(transaction_date) = ? AND (entity_id = ? OR (entity_id IS NULL AND ? IS NULL)) AND remarks LIKE '%[POS]%' LIMIT 1");
            $sale_stmt->bind_param("sss", $pos_date, $customer_id, $customer_id);
            $sale_stmt->execute();
            $sale_res = $sale_stmt->get_result();
            
            if($sale_res->num_rows > 0){
                // EXISTS -> Use it
                $sale_row = $sale_res->fetch_assoc();
                $sale_id = $sale_row['id'];
                $sales_code = $sale_row['reference_code'];
                $current_total = floatval($sale_row['total_amount']);
                $new_total = $current_total + $session_cart_total;
                
                // Update master total
                $upd_sale = $this->conn->prepare("UPDATE `transactions` SET total_amount = ? WHERE id = ?");
                $upd_sale->bind_param("di", $new_total, $sale_id);
                $upd_sale->execute();
                $upd_sale->close();
            } else {
                // NOT EXISTS -> Create new
                $sales_code = $this->generate_reference_code('sale', $pos_date);
                if(!$sales_code) throw new Exception("No active reference code setting for Sales.");
                
                $remarks = "[POS] Consolidated Sales for " . $pos_date;
                $new_total = $session_cart_total;
                $ins_sale = $this->conn->prepare("INSERT INTO `transactions` (`reference_code`, `type`, `entity_id`, `payment_terms`, `total_amount`, `remarks`, `transaction_date`, `created_by`) VALUES (?, 'sale', ?, 'Cash', ?, ?, ?, ?)");
                $ins_sale->bind_param("sidssi", $sales_code, $customer_id, $new_total, $remarks, $pos_date, $user_id);

                $ins_sale->execute();
                $sale_id = $this->conn->insert_id;
                $ins_sale->close();
            }
            $sale_stmt->close();

            // STEP 2: Insert or Update Sales Details
            if(isset($item_id) && is_array($item_id)){
                $chk_item = $this->conn->prepare("SELECT id, quantity, total_price FROM `transaction_items` WHERE transaction_id = ? AND item_id = ?");
                $ins_item = $this->conn->prepare("INSERT INTO `transaction_items` (transaction_id, item_id, quantity, unit_price, mrp, total_price, profit) VALUES (?, ?, ?, ?, ?, ?, 0)");
                $upd_item = $this->conn->prepare("UPDATE `transaction_items` SET quantity = ?, total_price = ?, unit_price = ?, mrp = ? WHERE id = ?");


                
                foreach($item_id as $k => $v){
                    $sell_qty = floatval($qty[$k]);
                    $sell_price = floatval($price[$k]);
                    $row_total = floatval($total_price[$k]);
                    
                    // Check if already in today's pos
                    $chk_item->bind_param("ii", $sale_id, $v);
                    $chk_item->execute();
                    $chk_res = $chk_item->get_result();
                    
                    if($chk_res->num_rows > 0){
                        $irow = $chk_res->fetch_assoc();
                        $new_qty = floatval($irow['quantity']) + $sell_qty;
                        $new_item_total = floatval($irow['total_price']) + $row_total;
                        $sell_mrp = isset($mrp[$k]) ? floatval($mrp[$k]) : 0;
                        
                        $upd_item->bind_param("ddddi", $new_qty, $new_item_total, $sell_price, $sell_mrp, $irow['id']);
                        $upd_item->execute();

                    } else {
                        $sell_mrp = isset($mrp[$k]) ? floatval($mrp[$k]) : 0;
                        $ins_item->bind_param("iidddd", $sale_id, $v, $sell_qty, $sell_price, $sell_mrp, $row_total);
                        $ins_item->execute();
                    }

                }
                $chk_item->close();
                $ins_item->close();
                $upd_item->close();
            }

            // STEP 3: Payment Record Handling
            // Check for payment on this date linked to the master sale AND customer
            $pay_stmt = $this->conn->prepare("SELECT id, reference_code, total_amount, remarks FROM `transactions` WHERE type='payment' AND parent_id = ? AND (entity_id = ? OR (entity_id IS NULL AND ? IS NULL)) AND DATE(transaction_date) = ? LIMIT 1");
            $pay_stmt->bind_param("isss", $sale_id, $customer_id, $customer_id, $pos_date);
            $pay_stmt->execute();
            $pay_res = $pay_stmt->get_result();
            
            if($pay_res->num_rows > 0) {
                // Update payment
                $prow = $pay_res->fetch_assoc();
                $pay_id = $prow['id'];
                $pcode = $prow['reference_code'];
                $new_paid_total = floatval($prow['total_amount']) + $paid_amount;
                
                // Append breakdown to remarks
                $new_remarks = $prow['remarks'];
                if(!empty($payment_breakdown)){
                    $new_remarks .= ($new_remarks ? " | " : "") . "[Update] " . $payment_breakdown;
                }
                
                $upd_pay = $this->conn->prepare("UPDATE `transactions` SET total_amount = ?, remarks = ? WHERE id = ?");
                $upd_pay->bind_param("dsi", $new_paid_total, $new_remarks, $pay_id);
                $upd_pay->execute();
                $upd_pay->close();
            } else {
                // Insert new payment
                $pcode = $this->generate_reference_code('payment', $pos_date);
                if(!$pcode) {
                     $pcode = 'PAY-' . date('YmdHis') . rand(100,999);
                }
                $premarks = "[POS Payment] " . $payment_breakdown;
                $ins_pay = $this->conn->prepare("INSERT INTO `transactions` (`reference_code`, `type`, `parent_id`, `entity_id`, `total_amount`, `remarks`, `transaction_date`, `created_by`) VALUES (?, 'payment', ?, ?, ?, ?, ?, ?)");
                $ins_pay->bind_param("siidssi", $pcode, $sale_id, $customer_id, $paid_amount, $premarks, $pos_date, $user_id);
                $ins_pay->execute();
                $pay_id = $this->conn->insert_id;
                $ins_pay->close();
            }
            $pay_stmt->close();

            // STEP 4: Transaction List (Ledger) Entries
            if(isset($account_amount) && is_array($account_amount)){
                $chk_tl = $this->conn->prepare("SELECT id, amount FROM transaction_list WHERE ref_table='transactions' AND ref_id=? AND account_id=?");
                $ins_tl = $this->conn->prepare("INSERT INTO transaction_list (account_id, amount, type, trans_code, ref_table, ref_id) VALUES (?, ?, 1, ?, 'transactions', ?)");
                $upd_tl = $this->conn->prepare("UPDATE transaction_list SET amount = ? WHERE id = ?");
                
                foreach($account_amount as $acc_id => $amt){
                    $amt = floatval($amt);
                    if($amt != 0){
                        $chk_tl->bind_param("ii", $pay_id, $acc_id);
                        $chk_tl->execute();
                        $tl_res = $chk_tl->get_result();
                        
                        if($tl_res->num_rows > 0){
                            $tl_row = $tl_res->fetch_assoc();
                            $new_tl_amt = floatval($tl_row['amount']) + $amt;
                            $upd_tl->bind_param("di", $new_tl_amt, $tl_row['id']);
                            $upd_tl->execute();
                        } else {
                            $ins_tl->bind_param("idsi", $acc_id, $amt, $pcode, $pay_id);
                            $ins_tl->execute();
                        }
                        $this->update_account_balance($acc_id);
                    }
                }
                $chk_tl->close();
                $ins_tl->close();
                $upd_tl->close();
            }

            // Sync item stock quantities & Search Index
            if(isset($item_id) && is_array($item_id)){
                foreach(array_unique($item_id) as $iid) $this->update_item_stock($iid);
            }
            
            
            $this->update_summary_metrics($pos_date);
            
            // Record Adjustments if applicable (e.g. Esewa overpayment)
            if(!empty($overpaid_adjustments)){
                $this->manage_pos_change_transfer($pos_date, $overpaid_adjustments);
            }
            
            $search_data = implode(' ', [$sales_code, "[POS] Consolidated Sales"]);

            $this->update_search_index('transactions', $sale_id, 'sale', $sales_code, 'Walk-in [POS]', $search_data, $new_total, $pos_date, "transactions/sales/view_sale&id=$sale_id", "transactions/sales/manage_sale&id=$sale_id");
            
            $this->conn->commit();
            $this->clear_cache('dashboard');
            
            $resp['status'] = 'success';
            $resp['msg'] = "POS entry successfully saved.";
            $resp['sale_id'] = $sale_id;
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }
    
    // ==================== SALES FUNCTIONS ====================
    function save_sale(){
        if(empty($_POST['id'])){
            $date = isset($_POST['date_created']) ? $_POST['date_created'] : date('Y-m-d');
            $code = $this->generate_reference_code('sale', $date);
            if(!$code){
                return json_encode(['status'=>'failed','msg'=>'No active reference code setting for Sales.']);
            }
            $_POST['sales_code'] = $code;
        }
        
        extract($_POST);
        $resp = [];
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $user_id = $this->settings->userdata('id') ?: 1;
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Handle empty customer_id for walk-in customers
        $customer_id = !empty($customer_id) ? intval($customer_id) : null;
        $amount = isset($amount) ? floatval($amount) : 0;
        $discount = isset($discount) ? floatval($discount) : 0;
        $remarks = $remarks ?? '';
        $payment_terms = $payment_terms ?? '';
        $trans_date = isset($date_sale) ? $date_sale : date('Y-m-d');

        // Backend Validation
        if(empty($customer_id)){
            return json_encode(['status'=>'failed','msg'=>'Customer is mandatory.']);
        }
        if(empty($trans_date)){
            return json_encode(['status'=>'failed','msg'=>'Sale date is mandatory.']);
        }
        if(!isset($item_id) || !is_array($item_id) || count($item_id) <= 0){
            return json_encode(['status'=>'failed','msg'=>'Please add at least 1 item.']);
        }
        foreach($qty as $k => $v){
            if(floatval($v) < 1){
                return json_encode(['status'=>'failed','msg'=>'All items must have a quantity of at least 1.']);
            }
        }

        $this->conn->begin_transaction();
        try {
            if(empty($id)){
                $sql = "INSERT INTO `transactions` (`reference_code`, `type`, `entity_id`, `payment_terms`, `total_amount`, `discount`, `remarks`, `transaction_date`, `created_by`) VALUES (?, 'sale', ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("sisddssi", $sales_code, $customer_id, $payment_terms, $amount, $discount, $remarks, $trans_date, $user_id);
            }else{
                $sql = "UPDATE `transactions` SET `reference_code` = ?, `entity_id` = ?, `payment_terms` = ?, `total_amount` = ?, `discount` = ?, `remarks` = ?, `transaction_date` = ?, `updated_by` = ? WHERE id = ? AND type='sale'";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("sisddssii", $sales_code, $customer_id, $payment_terms, $amount, $discount, $remarks, $trans_date, $user_id, $id);
            }
            
            if(!$stmt->execute()) throw new Exception($this->conn->error);
            $sale_id = empty($id) ? $this->conn->insert_id : $id;
            $stmt->close();

            // Reverse transaction items
            if (!empty($id)) {
                $del_items = $this->conn->prepare("DELETE FROM `transaction_items` WHERE transaction_id = ?");
                $del_items->bind_param("i", $sale_id);
                $del_items->execute();
                $del_items->close();
            }

            $ti_stmt = $this->conn->prepare("INSERT INTO `transaction_items` (transaction_id, item_id, quantity, unit_price, total_price, profit) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach($item_id as $k => $v){
                $sell_qty = floatval($qty[$k]);
                $sell_price = floatval($price[$k]);
                $row_total = floatval($total_price[$k] ?? ($total[$k] ?? 0));
                $profit = floatval($profit[$k] ?? 0);

                $ti_stmt->bind_param("iidddd", $sale_id, $v, $sell_qty, $sell_price, $row_total, $profit);
                if(!$ti_stmt->execute()) throw new Exception($this->conn->error);
            }
            $ti_stmt->close();

            // Sync item stock quantities
            if(isset($item_id) && is_array($item_id)){
                foreach(array_unique($item_id) as $iid) $this->update_item_stock($iid);
            }

            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['id'] = $sale_id;
            $this->settings->log_action($user_id, (empty($id) ? "Created" : "Updated")." Sale", "Sales", $sale_id);
            $this->settings->set_flashdata('success', "Sale successfully saved.");
            
            // Search Index Update
            $pty_stmt = $this->conn->prepare("SELECT display_name FROM entity_list WHERE id = ?");
            $pty_stmt->bind_param("i", $customer_id);
            $pty_stmt->execute();
            $party = $pty_stmt->get_result()->fetch_assoc()['display_name'] ?? 'Walk-in';
            $pty_stmt->close();
            $search_data = implode(' ', [$sales_code, $remarks, $party]);
            $this->update_search_index('transactions', $sale_id, 'sale', $sales_code, $party, $search_data, $amount, $trans_date, "transactions/sales/view_sale&id=$sale_id", "transactions/sales/manage_sale&id=$sale_id");
            
            // Update summary metrics
            $this->update_summary_metrics($trans_date);
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }
    
    function delete_sale(){
        extract($_POST);
        $id = intval($id);
        $this->conn->begin_transaction();
        try {
            // Prevent deletion if payments are linked - Fixed: ref_id to parent_id
            $pay_stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM transactions WHERE parent_id = ? AND type = 'payment'");
            $pay_stmt->bind_param("i", $id);
            $pay_stmt->execute();
            $payments_count = $pay_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
            $pay_stmt->close();

            if($payments_count > 0){
                throw new Exception("Cannot delete sale because payments are linked to this sale.");
            }

            // Get sale date for summary update
            $date_stmt = $this->conn->prepare("SELECT transaction_date FROM transactions WHERE id = ?");
            $date_stmt->bind_param("i", $id);
            $date_stmt->execute();
            $trans_date = $date_stmt->get_result()->fetch_assoc()['transaction_date'] ?? date('Y-m-d');
            $date_stmt->close();
            
            // Get item IDs to update stock later
            $items_stmt = $this->conn->prepare("SELECT DISTINCT item_id FROM transaction_items WHERE transaction_id = ?");
            $items_stmt->bind_param("i", $id);
            $items_stmt->execute();
            $items_qry = $items_stmt->get_result();
            $affected_items = [];
            while($irow = $items_qry->fetch_assoc()) $affected_items[] = $irow['item_id'];
            $items_stmt->close();

            // Delete related items
            $del_items = $this->conn->prepare("DELETE FROM `transaction_items` WHERE transaction_id = ?");
            $del_items->bind_param("i", $id);
            $del_items->execute();
            $del_items->close();
            
            // Delete related payments - Fixed: ref_id to parent_id
            $del_pay = $this->conn->prepare("DELETE FROM transactions WHERE parent_id = ? AND type = 'payment'");
            $del_pay->bind_param("i", $id);
            $del_pay->execute();
            $del_pay->close();
            
            // Delete sale
            $del_sale = $this->conn->prepare("DELETE FROM `transactions` WHERE id = ? AND type = 'sale'");
            $del_sale->bind_param("i", $id);
            $del_sale->execute();
            $del_sale->close();
            
            // Sync item stock quantities
            foreach($affected_items as $iid) $this->update_item_stock($iid);

            $this->conn->commit();
            if(isset($trans_date)) $this->update_summary_metrics($trans_date);
            $this->clear_cache('dashboard');
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Sale successfully deleted.");
            $this->delete_from_index('transactions', $id);
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['error'] = $e->getMessage();
        }
        return json_encode($resp);
    }
    
    // ==================== PAYMENT FUNCTIONS ====================
    function save_payment(){
        extract($_POST);
        $user_id = $this->settings->userdata('id') ?: 1;
        $ip = $_SERVER['REMOTE_ADDR'];

        $this->conn->begin_transaction();
        try {
            // Basic validation
            if(!isset($parent_id) || empty($parent_id)) throw new Exception("Parent record id is required.");
            if(!isset($type)) throw new Exception("Payment type is required.");
            // Get parent record to determine party and calculate outstanding
            $p_stmt = $this->conn->prepare("SELECT total_amount, entity_id FROM transactions WHERE id = ?");
            $p_stmt->bind_param("i", $parent_id);
            $p_stmt->execute();
            $parent = $p_stmt->get_result()->fetch_assoc();
            $p_stmt->close();
            if(!$parent) throw new Exception("Parent record not found.");
            
            $party_id = $parent['entity_id'] ?? 0;
            
            // Validate payment amount doesn't exceed balance
            $total_amount = floatval($parent['total_amount']);
            $ex_stmt = $this->conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as paid FROM transactions WHERE parent_id = ? AND type = 'payment'");
            $ex_stmt->bind_param("i", $parent_id);
            $ex_stmt->execute();
            $existing_paid = $ex_stmt->get_result()->fetch_assoc()['paid'] ?? 0;
            $ex_stmt->close();
            $current_balance = $total_amount - $existing_paid;
            
            $cash_amount = isset($cash_amount) ? floatval($cash_amount) : 0;
            $qr_amount = isset($qr_amount) ? floatval($qr_amount) : 0;
            $bank_amount = isset($bank_amount) ? floatval($bank_amount) : 0;
            $total_payment = $cash_amount + $qr_amount + $bank_amount;
            
            if($total_payment > $current_balance + 0.01) {
                throw new Exception("Payment amount ({$total_payment}) exceeds remaining balance ({$current_balance}).");
            }

            // Determine payment method(s)
            $payment_method = 'Mixed';
            $cash_count = ($cash_amount > 0) ? 1 : 0;
            $qr_count = ($qr_amount > 0) ? 1 : 0;
            $bank_count = ($bank_amount > 0) ? 1 : 0;
            $method_count = $cash_count + $qr_count + $bank_count;

            if($method_count == 1){
                if($cash_count) $payment_method = 'Cash';
                elseif($qr_count) $payment_method = 'QR';
                elseif($bank_count) $payment_method = 'Bank';
            }

            // Generate payment code (shared for parts)
            $payment_code = $this->generate_reference_code('payment', date('Y-m-d'));
            if(!$payment_code) throw new Exception("No active reference code setting for Payments.");
            $remarks = $remarks ?? '';

            // Insert payment(s) - create one row per non-zero method
            $stmt = $this->conn->prepare("INSERT INTO `transactions` (`reference_code`, `parent_id`, `type`, `total_amount`, `payment_method`, `account_id`, `remarks`, `created_by`, `transaction_date`) VALUES (?, ?, 'payment', ?, ?, ?, ?, ?, NOW())");
            if(!$stmt) throw new Exception('Failed to prepare payment insert statement: ' . $this->conn->error);

            // helper to insert a payment part and update account balance
            $insert_part = function($pstmt, $payment_code, $parent_id, $part_amount, $method_name, $remarks, $user_id, $master){
                if($part_amount <= 0) return true;
                $account_id = ($method_name == 'Cash') ? 1 : (($method_name == 'QR') ? 2 : 3);
                if(!$pstmt->bind_param("sidsisi", $payment_code, $parent_id, $part_amount, $method_name, $account_id, $remarks, $user_id)) return $pstmt->error;
                if(!$pstmt->execute()) return $pstmt->error;
                
                // update account balance
                try{ $master->update_account_balance($account_id); } catch(Throwable $e){}
                return true;
            };

            if($method_count == 1){
                if($cash_count) $part_amount = $cash_amount;
                elseif($qr_count) $part_amount = $qr_amount;
                else $part_amount = $bank_amount;
                $acc_id = ($payment_method == 'Cash') ? 1 : (($payment_method == 'QR') ? 2 : 3);
                if(!$stmt->bind_param("sidsisi", $payment_code, $parent_id, $part_amount, $payment_method, $acc_id, $remarks, $user_id)) throw new Exception($this->conn->error);
                if(!$stmt->execute()) throw new Exception($this->conn->error);
                $first_id = $this->conn->insert_id;
                $this->update_account_balance($acc_id);
                $stmt->close();
            } else {
                $err = $insert_part($stmt, $payment_code, $parent_id, $cash_amount, 'Cash', $remarks, $user_id, $this);
                if($err !== true) throw new Exception($err);
                if(!isset($first_id)) $first_id = $this->conn->insert_id;
                
                $err = $insert_part($stmt, $payment_code, $parent_id, $qr_amount, 'QR', $remarks, $user_id, $this);
                if($err !== true) throw new Exception($err);
                if(!isset($first_id)) $first_id = $this->conn->insert_id;

                $err = $insert_part($stmt, $payment_code, $parent_id, $bank_amount, 'Bank', $remarks, $user_id, $this);
                if($err !== true) throw new Exception($err);
                if(!isset($first_id)) $first_id = $this->conn->insert_id;
                $stmt->close();
            }

            // Search Index Update
            $search_data = implode(' ', [$payment_code, $remarks, $payment_method]);
            $trans_date = date('Y-m-d'); // Payment date is typically current date in this logic
            $this->update_search_index('transactions', $first_id, 'payment', $payment_code, $payment_method, $search_data, $total_payment, $trans_date, "transactions/payments/view_payment&id=$parent_id", "transactions/payments/manage_payment&id=$parent_id");

            // Party Outstanding can be queried live easily now, so we just log success.

            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['msg'] = "Payment successfully recorded.";
            $this->settings->set_flashdata('success', $resp['msg']);
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }
    

    function delete_payment(){
        extract($_POST);
        $id = intval($id);
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("SELECT reference_code FROM `transactions` WHERE id = ? AND type='payment'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $pay = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if(!$pay) {
                throw new Exception("Payment not found.");
            }
            
            $group_code = $pay['reference_code'];

            // Fetch all account IDs involved in this group to update balances later
            $involved_accounts = [];
            $acc_stmt = $this->conn->prepare("SELECT DISTINCT account_id FROM `transactions` WHERE reference_code = ? AND type='payment'");
            $acc_stmt->bind_param("s", $group_code);
            $acc_stmt->execute();
            $acc_qry = $acc_stmt->get_result();
            while($ar = $acc_qry->fetch_assoc()){
                $involved_accounts[] = $ar['account_id'];
            }
            $acc_stmt->close();
            
            // Delete from search index - we need the ID from any row in this group
            $idx_stmt = $this->conn->prepare("SELECT id FROM `transactions` WHERE reference_code = ? AND type='payment' LIMIT 1");
            $idx_stmt->bind_param("s", $group_code);
            $idx_stmt->execute();
            $idx_res = $idx_stmt->get_result()->fetch_assoc();
            $idx_stmt->close();
            if($idx_res) $this->delete_from_index('transactions', $idx_res['id']);

            // Delete associated transactions from transaction_list
            $del_list = $this->conn->prepare("DELETE FROM transaction_list WHERE trans_code = ? AND ref_table = 'transactions'");
            $del_list->bind_param("s", $group_code);
            $del_list->execute();
            $del_list->close();

            // Delete all payments in the group
            $del_pay = $this->conn->prepare("DELETE FROM `transactions` WHERE reference_code = ? AND type='payment'");
            $del_pay->bind_param("s", $group_code);
            $del_pay->execute();
            $del_pay->close();
            
            // Update account balances for all affected accounts
            foreach($involved_accounts as $acc_id){
                if($acc_id > 0) $this->update_account_balance($acc_id);
            }
            
            $this->conn->commit();
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Payment successfully deleted.");
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['error'] = $e->getMessage();
        }
        return json_encode($resp);
    }
    
    // ==================== ACCOUNT FUNCTIONS ====================
    function update_account_balance($account_id){
        $account_id = intval($account_id);
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM transaction_list WHERE account_id = ? AND type = ?");
        
        $type_in = 1;
        $stmt->bind_param("ii", $account_id, $type_in);
        $stmt->execute();
        $inc = $stmt->get_result()->fetch_array()[0];
        
        $type_out = 2;
        $stmt->bind_param("ii", $account_id, $type_out);
        $stmt->execute();
        $exp = $stmt->get_result()->fetch_array()[0];
        $stmt->close();

        $new_bal = floatval($inc) - floatval($exp);
        $upd = $this->conn->prepare("UPDATE account_list SET balance = ? WHERE id = ?");
        $upd->bind_param("di", $new_bal, $account_id);
        $upd->execute();
        $upd->close();
        $this->clear_cache('dashboard');
    }

    function save_transfer(){
        extract($_POST);
        $user_id = $this->settings->userdata('id') ?: 1;
        $this->conn->begin_transaction();
        try {
            $date = !empty($date) ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
            
            if(empty($id)){
                $code = $this->generate_reference_code('transfer', $date);
                if(!$code) throw new Exception("No active reference code setting for Fund Transfers.");
                
                $stmt = $this->conn->prepare("INSERT INTO `transactions` (`reference_code`, `type`, `total_amount`, `remarks`, `created_by`, `transaction_date`) VALUES (?, 'transfer', ?, ?, ?, ?)");
                $stmt->bind_param("sdsis", $code, $amount, $remarks, $user_id, $date);
                if(!$stmt->execute()) throw new Exception("Failed to save master transaction record: " . $stmt->error);
                $tid = $this->conn->insert_id;
                $stmt->close();
            } else {
                $id = intval($id);
                $chk_stmt = $this->conn->prepare("SELECT reference_code FROM transactions WHERE id = ?");
                $chk_stmt->bind_param("i", $id);
                $chk_stmt->execute();
                $chk = $chk_stmt->get_result()->fetch_assoc();
                $chk_stmt->close();
                
                $code = $chk['reference_code'] ?? '';
                $tid = $id;

                $old_accounts = [];
                $old_tl_stmt = $this->conn->prepare("SELECT account_id FROM transaction_list WHERE ref_table = 'transactions' AND ref_id = ?");
                $old_tl_stmt->bind_param("i", $id);
                $old_tl_stmt->execute();
                $old_tl = $old_tl_stmt->get_result();
                while($or = $old_tl->fetch_assoc()){
                    $old_accounts[] = $or['account_id'];
                }
                $old_tl_stmt->close();

                $stmt = $this->conn->prepare("UPDATE `transactions` SET `total_amount`=?, `remarks`=?, `updated_by`=?, `transaction_date`=? WHERE id = ?");
                $stmt->bind_param("dsisi", $amount, $remarks, $user_id, $date, $tid);
                if(!$stmt->execute()) throw new Exception("Failed to save master transaction record: " . $stmt->error);
                $stmt->close();

                $del_tl = $this->conn->prepare("DELETE FROM transaction_list WHERE ref_table = 'transactions' AND ref_id = ?");
                $del_tl->bind_param("i", $tid);
                $del_tl->execute();
                $del_tl->close();
            }

            // 2. Ledger Entries (Dual Entry)
            $stmt = $this->conn->prepare("INSERT INTO `transaction_list` (`account_id`, `amount`, `type`, `trans_code`, `ref_table`, `ref_id`, `date_created`) VALUES (?, ?, ?, ?, 'transactions', ?, ?)");
            $trans_time = $date . date(" H:i:s");
            // FROM Account (Type 2 - Out)
            $type = 2;
            $stmt->bind_param("idisis", $from_account_id, $amount, $type, $code, $tid, $trans_time);
            if(!$stmt->execute()) throw new Exception("Failed to record credit entry: " . $stmt->error);
            
            // TO Account (Type 1 - In)
            $type = 1;
            $stmt->bind_param("idisis", $to_account_id, $amount, $type, $code, $tid, $trans_time);
            if(!$stmt->execute()) throw new Exception("Failed to record debit entry: " . $stmt->error);
            $stmt->close();

            // 3. Update Balances
            if(isset($old_accounts)){
                foreach($old_accounts as $oacc){
                    $this->update_account_balance($oacc);
                }
            }
            $this->update_account_balance($from_account_id);
            $this->update_account_balance($to_account_id);

            $this->conn->commit();
            $this->settings->log_action($user_id, (empty($id) ? "Created" : "Updated")." Fund Transfer Record", "Accounts", $tid);
            $resp['status'] = 'success';
            $resp['msg'] = "Fund transfer recorded successfully.";
            $this->settings->set_flashdata('success', $resp['msg']);
            
            // Search Index Update
            $f_acc_stmt = $this->conn->prepare("SELECT name FROM account_list WHERE id = ?");
            $f_acc_stmt->bind_param("i", $from_account_id);
            $f_acc_stmt->execute();
            $from_acc = $f_acc_stmt->get_result()->fetch_assoc()['name'] ?? 'Account';
            $f_acc_stmt->close();

            $t_acc_stmt = $this->conn->prepare("SELECT name FROM account_list WHERE id = ?");
            $t_acc_stmt->bind_param("i", $to_account_id);
            $t_acc_stmt->execute();
            $to_acc = $t_acc_stmt->get_result()->fetch_assoc()['name'] ?? 'Account';
            $t_acc_stmt->close();

            $search_data = implode(' ', [$code, $remarks, $from_acc, $to_acc]);
            $this->update_search_index('transactions', $tid, 'transfer', $code, "$from_acc -> $to_acc", $search_data, $amount, $date, "master/accounts/transfers", "master/accounts/transfers");
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }

    function delete_transfer(){
        extract($_POST);
        $this->conn->begin_transaction();
        try {
            $id = intval($id);
            $stmt = $this->conn->prepare("SELECT id FROM `transactions` WHERE id = ? AND type='transfer'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if($stmt->get_result()->num_rows <= 0) {
                $stmt->close();
                throw new Exception("Transfer record not found.");
            }
            $stmt->close();
            
            // Get involved accounts
            $involved_accounts = [];
            $acc_stmt = $this->conn->prepare("SELECT DISTINCT account_id FROM `transaction_list` WHERE ref_table = 'transactions' AND ref_id = ?");
            $acc_stmt->bind_param("i", $id);
            $acc_stmt->execute();
            $acc_qry = $acc_stmt->get_result();
            while($ar = $acc_qry->fetch_assoc()) $involved_accounts[] = $ar['account_id'];
            $acc_stmt->close();

            // Delete
            $del_list = $this->conn->prepare("DELETE FROM `transaction_list` WHERE ref_table = 'transactions' AND ref_id = ?");
            $del_list->bind_param("i", $id);
            $del_list->execute();
            $del_list->close();

            $del_txn = $this->conn->prepare("DELETE FROM `transactions` WHERE id = ?");
            $del_txn->bind_param("i", $id);
            $del_txn->execute();
            $del_txn->close();

            // Update Balances
            foreach($involved_accounts as $aid) $this->update_account_balance($aid);

            $this->conn->commit();
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Transfer record deleted.");
            $this->delete_from_index('transactions', $id);
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }

    function save_opening_balance(){
        extract($_POST);
        $user_id = $this->settings->userdata('id') ?: 1;
        $this->conn->begin_transaction();
        try {
            $date = !empty($transaction_date) ? $transaction_date : date('Y-m-d H:i:s');
            $code = $this->generate_reference_code('opening_balance', $date);
            if(!$code){
                throw new Exception("No active reference code setting for Opening Balances.");
            }

            if(isset($account_balances)){
                foreach($account_balances as $aid => $bal){
                    if($bal == 0) continue;
                    $type = ($bal > 0) ? 1 : 2; // Positive = In, Negative = Out
                    $abs_bal = abs($bal);
                    
                    $acode = $this->generate_reference_code('opening_balance', $date);
                    if(!$acode) throw new Exception("No active reference code setting for Opening Balances.");
                    
                    // Create transaction for ledger
                    $txn_stmt = $this->conn->prepare("INSERT INTO `transactions` (`reference_code`, `type`, `total_amount`, `remarks`, `created_by`, `transaction_date`) VALUES (?, 'opening_balance', ?, 'Opening Balance', ?, ?)");
                    $txn_stmt->bind_param("sdis", $acode, $abs_bal, $user_id, $date);
                    $txn_stmt->execute();
                    $tid = $this->conn->insert_id;
                    $txn_stmt->close();
                    
                    $tl_stmt = $this->conn->prepare("INSERT INTO `transaction_list` (`account_id`, `amount`, `type`, `trans_code`, `ref_table`, `ref_id`) VALUES (?, ?, ?, ?, 'transactions', ?)");
                    $tl_stmt->bind_param("idisi", $aid, $abs_bal, $type, $acode, $tid);
                    $tl_stmt->execute();
                    $tl_stmt->close();
                    $this->update_account_balance($aid);
                }
            }

            if(isset($item_stocks)){
                foreach($item_stocks as $iid => $v){
                    $qty = $v['qty'];
                    $cost = $v['cost'];
                    if($qty == 0) continue;
                    
                    $scode = $this->generate_reference_code('opening_stock', $date);
                    if(!$scode) throw new Exception("No active reference code setting for Opening Stock.");
                    
                    $txn_stmt = $this->conn->prepare("INSERT INTO `transactions` (`reference_code`, `type`, `total_amount`, `remarks`, `created_by`, `transaction_date`) VALUES (?, 'opening_stock', ?, 'Opening Stock', ?, ?)");
                    $amt = $qty * $cost;
                    $txn_stmt->bind_param("sdis", $scode, $amt, $user_id, $date);
                    $txn_stmt->execute();
                    $tid = $this->conn->insert_id;
                    $txn_stmt->close();

                    $ti_stmt = $this->conn->prepare("INSERT INTO `transaction_items` (`transaction_id`, `item_id`, `quantity`, `unit_price`, `total_price`) VALUES (?, ?, ?, ?, ?)");
                    $ti_stmt->bind_param("iiddd", $tid, $iid, $qty, $cost, $amt);
                    $ti_stmt->execute();
                    $ti_stmt->close();
                    
                    $this->update_item_stock($iid);
                    $this->update_item_cost($iid);
                }
            }

            if(isset($vendor_balances)){
                foreach($vendor_balances as $vid => $bal){
                    if($bal == 0) continue;
                    $vcode = 'OBV-' . date('YmdHis') . rand(100,999);
                    $v_stmt = $this->conn->prepare("INSERT INTO `transactions` (`reference_code`, `type`, `entity_id`, `total_amount`, `remarks`, `created_by`, `transaction_date`) VALUES (?, 'purchase', ?, ?, 'Opening Balance', ?, ?)");
                    $v_stmt->bind_param("sidds", $vcode, $vid, $bal, $user_id, $date);
                    $v_stmt->execute();
                    $v_stmt->close();
                }
            }

            if(isset($customer_balances)){
                foreach($customer_balances as $cid => $bal){
                    if($bal == 0) continue;
                    $ccode = 'OBC-' . date('YmdHis') . rand(100,999);
                    $c_stmt = $this->conn->prepare("INSERT INTO `transactions` (`reference_code`, `type`, `entity_id`, `total_amount`, `remarks`, `created_by`, `transaction_date`) VALUES (?, 'sale', ?, ?, 'Opening Balance', ?, ?)");
                    $c_stmt->bind_param("sidds", $ccode, $cid, $bal, $user_id, $date);
                    $c_stmt->execute();
                    $c_stmt->close();
                }
            }

            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['msg'] = "Opening balances saved successfully.";
            $this->settings->set_flashdata('success', $resp['msg']);
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }

    /**
     * Update a single opening balance/stock/vendor/customer entry.
     * Accepts id, category (optional) and new values. Adjusts associated
     * account/item balances when necessary.
     */
    function update_opening_balance_entry(){
        extract($_POST);
        $resp = [];
        $this->conn->begin_transaction();
        try {
            if(empty($id)) throw new Exception("Invalid record.");
            $id = intval($id);
            $stmt = $this->conn->prepare("SELECT id, type, total_amount, remarks, transaction_date FROM `transactions` WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $qry = $stmt->get_result();
            if($qry->num_rows <= 0) {
                $stmt->close();
                throw new Exception("Record not found.");
            }
            $row = $qry->fetch_assoc();
            $stmt->close();
            $type = $row['type'];
            $old_info = $row;
            switch($type){
                case 'opening_balance':
                    // account entry
                    $acc_stmt = $this->conn->prepare("SELECT account_id, amount, type FROM transaction_list WHERE ref_table='transactions' AND ref_id = ?");
                    $acc_stmt->bind_param("i", $id);
                    $acc_stmt->execute();
                    $acc_q = $acc_stmt->get_result();
                    if($acc_q->num_rows <= 0) {
                        $acc_stmt->close();
                        throw new Exception("Linked account record missing.");
                    }
                    $acc = $acc_q->fetch_assoc();
                    $acc_stmt->close();
                    $old_account = $acc['account_id'];
                    $old_amount = $acc['amount'];
                    $old_type = $acc['type'];
                    // update transaction date
                    if(isset($transaction_date)){
                        $upd_t = $this->conn->prepare("UPDATE transactions SET transaction_date = ? WHERE id = ?");
                        $upd_t->bind_param("si", $transaction_date, $id);
                        $upd_t->execute();
                        $upd_t->close();
                    }
                    // maybe update account change
                    if(isset($account_id) && $account_id != $old_account){
                        // move the record to new account
                        $upd_acc = $this->conn->prepare("UPDATE transaction_list SET account_id = ? WHERE ref_table='transactions' AND ref_id = ?");
                        $account_id = intval($account_id);
                        $upd_acc->bind_param("ii", $account_id, $id);
                        $upd_acc->execute();
                        $upd_acc->close();
                    }
                    if(isset($amount)){
                        $amt = floatval($amount);
                        $new_type = ($amt >= 0) ? 1 : 2;
                        $upd_tl = $this->conn->prepare("UPDATE transaction_list SET amount = ?, type = ? WHERE ref_table='transactions' AND ref_id = ?");
                        $abs_amt = abs($amt);
                        $upd_tl->bind_param("dii", $abs_amt, $new_type, $id);
                        $upd_tl->execute();
                        $upd_tl->close();

                        $upd_txn = $this->conn->prepare("UPDATE transactions SET total_amount = ? WHERE id = ?");
                        $upd_txn->bind_param("di", $abs_amt, $id);
                        $upd_txn->execute();
                        $upd_txn->close();
                    }
                    // refresh balances for affected accounts
                    $this->update_account_balance(isset($account_id) ? $account_id : $old_account);
                    if(isset($account_id) && $account_id != $old_account){
                        $this->update_account_balance($old_account);
                    }
                    break;
                case 'opening_stock':
                    // stock entry
                    $item_stmt = $this->conn->prepare("SELECT item_id, quantity, unit_price FROM transaction_items WHERE transaction_id = ?");
                    $item_stmt->bind_param("i", $id);
                    $item_stmt->execute();
                    $item_q = $item_stmt->get_result();
                    if($item_q->num_rows <= 0) {
                        $item_stmt->close();
                        throw new Exception("Linked stock record missing.");
                    }
                    $item = $item_q->fetch_assoc();
                    $item_stmt->close();
                    $old_item = $item['item_id'];
                    $old_qty = $item['quantity'];
                    $old_cost = $item['unit_price'];
                    if(isset($transaction_date)){
                        $upd_t = $this->conn->prepare("UPDATE transactions SET transaction_date = ? WHERE id = ?");
                        $upd_t->bind_param("si", $transaction_date, $id);
                        $upd_t->execute();
                        $upd_t->close();
                    }
                    if(isset($item_id) && $item_id != $old_item){
                        $upd_i = $this->conn->prepare("UPDATE transaction_items SET item_id = ? WHERE transaction_id = ?");
                        $item_id = intval($item_id);
                        $upd_i->bind_param("ii", $item_id, $id);
                        $upd_i->execute();
                        $upd_i->close();
                    }
                    if(isset($quantity) || isset($unit_price)){
                        $qty = isset($quantity) ? floatval($quantity) : $old_qty;
                        $price = isset($unit_price) ? floatval($unit_price) : $old_cost;
                        $amt = $qty * $price;
                        $upd_ti = $this->conn->prepare("UPDATE transaction_items SET quantity = ?, unit_price = ?, total_price = ? WHERE transaction_id = ?");
                        $upd_ti->bind_param("dddi", $qty, $price, $amt, $id);
                        $upd_ti->execute();
                        $upd_ti->close();

                        $upd_txn = $this->conn->prepare("UPDATE transactions SET total_amount = ? WHERE id = ?");
                        $upd_txn->bind_param("di", $amt, $id);
                        $upd_txn->execute();
                        $upd_txn->close();
                    }
                    // refresh stock for affected items
                    $item_to_update = isset($item_id) ? $item_id : $old_item;
                    $this->update_item_stock($item_to_update);
                    $this->update_item_cost($item_to_update);
                    
                    if(isset($item_id) && $item_id != $old_item){
                        $this->update_item_stock($old_item);
                        $this->update_item_cost($old_item);
                    }
                    break;
                case 'purchase':
                    if($row['remarks'] !== 'Opening Balance') break;
                    if(isset($transaction_date)){
                        $upd_t = $this->conn->prepare("UPDATE transactions SET transaction_date = ? WHERE id = ?");
                        $upd_t->bind_param("si", $transaction_date, $id);
                        $upd_t->execute();
                        $upd_t->close();
                    }
                    if(isset($amount)){
                        $upd_txn = $this->conn->prepare("UPDATE transactions SET total_amount = ? WHERE id = ?");
                        $amt = floatval($amount);
                        $upd_txn->bind_param("di", $amt, $id);
                        $upd_txn->execute();
                        $upd_txn->close();
                    }
                    break;
                case 'sale':
                    if($row['remarks'] !== 'Opening Balance') break;
                    if(isset($transaction_date)){
                        $upd_t = $this->conn->prepare("UPDATE transactions SET transaction_date = ? WHERE id = ?");
                        $upd_t->bind_param("si", $transaction_date, $id);
                        $upd_t->execute();
                        $upd_t->close();
                    }
                    if(isset($amount)){
                        $upd_txn = $this->conn->prepare("UPDATE transactions SET total_amount = ? WHERE id = ?");
                        $amt = floatval($amount);
                        $upd_txn->bind_param("di", $amt, $id);
                        $upd_txn->execute();
                        $upd_txn->close();
                    }
                    break;
                default:
                    throw new Exception("Unsupported record type for update.");
            }
            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['msg'] = 'Record updated successfully.';
        } catch (Exception $e){
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }

    /**
     * Delete a single opening balance entry regardless of category.
     * Will adjust related balances or stocks just like original creation.
     */
    function delete_opening_balance_entry(){
        extract($_POST);
        $resp = [];
        $this->conn->begin_transaction();
        try{
            if(empty($id)) throw new Exception('Invalid record.');
            $id = intval($id);
            $stmt = $this->conn->prepare("SELECT type, remarks FROM transactions WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $qry = $stmt->get_result();
            if($qry->num_rows <= 0) {
                $stmt->close();
                throw new Exception('Record not found.');
            }
            $row = $qry->fetch_assoc();
            $stmt->close();

            switch($row['type']){
                case 'opening_balance':
                    // delete transaction list entries and update account balances
                    $accs = [];
                    $acc_stmt = $this->conn->prepare("SELECT account_id FROM transaction_list WHERE ref_table='transactions' AND ref_id = ?");
                    $acc_stmt->bind_param("i", $id);
                    $acc_stmt->execute();
                    $acc_q = $acc_stmt->get_result();
                    while($ar = $acc_q->fetch_assoc()) $accs[] = $ar['account_id'];
                    $acc_stmt->close();

                    $del_tl = $this->conn->prepare("DELETE FROM transaction_list WHERE ref_table='transactions' AND ref_id = ?");
                    $del_tl->bind_param("i", $id);
                    $del_tl->execute();
                    $del_tl->close();

                    $del_txn = $this->conn->prepare("DELETE FROM transactions WHERE id = ?");
                    $del_txn->bind_param("i", $id);
                    $del_txn->execute();
                    $del_txn->close();

                    foreach($accs as $aid) $this->update_account_balance($aid);
                    break;
                case 'opening_stock':
                    $items = [];
                    $item_stmt = $this->conn->prepare("SELECT item_id FROM transaction_items WHERE transaction_id = ?");
                    $item_stmt->bind_param("i", $id);
                    $item_stmt->execute();
                    $item_q = $item_stmt->get_result();
                    while($ar = $item_q->fetch_assoc()) $items[] = $ar['item_id'];
                    $item_stmt->close();

                    $del_ti = $this->conn->prepare("DELETE FROM transaction_items WHERE transaction_id = ?");
                    $del_ti->bind_param("i", $id);
                    $del_ti->execute();
                    $del_ti->close();

                    $del_txn = $this->conn->prepare("DELETE FROM transactions WHERE id = ?");
                    $del_txn->bind_param("i", $id);
                    $del_txn->execute();
                    $del_txn->close();

                    foreach($items as $it) $this->update_item_stock($it);
                    break;
                case 'purchase':
                    if($row['remarks'] === 'Opening Balance'){
                        // reuse existing delete_po logic without payments
                        $items_stmt = $this->conn->prepare("SELECT DISTINCT item_id FROM transaction_items WHERE transaction_id = ?");
                        $items_stmt->bind_param("i", $id);
                        $items_stmt->execute();
                        $items_q = $items_stmt->get_result();
                        $affected_items = [];
                        while($irow = $items_q->fetch_assoc()) $affected_items[] = $irow['item_id'];
                        $items_stmt->close();

                        $del_ti = $this->conn->prepare("DELETE FROM transaction_items WHERE transaction_id = ?");
                        $del_ti->bind_param("i", $id);
                        $del_ti->execute();
                        $del_ti->close();

                        $del_txn = $this->conn->prepare("DELETE FROM transactions WHERE id = ? AND type='purchase'");
                        $del_txn->bind_param("i", $id);
                        $del_txn->execute();
                        $del_txn->close();

                        foreach($affected_items as $iid) $this->update_item_stock($iid);
                    } else {
                        throw new Exception('Not an opening balance purchase.');
                    }
                    break;
                case 'sale':
                    if($row['remarks'] === 'Opening Balance'){
                        $del_txn = $this->conn->prepare("DELETE FROM transactions WHERE id = ? AND type='sale'");
                        $del_txn->bind_param("i", $id);
                        $del_txn->execute();
                        $del_txn->close();
                    } else {
                        throw new Exception('Not an opening balance sale.');
                    }
                    break;
                default:
                    throw new Exception('Unsupported record type.');
            }
            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['msg'] = 'Record deleted successfully.';
        } catch(Exception $e){
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }

    // ==================== CATEGORY FUNCTIONS ====================
    function save_category(){
        extract($_POST);
        $user_id = $this->settings->userdata('id') ?: 1;
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $chk_id = !empty($id) ? intval($id) : 0;
        $check_stmt = $this->conn->prepare("SELECT id FROM `category_list` WHERE `name` = ? ".($chk_id > 0 ? " AND id != ?" : ""));
        if ($chk_id > 0) {
            $check_stmt->bind_param("si", $name, $chk_id);
        } else {
            $check_stmt->bind_param("s", $name);
        }
        $check_stmt->execute();
        $check = $check_stmt->get_result()->num_rows;
        $check_stmt->close();

        if($check > 0){
            $resp['status'] = 'failed';
            $resp['msg'] = "Category Name already exists.";
            return json_encode($resp);
        }

        if(empty($id)){
            $stmt = $this->conn->prepare("INSERT INTO `category_list` (`name`, `description`, `status`, `created_by`, `ip_address`) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiis", $name, $description, $status, $user_id, $ip);
        }else{
            $stmt = $this->conn->prepare("UPDATE `category_list` SET `name` = ?, `description` = ?, `status` = ?, `updated_by` = ?, `ip_address` = ? WHERE id = ?");
            $id = intval($id);
            $stmt->bind_param("ssiisi", $name, $description, $status, $user_id, $ip, $id);
        }
        
        if($stmt->execute()){
            $resp['status'] = 'success';
            if(empty($id)) $id = $this->conn->insert_id;
            $stmt->close();
            $this->settings->log_action($user_id, (empty($id) ? "Created" : "Updated")." Category", "Categories", $id);
            $this->settings->set_flashdata('success', "Category successfully saved.");
            
            // Search Index Update
            $this->update_search_index('category_list', $id, 'category', $name, "Item Category", "$name", 0, null, "master/categories", "master/categories/manage&id=$id");
        }else{
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error;
        }
        return json_encode($resp);
    }


    
    function delete_category(){
        extract($_POST);
        $id = intval($id);
        $stmt = $this->conn->prepare("DELETE FROM `category_list` WHERE id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Category successfully deleted.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        $stmt->close();
        return json_encode($resp);
    }
    
    // ==================== ACCOUNT MANAGEMENT ====================
    function save_account(){
        extract($_POST);
        $user_id = $this->settings->userdata('id') ?: 1;
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $chk_id = !empty($id) ? intval($id) : 0;
        $check_stmt = $this->conn->prepare("SELECT id FROM `account_list` WHERE `name` = ? ".($chk_id > 0 ? " AND id != ?" : ""));
        if ($chk_id > 0) {
            $check_stmt->bind_param("si", $name, $chk_id);
        } else {
            $check_stmt->bind_param("s", $name);
        }
        $check_stmt->execute();
        $check = $check_stmt->get_result()->num_rows;
        $check_stmt->close();

        if($check > 0){
            $resp['status'] = 'failed';
            $resp['msg'] = "Account name already exists.";
            return json_encode($resp);
        }
        
        if(empty($id)){
            $stmt = $this->conn->prepare("INSERT INTO `account_list` (`name`, `description`, `account_no`, `bank_name`, `balance`, `status`, `created_by`, `ip_address`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssdiis", $name, $description, $account_no, $bank_name, $balance, $status, $user_id, $ip);
        }else{
            $stmt = $this->conn->prepare("UPDATE `account_list` SET `name` = ?, `description` = ?, `account_no` = ?, `bank_name` = ?, `balance` = ?, `status` = ?, `updated_by` = ?, `ip_address` = ? WHERE id = ?");
            $id = intval($id);
            $stmt->bind_param("ssssdiisi", $name, $description, $account_no, $bank_name, $balance, $status, $user_id, $ip, $id);
        }
        
        if($stmt->execute()){
            $resp['status'] = 'success';
            if(empty($id)) $id = $this->conn->insert_id;
            $stmt->close();
            $this->settings->log_action($user_id, (empty($id) ? "Created" : "Updated")." Account", "Accounts", $id);
            $this->settings->set_flashdata('success', "Account successfully saved.");
            
            // Search Index Update
            $this->update_search_index('account_list', $id, 'account', $name, "Account Balance: $balance", "$name", $balance, null, "master/bank_accounts", "master/bank_accounts/manage&id=$id");
        }else{
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error;
        }
        return json_encode($resp);
    }
        

    
    function delete_account(){
        extract($_POST);
        $id = intval($id);
        $stmt = $this->conn->prepare("DELETE FROM `account_list` WHERE id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Account successfully deleted.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        $stmt->close();
        return json_encode($resp);
    }
    
    // ==================== EXPENSE FUNCTIONS ====================
    function save_expense(){
        extract($_POST);
        $user_id = $this->settings->userdata('id') ?: 1;
        $ip = $_SERVER['REMOTE_ADDR'];
        $status = isset($status) ? intval($status) : 1; // Default to Paid if not set
        // Use provided date or current time
        $date_created = isset($date_created) ? date("Y-m-d H:i:s", strtotime($date_created . " " . date("H:i:s"))) : date("Y-m-d H:i:s");
        $trans_date = date("Y-m-d", strtotime($date_created));

        $this->conn->begin_transaction();
        try {
            if(empty($id)){
                $code = $this->generate_reference_code('expense', $trans_date);
                if(!$code) throw new Exception("No active reference code setting for Expenses.");
                
                // Insert into transactions table WITH reference_code
                $sql = "INSERT INTO `transactions` (`reference_code`, `type`, `account_id`, `total_amount`, `remarks`, `status`, `transaction_date`, `created_by`) VALUES (?, 'expense', ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("sidsisi", $code, $account_id, $amount, $remarks, $status, $trans_date, $user_id);
            }else{
                $sql = "UPDATE `transactions` SET `account_id` = ?, `total_amount` = ?, `remarks` = ?, `status` = ?, `transaction_date` = ?, `updated_by` = ? WHERE id = ? AND type='expense'";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("idsisii", $account_id, $amount, $remarks, $status, $trans_date, $user_id, $id);
            }
            if(!$stmt->execute()) throw new Exception($this->conn->error);
            $expense_id = empty($id) ? $this->conn->insert_id : $id;
            $stmt->close();
            
            // Replicate the transaction entry for the reconciliation module
            if(!empty($id)){
                $id = intval($id);
                $del_stmt = $this->conn->prepare("DELETE FROM transaction_list WHERE ref_id = ? AND ref_table = 'transactions' AND type = 2");
                $del_stmt->bind_param("i", $id);
                $del_stmt->execute();
                $del_stmt->close();
                $this->update_account_balance($account_id);
            }

            // Create transaction entry ONLY if Paid (Status = 1)
            // Keeping transaction_list for backwards compat with daily balances logic for now
            if($status == 1 && $account_id > 0 && $amount > 0){
                $trans_code = $this->generate_reference_code('expense', $date_created);
                if(!$trans_code) throw new Exception("No active reference code setting for Expenses.");
                
                $trans_stmt = $this->conn->prepare("INSERT INTO transaction_list (trans_code, account_id, type, amount, remarks, ref_id, ref_table, date_created) VALUES (?, ?, 2, ?, ?, ?, 'transactions', ?)");
                $trans_stmt->bind_param("sidsis", $trans_code, $account_id, $amount, $combined_remarks, $expense_id, $date_created);
                $trans_stmt->execute();
                $trans_stmt->close();
                $this->update_account_balance($account_id);
            }

            $this->conn->commit();
            $this->update_summary_metrics($date_created);
            $this->settings->log_action($user_id, (empty($id) ? "Created" : "Updated")." Expense", "Expenses", $expense_id);
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Expense successfully saved.");
            
            // Search Index Update
            $acc_stmt = $this->conn->prepare("SELECT name FROM account_list WHERE id = ?");
            $acc_stmt->bind_param("i", $account_id);
            $acc_stmt->execute();
            $acc_name = $acc_stmt->get_result()->fetch_assoc()['name'] ?? 'Account';
            $acc_stmt->close();
            $search_data = implode(' ', [$code, $remarks, $acc_name]);
            $this->update_search_index('transactions', $expense_id, 'expense', $code, $acc_name, $search_data, $amount, $trans_date, "transactions/expenses/view_expense&id=$expense_id", "transactions/expenses/manage_expense&id=$expense_id");
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }
    
    function delete_expense(){
        extract($_POST);
        $id = intval($id);
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("SELECT account_id, transaction_date FROM transactions WHERE id = ? AND type='expense'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $exp = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $trans_date = $exp['transaction_date'] ?? date('Y-m-d');

            $del_stmt = $this->conn->prepare("DELETE FROM transaction_list WHERE ref_id = ? AND ref_table = 'transactions' AND type = 2");
            $del_stmt->bind_param("i", $id);
            $del_stmt->execute();
            $del_stmt->close();

            if(isset($exp['account_id'])) $this->update_account_balance($exp['account_id']);

            $del_txn = $this->conn->prepare("DELETE FROM `transactions` WHERE id = ? AND type='expense'");
            $del_txn->bind_param("i", $id);
            $del_txn->execute();
            $del_txn->close();

            $this->conn->commit();
            if(isset($trans_date)) $this->update_summary_metrics($trans_date);
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Expense successfully deleted.");
            $this->delete_from_index('transactions', $id);
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['error'] = $e->getMessage();
        }
        return json_encode($resp);
    }

    function pay_expense(){
        extract($_POST);
        $id = intval($id);
        $user_id = $this->settings->userdata('id') ?: 1;
        $this->conn->begin_transaction();
        try {
            // Update status
            $upd_stmt = $this->conn->prepare("UPDATE transactions SET status = 1, updated_by = ? WHERE id = ? AND type='expense'");
            $upd_stmt->bind_param("ii", $user_id, $id);
            $upd_stmt->execute();
            $upd_stmt->close();
            
            // Get expense details for transaction
            $exp_stmt = $this->conn->prepare("SELECT account_id, total_amount, remarks, transaction_date FROM transactions WHERE id = ? AND type='expense'");
            $exp_stmt->bind_param("i", $id);
            $exp_stmt->execute();
            $exp = $exp_stmt->get_result()->fetch_assoc();
            $exp_stmt->close();
            
            if($exp['account_id'] > 0 && $exp['total_amount'] > 0){
                $exp_prefix = $this->settings->info('expense_prefix') ?: 'EXP';
                $trans_code = $exp_prefix . '-' . sprintf("%'.04d", $id);
                // Check if transaction already exists (idempotency)
                $chk_stmt = $this->conn->prepare("SELECT 1 FROM transaction_list WHERE ref_id = ? AND ref_table = 'transactions' AND type = 2");
                $chk_stmt->bind_param("i", $id);
                $chk_stmt->execute();
                $chk = $chk_stmt->get_result()->num_rows;
                $chk_stmt->close();
                
                if($chk == 0){
                     $trans_stmt = $this->conn->prepare("INSERT INTO transaction_list (trans_code, account_id, type, amount, remarks, ref_id, ref_table, date_created) VALUES (?, ?, 2, ?, ?, ?, 'transactions', ?)");
                     $trans_stmt->bind_param("sidsis", $trans_code, $exp['account_id'], $exp['total_amount'], $exp['remarks'], $id, $exp['transaction_date']);
                     $trans_stmt->execute();
                     $trans_stmt->close();
                     $this->update_account_balance($exp['account_id']);
                }
            }
            
            $this->conn->commit();
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Expense marked as Paid.");
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }
    
    // ==================== ADJUSTMENT FUNCTIONS ====================
    function save_adjustment(){
        extract($_POST);
        $user_id = $this->settings->userdata('id') ?: 1;
        $transaction_date = date("Y-m-d");
        $amount = isset($amount) ? floatval($amount) : 0;
        $price = isset($price) ? floatval($price) : 0;

        $this->conn->begin_transaction();
        try {
            if(empty($id)){
                $reference_code = $this->generate_reference_code('adjustment', $transaction_date);
                if(!$reference_code) throw new Exception("No active reference code setting for Adjustments.");
                
                $sql = "INSERT INTO `transactions` (`reference_code`, `type`, `remarks`, `transaction_date`, `created_by`, `total_amount`) VALUES (?, 'adjustment', ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("sssid", $reference_code, $remarks, $transaction_date, $user_id, $amount);
            } else {
                $sql = "UPDATE `transactions` SET `remarks` = ?, `total_amount` = ?, `updated_by` = ? WHERE id = ? AND type = 'adjustment'";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("sdii", $remarks, $amount, $user_id, $id);
            }
            
            if(!$stmt->execute()) throw new Exception($this->conn->error);
            $trans_id = empty($id) ? $this->conn->insert_id : $id;
            $stmt->close();

            // Record/Update Adjustment Item
            if(!empty($id)){
                $del_stmt = $this->conn->prepare("DELETE FROM transaction_items WHERE transaction_id = ?");
                $del_stmt->bind_param("i", $trans_id);
                $del_stmt->execute();
                $del_stmt->close();
            }

            $q = ($type == 1) ? floatval($quantity) : -floatval($quantity);
            $ti_stmt = $this->conn->prepare("INSERT INTO `transaction_items` (transaction_id, item_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $ti_stmt->bind_param("iiddd", $trans_id, $item_id, $q, $price, $amount);
            if(!$ti_stmt->execute()) throw new Exception($this->conn->error);
            $ti_stmt->close();

            // Sync item stock quantity and average cost
            $this->update_item_stock($item_id);
            $this->update_item_cost($item_id);

            $this->conn->commit();
            $action = empty($id) ? "Inventory Adjustment recorded" : "Inventory Adjustment updated";
            $this->settings->log_action($user_id, $action, "Adjustment", $trans_id);
            $resp['status'] = 'success';
            
            // Search Index Update
            $item_stmt = $this->conn->prepare("SELECT name FROM item_list WHERE id = ?");
            $item_stmt->bind_param("i", $item_id);
            $item_stmt->execute();
            $item_name = $item_stmt->get_result()->fetch_assoc()['name'] ?? 'Item';
            $item_stmt->close();
            $search_data = implode(' ', [$reference_code, $remarks, $item_name]);
            $this->update_search_index('transactions', $trans_id, 'adjustment', $reference_code, $item_name, $search_data, $amount, $transaction_date, "transactions/stock_adjustments/view_adjustment&id=$trans_id", "transactions/stock_adjustments/manage_adjustment&id=$trans_id");
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }

    function delete_adjustment(){
        extract($_POST);
        $id = intval($id);
        $this->conn->begin_transaction();
        try {
            // Get item IDs to update stock later
            $items_stmt = $this->conn->prepare("SELECT DISTINCT item_id FROM transaction_items WHERE transaction_id = ?");
            $items_stmt->bind_param("i", $id);
            $items_stmt->execute();
            $items_qry = $items_stmt->get_result();
            $affected_items = [];
            while($irow = $items_qry->fetch_assoc()) $affected_items[] = $irow['item_id'];
            $items_stmt->close();

            // Delete related items
            $del_ti = $this->conn->prepare("DELETE FROM `transaction_items` WHERE transaction_id = ?");
            $del_ti->bind_param("i", $id);
            $del_ti->execute();
            $del_ti->close();

            // Delete adjustment record
            $del_txn = $this->conn->prepare("DELETE FROM `transactions` WHERE id = ? AND type='adjustment'");
            $del_txn->bind_param("i", $id);
            $del_txn->execute();
            $del_txn->close();
            
            // Sync item stock quantities
            foreach($affected_items as $iid) $this->update_item_stock($iid);

            $this->conn->commit();
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Adjustment successfully deleted.");
            $this->delete_from_index('transactions', $id);
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['error'] = $e->getMessage();
        }
        return json_encode($resp);
    }
    
    // ==================== RETURN FUNCTIONS ====================
    function save_return(){
        if(empty($_POST['id'])){
            $date = isset($_POST['date_created']) ? $_POST['date_created'] : date('Y-m-d');
            $code = $this->generate_reference_code('return', $date);
            if(!$code) return json_encode(['status'=>'failed', 'msg'=>'No active reference code setting for Returns.']);
            $_POST['return_code'] = $code;
        }
        
        extract($_POST);
        $user_id = $this->settings->userdata('id') ?: 1;
        $transaction_date = isset($date_created) ? $date_created : date("Y-m-d");

        // Backend Output Calculation: ensure amount is calculated from items
        $amount = isset($amount) ? floatval($amount) : 0;
        if(isset($total) && is_array($total)){
            $calc_amount = 0;
            foreach($total as $t){
                $calc_amount += floatval($t);
            }
            if($calc_amount > 0) $amount = $calc_amount;
        }

        $this->conn->begin_transaction();
        try {
            if(empty($id)){
                $sql = "INSERT INTO `transactions` (`reference_code`, `type`, `entity_type`, `entity_id`, `total_amount`, `remarks`, `transaction_date`, `created_by`, `parent_id`) VALUES (?, 'return', ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $p_id = !empty($parent_id) ? $parent_id : null;
                $stmt->bind_param("ssidssii", $return_code, $entity_type, $vendor_id, $amount, $remarks, $transaction_date, $user_id, $p_id);
            }else{
                $sql = "UPDATE `transactions` SET `entity_type` = ?, `entity_id` = ?, `total_amount` = ?, `remarks` = ?, `transaction_date` = ?, `updated_by` = ?, `parent_id` = ? WHERE id = ? AND type='return'";
                $stmt = $this->conn->prepare($sql);
                $p_id = !empty($parent_id) ? $parent_id : null;
                $stmt->bind_param("sidssiii", $entity_type, $vendor_id, $amount, $remarks, $transaction_date, $user_id, $p_id, $id);
            }
            
            if(!$stmt->execute()) throw new Exception($this->conn->error);
            $return_id = empty($id) ? $this->conn->insert_id : $id;
            $stmt->close();
            
            // Handle Items
            $del_items = $this->conn->prepare("DELETE FROM `transaction_items` WHERE transaction_id = ?");
            $del_items->bind_param("i", $return_id);
            $del_items->execute();
            $del_items->close();
            
            if(isset($item_id) && is_array($item_id)){
                $ti_stmt = $this->conn->prepare("INSERT INTO `transaction_items` (`transaction_id`, `item_id`, `quantity`, `unit_price`, `total_price`, `unit`) VALUES (?, ?, ?, ?, ?, ?)");
                foreach($item_id as $k => $v){
                    $ti_qty = floatval($qty[$k]);
                    $ti_price = floatval($price[$k]);
                    $ti_tprice = floatval($total[$k]);
                    $ti_unit = isset($unit[$k]) ? $unit[$k] : '';
                    $ti_stmt->bind_param("iiddds", $return_id, $v, $ti_qty, $ti_price, $ti_tprice, $ti_unit);
                    if(!$ti_stmt->execute()) throw new Exception($this->conn->error);
                }
                $ti_stmt->close();
                
                // Sync item stock quantities
                foreach(array_unique($item_id) as $iid) $this->update_item_stock($iid);
            }

            $this->conn->commit();
            $this->settings->set_flashdata('success', "Return record successfully saved.");
            $resp['status'] = 'success';
            $resp['id'] = $return_id;
            
            // Search Index Update
            $party = 'Internal';
            if(!empty($vendor_id)){
                $pty_stmt = $this->conn->prepare("SELECT display_name FROM entity_list WHERE id = ?");
                $vendor_id = intval($vendor_id);
                $pty_stmt->bind_param("i", $vendor_id);
                $pty_stmt->execute();
                $party = $pty_stmt->get_result()->fetch_assoc()['display_name'] ?? 'Unknown';
                $pty_stmt->close();
            }
            $search_data = implode(' ', [$return_code, $remarks, $party]);
            $this->update_search_index('transactions', $return_id, 'return', $return_code, $party, $search_data, $amount, $transaction_date, "transactions/returns/view_return&id=$return_id", "transactions/returns/manage_return&id=$return_id");
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = "Error: " . $e->getMessage();
        }
        return json_encode($resp);
    }
    
    function delete_return(){
        extract($_POST);
        $id = intval($id);
        $this->conn->begin_transaction();
        try {
            // Get item IDs to update stock later
            $items_stmt = $this->conn->prepare("SELECT DISTINCT item_id FROM transaction_items WHERE transaction_id = ?");
            $items_stmt->bind_param("i", $id);
            $items_stmt->execute();
            $items_qry = $items_stmt->get_result();
            $affected_items = [];
            while($irow = $items_qry->fetch_assoc()) $affected_items[] = $irow['item_id'];
            $items_stmt->close();

            $del_ti = $this->conn->prepare("DELETE FROM `transaction_items` WHERE transaction_id = ?");
            $del_ti->bind_param("i", $id);
            $del_ti->execute();
            $del_ti->close();

            $del_txn = $this->conn->prepare("DELETE FROM `transactions` WHERE id = ? AND type='return'");
            $del_txn->bind_param("i", $id);
            if($del_txn->execute()){
                $del_txn->close();
                // Sync item stock quantities
                foreach($affected_items as $iid) $this->update_item_stock($iid);

                $this->conn->commit();
                $resp['status'] = 'success';
                $this->settings->set_flashdata('success', "Return successfully deleted.");
                $this->delete_from_index('transactions', $id);
            }else{
                $err = $del_txn->error;
                $del_txn->close();
                throw new Exception($err);
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }
    

    // ==================== DENOMINATION (CASH COUNTING) FUNCTIONS ====================

    /**
     * Upsert daily account balance snapshots.
     * @param string $date        Y-m-d
     * @param array  $balances    [ account_id => actual_balance ]
     * @param int    $denom_id    cash_denominations.id (for reference)
     */
    private function save_daily_balances($date, $balances, $denom_id){
        if(empty($balances) || !is_array($balances)) return;
        
        $acc_stmt = $this->conn->prepare("SELECT name FROM account_list WHERE id = ?");
        $ins_stmt = $this->conn->prepare("INSERT INTO `daily_account_balances` (`balance_date`, `account_id`, `account_name`, `balance`, `source`, `denomination_id`) VALUES (?, ?, ?, ?, 'denomination', ?) ON DUPLICATE KEY UPDATE `balance` = VALUES(`balance`), `account_name` = VALUES(`account_name`), `denomination_id` = VALUES(`denomination_id`), `date_recorded` = NOW()");

        foreach($balances as $acc_id => $bal){
            $acc_id = intval($acc_id);
            if($acc_id <= 0) continue;
            $bal = floatval($bal);
            
            $acc_stmt->bind_param("i", $acc_id);
            $acc_stmt->execute();
            $acc_name = $acc_stmt->get_result()->fetch_assoc()['name'] ?? 'Unknown';
            
            $ins_stmt->bind_param("sisdi", $date, $acc_id, $acc_name, $bal, $denom_id);
            $ins_stmt->execute();
        }
        $acc_stmt->close();
        $ins_stmt->close();
    }

function save_denomination(){
    extract($_POST);
    $user_id = $this->settings->userdata('id') ?: 1;

    try {
        // Date from post or today
        $date = isset($date) && !empty($date) ? $date : date("Y-m-d");

        // Block saving if this date is already finalized
        if(!empty($id)){
            $id = intval($id);
            $chk_stmt = $this->conn->prepare("SELECT reconciliation_status FROM `cash_denominations` WHERE id = ? LIMIT 1");
            $chk_stmt->bind_param("i", $id);
            $chk_stmt->execute();
            $chk = $chk_stmt->get_result();
            if($chk && $chk->num_rows > 0){
                $row = $chk->fetch_assoc();
                if($row['reconciliation_status'] == 1){
                    $chk_stmt->close();
                    return json_encode(['status'=>'failed','msg'=>'This record is finalized and cannot be modified.']);
                }
            }
            $chk_stmt->close();
        } else {
            // For new records, check if this date already has a finalized record
            $chk_stmt = $this->conn->prepare("SELECT id FROM `cash_denominations` WHERE COALESCE(`date`, DATE(`date_created`)) = ? AND reconciliation_status = 1 LIMIT 1");
            $chk_stmt->bind_param("s", $date);
            $chk_stmt->execute();
            $chk = $chk_stmt->get_result();
            if($chk && $chk->num_rows > 0){
                $chk_stmt->close();
                return json_encode(['status'=>'failed','msg'=>'This date is already finalized. No new records can be added.']);
            }
            $chk_stmt->close();
        }

        if(empty($_POST['id'])){
            $prefix = "CD";
            $qry = $this->conn->query("SELECT cd_code FROM `cash_denominations` WHERE cd_code LIKE '{$prefix}-%' ORDER BY cd_code DESC LIMIT 1");
            $next = 1;
            if($qry->num_rows > 0){
                $last_code = $qry->fetch_assoc()['cd_code'];
                $last_num = intval(substr($last_code, strpos($last_code, '-') + 1));
                $next = $last_num + 1;
            }
            $cd_code = $prefix . "-" . sprintf("%'.04d", $next);
        }

        // Prepare denominations JSON
        $counts = isset($counts) && is_array($counts) ? $counts : [];
        $denominations_json = json_encode($counts);

        // Prepare account balances JSON
        $account_balances = isset($account_balances) && is_array($account_balances) ? $account_balances : [];
        $account_balances_json = json_encode($account_balances);

        $difference = isset($difference) ? $difference : 0;

        if(empty($id)){
            $sql = "INSERT INTO `cash_denominations` (`user_id`, `cd_code`, `date`, `denominations`, `expected_amount`, `total_amount`, `difference`, `account_balances`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            if(!$stmt) throw new Exception("Prepare failed: " . $this->conn->error);
            $stmt->bind_param("isssddds", $user_id, $cd_code, $date, $denominations_json, $expected_amount, $total_amount, $difference, $account_balances_json);
        }else{
            $sql = "UPDATE `cash_denominations` SET `date` = ?, `denominations` = ?, `expected_amount` = ?, `total_amount` = ?, `difference` = ?, `account_balances` = ?, `updated_by` = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if(!$stmt) throw new Exception("Prepare failed: " . $this->conn->error);
            $stmt->bind_param("ssdddsii", $date, $denominations_json, $expected_amount, $total_amount, $difference, $account_balances_json, $user_id, $id);
        }
        
        if(!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
        
        $id = empty($id) ? $this->conn->insert_id : $id;
        $stmt->close();

        // Snapshot account balances into daily_account_balances
        if(!empty($account_balances) && is_array($account_balances)){
            $this->save_daily_balances($date, $account_balances, $id);
        }
        
        $this->settings->log_action($user_id, (empty($_POST['id']) ? "Created" : "Updated")." Cash Denomination Record", "Cash Counting", $id);
        return json_encode(['status'=>'success','msg'=>'Cash denomination record saved successfully.','id'=>$id]);
        
    } catch(Exception $e){
        error_log("Save Denomination Error: " . $e->getMessage());
        return json_encode(['status'=>'failed','msg'=>'An error occurred: ' . $e->getMessage()]);
    }
}

function check_reconciliation_status(){
    extract($_POST);
    if(!isset($date) || empty($date)){
        echo json_encode(['status'=>'error','msg'=>'Date is required.']);
        exit;
    }
    
    $stmt = $this->conn->prepare("SELECT id, reconciliation_status, finalized_at, finalized_by FROM `cash_denominations` WHERE COALESCE(`date`, DATE(`date_created`)) = ? LIMIT 1");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $qry = $stmt->get_result();

    if($qry && $qry->num_rows > 0){
        $row = $qry->fetch_assoc();
        $is_finalized = intval($row['reconciliation_status']) === 1;
        echo json_encode([
            'status' => 'success',
            'is_finalized' => $is_finalized,
            'denomination_id' => $row['id'],
            'finalized_at' => $row['finalized_at'],
            'msg' => $is_finalized ? 'This date is already reconciled. Data entry is not allowed.' : ''
        ]);
    } else {
        echo json_encode(['status'=>'success','is_finalized'=>false,'denomination_id'=>null,'msg'=>'']);
    }
    $stmt->close();
    exit;
}

function finalize_reconciliation(){
    extract($_POST);
    if(!isset($id) || empty($id)){
        return json_encode(['status'=>'failed','msg'=>'Record ID is required.']);
    }
    $id = intval($id);
    $user_id = $this->settings->userdata('id') ?: 1;

    // Check it exists and is not already finalized
    $chk_stmt = $this->conn->prepare("SELECT id, reconciliation_status FROM `cash_denominations` WHERE id = ? LIMIT 1");
    $chk_stmt->bind_param("i", $id);
    $chk_stmt->execute();
    $chk = $chk_stmt->get_result();
    if(!$chk || $chk->num_rows == 0){
        $chk_stmt->close();
        return json_encode(['status'=>'failed','msg'=>'Record not found.']);
    }
    $row = $chk->fetch_assoc();
    $chk_stmt->close();
    if($row['reconciliation_status'] == 1){
        return json_encode(['status'=>'failed','msg'=>'This record is already finalized.']);
    }

    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE `cash_denominations` SET `reconciliation_status` = 1, `finalized_by` = ?, `finalized_at` = ? WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    if(!$stmt) return json_encode(['status'=>'failed','msg'=>'Prepare failed: '.$this->conn->error]);
    $stmt->bind_param("isi", $user_id, $now, $id);
    if(!$stmt->execute()) return json_encode(['status'=>'failed','msg'=>'Execute failed: '.$stmt->error]);
    $stmt->close();

    // Re-snapshot account balances on finalize to lock them in
    $denom_stmt = $this->conn->prepare("SELECT `date`, `account_balances` FROM `cash_denominations` WHERE id = ? LIMIT 1");
    $denom_stmt->bind_param("i", $id);
    $denom_stmt->execute();
    $denom_row = $denom_stmt->get_result();
    if($denom_row && $denom_row->num_rows > 0){
        $denom_data = $denom_row->fetch_assoc();
        $bal_arr = json_decode($denom_data['account_balances'], true);
        if(!empty($bal_arr) && is_array($bal_arr)){
            $this->save_daily_balances($denom_data['date'], $bal_arr, $id);
        }
    }
    $denom_stmt->close();

    $this->settings->log_action($user_id, "Finalized Cash Denomination Record", "Cash Counting", $id);
    return json_encode(['status'=>'success','msg'=>'Reconciliation finalized successfully. This date is now locked.']);
}

function delete_denomination(){
    extract($_POST);
    $id = intval($id);
    $user_id = $this->settings->userdata('id') ?: 1;
    $stmt = $this->conn->prepare("DELETE FROM `cash_denominations` WHERE id = ?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()){
        $this->settings->log_action($user_id, "Deleted Cash Denomination Record", "Cash Counting", $id);
        $resp['status'] = 'success';
        $this->settings->set_flashdata('success', "Cash denomination record successfully deleted.");
    }else{
        $resp['status'] = 'failed';
        $resp['error'] = $this->conn->error;
    }
    $stmt->close();
    return json_encode($resp);
}

function save_ref_code_setting(){
    extract($_POST);
    $data = "";
    foreach($_POST as $k =>$v){
        if(!in_array($k,array('id'))){
            if(!empty($data)) $data .=",";
            $data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
        }
    }
    if(empty($id)){
        $sql = "INSERT INTO `reference_code_settings` set {$data} ";
    }else{
        $sql = "UPDATE `reference_code_settings` set {$data} where id = '{$id}' ";
    }
    $save = $this->conn->query($sql);
    if($save){
        $resp['status'] = 'success';
        if(empty($id))
            $this->settings->set_flashdata('success',"Reference Code Setting successfully saved.");
        else
            $this->settings->set_flashdata('success',"Reference Code Setting successfully updated.");
    }else{
        $resp['status'] = 'failed';
        $resp['err'] = $this->conn->error."[{$sql}]";
    }
    return json_encode($resp);
}

function save_bulk_ref_codes(){
    extract($_POST);
    if(isset($settings) && is_array($settings)){
        $this->conn->begin_transaction();
        try {
            foreach($settings as $id => $data){
                $id = intval($id);
                $update_data = "";
                foreach($data as $k => $v){
                    if(!empty($update_data)) $update_data .=",";
                    $update_data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
                }
                $sql = "UPDATE `reference_code_settings` set {$update_data} where id = '{$id}' ";
                $save = $this->conn->query($sql);
                if(!$save) throw new Exception($this->conn->error . " [{$sql}]");
            }
            $this->conn->commit();
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"All Reference Code Settings successfully updated.");
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }
    return json_encode(['status'=>'failed', 'msg'=>'No settings data received.']);
}

    function get_next_ref_code(){
        extract($_POST);
        if(!isset($type) || empty($type)){
            return json_encode(['status'=>'failed','msg'=>'Transaction type is required.']);
        }
        $date = isset($date) && !empty($date) ? $date : date('Y-m-d');
        $res = $this->generate_reference_code($type, $date, false);
        if($res){
            return json_encode(['status'=>'success','code'=>$res]);
        }else{
            return json_encode(['status'=>'failed','msg'=>'No active reference code setting found for this type/date.']);
        }
    }

    /**
     * Internal method to generate the code
     * @param string $type transaction_type
     * @param string $date transaction_date
     * @param bool $increment whether to update the next_number in DB
     */
    protected function generate_reference_code($type, $date, $increment = true){
        $date = date('Y-m-d', strtotime($date));
        
        // Search for specific date range first
        $stmt = $this->conn->prepare("SELECT id, next_number, padding, prefix, suffix FROM `reference_code_settings` WHERE `transaction_type` = ? AND `status` = 1 AND (start_date <= ? AND end_date >= ?) LIMIT 1");
        $stmt->bind_param("sss", $type, $date, $date);
        $stmt->execute();
        $qry = $stmt->get_result();

        // Fallback to if dates are NULL (general setting)
        if($qry->num_rows <= 0){
             $stmt->close();
             $stmt = $this->conn->prepare("SELECT id, next_number, padding, prefix, suffix FROM `reference_code_settings` WHERE `transaction_type` = ? AND `status` = 1 AND (start_date IS NULL OR start_date = '0000-00-00') LIMIT 1");
             $stmt->bind_param("s", $type);
             $stmt->execute();
             $qry = $stmt->get_result();
        }

        if($qry->num_rows > 0){
            $row = $qry->fetch_assoc();
            $stmt->close();
            $num = $row['next_number'];
            $padding = $row['padding'];
            $prefix = $row['prefix'];
            $suffix = $row['suffix'];
            
            $formatted_num = str_pad($num, $padding, '0', STR_PAD_LEFT);
            $code = $prefix . $formatted_num . $suffix;
            
            if($increment){
                $new_next = $num + 1;
                $upd = $this->conn->prepare("UPDATE `reference_code_settings` SET `current_number` = `next_number`, `next_number` = ? WHERE id = ?");
                $upd->bind_param("ii", $new_next, $row['id']);
                $upd->execute();
                $upd->close();
            }
            return $code;
        }
        $stmt->close();
        return false;
    }

    function get_daily_balances(){
    extract($_POST);
    if(!isset($date) || empty($date)){
        echo json_encode(['error'=>'date_required','msg'=>'Date is required.']);
        exit;
    }
    
    $stmt = $this->conn->prepare("SELECT dab.account_id, dab.account_name, dab.balance, dab.balance_date, dab.date_recorded FROM daily_account_balances dab WHERE dab.balance_date = ? ORDER BY dab.account_name ASC");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $qry = $stmt->get_result();

    $rows = [];
    while($r = $qry->fetch_assoc()){
        $rows[$r['account_id']] = [
            'name'    => $r['account_name'],
            'balance' => floatval($r['balance']),
            'date'    => $r['balance_date'],
            'recorded_at' => $r['date_recorded']
        ];
    }
    if(empty($rows)){
        echo json_encode(['error'=>'no_data','msg'=>'No recorded balances found for this date.']);
    } else {
        echo json_encode(['status'=>'success','accounts'=>$rows]);
    }
    $stmt->close();
    exit;
}

function get_balances_by_date(){
    extract($_POST);
    if(!isset($date) || empty($date)) $date = date("Y-m-d");
    
    try {
        // Check if we have pre-recorded daily balances first (fast path)
        $daily_stmt = $this->conn->prepare("SELECT account_id, account_name, balance FROM daily_account_balances WHERE balance_date = ?");
        $daily_stmt->bind_param("s", $date);
        $daily_stmt->execute();
        $daily_check = $daily_stmt->get_result();

        if($daily_check && $daily_check->num_rows > 0){
            $account_data = ['accounts' => [], 'net_movement' => 0, 'from_daily_record' => true];
            while($dr = $daily_check->fetch_assoc()){
                $account_data['accounts'][$dr['account_id']] = [
                    'name'    => $dr['account_name'],
                    'balance' => floatval($dr['balance'])
                ];
            }
            $daily_stmt->close();

            // Also compute net movement for the day for cash accounts
            $day_start = $date . ' 00:00:00';
            $day_end   = $date . ' 23:59:59';
            $exp_stmt = $this->conn->prepare("SELECT SUM(CASE WHEN type=1 THEN amount WHEN type=2 THEN -amount ELSE 0 END) FROM transaction_list WHERE date_created BETWEEN ? AND ? AND account_id IN (SELECT id FROM account_list WHERE name LIKE '%Cash%' AND status=1)");
            $exp_stmt->bind_param("ss", $day_start, $day_end);
            $exp_stmt->execute();
            $res = $exp_stmt->get_result()->fetch_array()[0];
            $account_data['net_movement'] = $res ? floatval($res) : 0;
            $exp_stmt->close();

            echo json_encode($account_data);
            exit;
        }
        $daily_stmt->close();

        // Check if any transactions exist on OR BEFORE the selected date (use indexed range)
        $target_end = $date . ' 23:59:59';
        $check_stmt = $this->conn->prepare("SELECT 1 FROM `transaction_list` WHERE date_created <= ? LIMIT 1");
        $check_stmt->bind_param("s", $target_end);
        $check_stmt->execute();
        if($check_stmt->get_result()->num_rows == 0){
             $check_stmt->close();
             echo json_encode(array('error' => 'no_data', 'msg' => 'No data found or account balances are not recorded on this date please try another date'));
             exit;
        }
        $check_stmt->close();

        // Fetch all active accounts
        $accounts_qry = $this->conn->query("SELECT id, name, balance FROM `account_list` WHERE status = 1");
        if(!$accounts_qry){
             throw new Exception("Error fetching accounts: " . $this->conn->error);
        }
        
        // Get all transactions AFTER the target date in ONE query (Batch Optimization)
        $target_next_day = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';
        $after_stmt = $this->conn->prepare("SELECT 
            account_id,
            SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) as expense
            FROM `transaction_list` 
            WHERE date_created >= ?
            GROUP BY account_id");
        $after_stmt->bind_param("s", $target_next_day);
        $after_stmt->execute();
        $after_trans_qry = $after_stmt->get_result();
        
        $after_data = array();
        while($at = $after_trans_qry->fetch_assoc()){
            $after_data[$at['account_id']] = $at;
        }
        $after_stmt->close();

        $account_data = array();
        $account_data['accounts'] = array();
        
        while($row = $accounts_qry->fetch_assoc()){
            $account_id = $row['id'];
            $current_balance = floatval($row['balance']);
            
            $income_after = isset($after_data[$account_id]) ? floatval($after_data[$account_id]['income']) : 0;
            $expense_after = isset($after_data[$account_id]) ? floatval($after_data[$account_id]['expense']) : 0;
            
            // Historical Balance = Current - (Income after date) + (Expense after date)
            $historical_balance = $current_balance - $income_after + $expense_after;
            
            $account_data['accounts'][$account_id] = array(
                'name' => $row['name'],
                'balance' => $historical_balance
            );
        }

        // Calculate Day's Expected Movement for Cash accounts (Optimized)
        $day_start = $date . ' 00:00:00';
        $day_end = $date . ' 23:59:59';
        $expected_stmt = $this->conn->prepare("SELECT SUM(CASE WHEN type = 1 THEN amount WHEN type = 2 THEN -amount ELSE 0 END) FROM transaction_list WHERE date_created BETWEEN ? AND ? AND account_id IN (SELECT id FROM account_list WHERE name LIKE '%Cash%' AND status = 1)");
        $expected_stmt->bind_param("ss", $day_start, $day_end);
        $expected_stmt->execute();
        $expected = $expected_stmt->get_result()->fetch_array()[0];
        $account_data['net_movement'] = $expected ? floatval($expected) : 0;
        $expected_stmt->close();
        
        echo json_encode($account_data);
        exit;
    } catch(Exception $e){
        echo json_encode(array('error' => 'exception', 'msg' => $e->getMessage()));
        exit;
    }
}

    function get_outstanding_bills(){
        extract($_POST);
        $type = isset($type) ? intval($type) : 1; // 1=Sales, 2=Purchase
        $party_id = isset($party_id) ? intval($party_id) : 0;
        $payment_code = isset($payment_code) ? $payment_code : null;
        $p_code_sql = !empty($payment_code) ? "'{$this->conn->real_escape_string($payment_code)}'" : "NULL";
        
        // Use Stored Procedure for performance
        $sql = "CALL sp_get_outstanding_bills({$type}, {$party_id}, {$p_code_sql})";
        
        $qry = $this->conn->query($sql);
        
        if(!$qry){
            $err = $this->conn->error;
            error_log("SQL Error in get_outstanding_bills: " . $err);
            return json_encode(['status'=>'failed', 'msg'=>'Database Error: ' . $err]);
        }

        $data = [];
        while($row = $qry->fetch_assoc()){
            $row['outstanding'] = floatval($row['outstanding']);
            $row['amount'] = floatval($row['amount']);
            $row['date'] = date("Y-m-d", strtotime($row['date_created']));
            $row['code'] = $row['code']; // SP aliases it correctly
            $data[] = $row;
        }
        
        // IMPORTANT: Clean up stored procedure results (consume extra result sets)
        while($this->conn->more_results()){
            $this->conn->next_result();
        }
        return json_encode(['status'=>'success', 'data'=>$data]);
    }

    function save_bulk_payment(){
        extract($_POST);
        $user_id = $this->settings->userdata('id') ?: 1;
        
        $this->conn->begin_transaction();
        try {
            // Pool of funds from dynamic inputs
            $funds = []; // account_id => amount
            if(isset($account_amounts) && is_array($account_amounts)){
                foreach($account_amounts as $acc_id => $amt){
                    if(floatval($amt) > 0){
                        $funds[$acc_id] = floatval($amt);
                    }
                }
            }
            
            if(empty($funds)){
                 throw new Exception("No payment amount entered.");
            }
            
            if(!isset($invoices) || !is_array($invoices)){
                 throw new Exception("No invoices selected for payment.");
            }
            
            // Fetch Account Names for logging/history
            $acc_names = [];
            $acc_qry = $this->conn->query("SELECT id, name FROM account_list");
            while($r = $acc_qry->fetch_assoc()){
                $acc_names[$r['id']] = $r['name'];
            }
            $total_payment = 0;
            $payment_methods = [];
            
            // Payment code group - sequential implementation or edit existing
            if(!empty($id)){
                $id = intval($id);
                $stmt = $this->conn->prepare("SELECT created_by, transaction_date as date_created, reference_code as payment_code FROM `transactions` WHERE id = ? AND type='payment'");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $old_pay = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if($old_pay){
                    $group_code = $old_pay['payment_code'];
                    $original_creator = $old_pay['created_by'];
                    $original_date = $old_pay['date_created'];
                    $has_edit = true;
                    
                    // Identify and cleanup previous transactions for this group
                    $old_accounts = [];
                    $oacc_stmt = $this->conn->prepare("SELECT DISTINCT account_id FROM `transactions` WHERE reference_code = ? AND type='payment'");
                    $oacc_stmt->bind_param("s", $group_code);
                    $oacc_stmt->execute();
                    $oacc_qry = $oacc_stmt->get_result();
                    while($oar = $oacc_qry->fetch_assoc()){
                        $old_accounts[] = $oar['account_id'];
                    }
                    $oacc_stmt->close();
                    
                    $del_tl = $this->conn->prepare("DELETE FROM `transaction_list` WHERE trans_code = ? AND ref_table = 'transactions'");
                    $del_tl->bind_param("s", $group_code);
                    $del_tl->execute();
                    $del_tl->close();

                    $del_txn = $this->conn->prepare("DELETE FROM `transactions` WHERE reference_code = ? AND type='payment'");
                    $del_txn->bind_param("s", $group_code);
                    $del_txn->execute();
                    $del_txn->close();
                    
                    // Revert old account balances
                    foreach($old_accounts as $oacc_id){
                        if($oacc_id > 0) $this->update_account_balance($oacc_id);
                    }
                }
            }

            if(!isset($group_code)){
                $pdate = !empty($date_created) ? $date_created : date('Y-m-d');
                $generated = $this->generate_reference_code('payment', $pdate);
                if(!$generated){
                     throw new Exception("No active reference code setting for Payments.");
                }
                $group_code = $generated;
            }
            
            foreach($invoices as $idx => $inv_id){
                $alloc_amount = isset($allocation[$idx]) ? floatval($allocation[$idx]) : 0;
                
                if($alloc_amount <= 0) continue;
                
                foreach($funds as $acc_id => $available){
                    if($alloc_amount <= 0.001) break; 
                    if($available <= 0) continue; 
                    
                    $pay = min($alloc_amount, $available);
                    
                    $method_name = $acc_names[$acc_id] ?? 'Unknown';
                    
                    $p_creator = isset($original_creator) ? $original_creator : $user_id;
                    if(!empty($date_created)){
                        $p_date = $date_created;
                        if(strlen($p_date) == 10) $p_date_full = $p_date . date(" H:i:s");
                        else $p_date_full = $p_date;
                    } else {
                        $p_date_full = isset($original_date) ? $original_date : date("Y-m-d H:i:s");
                        $p_date = date("Y-m-d", strtotime($p_date_full));
                    }
                    $p_updater = isset($has_edit) ? $user_id : null;

                    $stmt = $this->conn->prepare("INSERT INTO `transactions` (`reference_code`, `type`, `parent_id`, `entity_id`, `account_id`, `total_amount`, `remarks`, `transaction_date`, `created_by`, `updated_by`) VALUES (?, 'payment', ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("siiidsssi", $group_code, $inv_id, $party_id, $acc_id, $pay, $remarks, $p_date, $p_creator, $p_updater);
                    if(!$stmt->execute()) throw new Exception($stmt->error);
                    
                    $payment_id = $this->conn->insert_id;
                    if(!isset($first_id)) $first_id = $payment_id;

                    // Sync with Account Balance via transaction_list
                    $trans_stmt = $this->conn->prepare("INSERT INTO transaction_list (trans_code, account_id, type, amount, remarks, ref_id, ref_table, date_created) VALUES (?, ?, ?, ?, ?, ?, 'transactions', ?)");
                    $trans_stmt->bind_param("siidsis", $group_code, $acc_id, $type, $pay, $remarks, $payment_id, $p_date_full);
                    $trans_stmt->execute();
                    $trans_stmt->close();

                    $this->update_account_balance($acc_id);
                    
                    $total_payment += $pay;
                    if(!in_array($method_name, $payment_methods)) $payment_methods[] = $method_name;
                    $alloc_amount -= $pay;
                    $funds[$acc_id] -= $pay;
                }
            }
            
            $this->conn->commit();

            // Search Index Update for Bulk Payment
            if(isset($first_id)){
                $pty_stmt = $this->conn->prepare("SELECT display_name FROM entity_list WHERE id = ?");
                $pty_stmt->bind_param("i", $party_id);
                $pty_stmt->execute();
                $party = $pty_stmt->get_result()->fetch_assoc()['display_name'] ?? 'Unknown';
                $pty_stmt->close();
                
                $methods_str = implode(', ', $payment_methods);
                $search_data = implode(' ', [$group_code, $remarks ?? '', $party, $methods_str]);
                $p_date = !empty($date_created) ? $date_created : date('Y-m-d');
                $this->update_search_index('transactions', $first_id, 'payment', $group_code, $party, $search_data, $total_payment, $p_date, "transactions/payments/view_payment&id=$first_id", "transactions/payments/manage_payment&id=$first_id");
            }

            return json_encode(['status'=>'success', 'id' => $first_id ?? 0]);
            
        } catch(Exception $e){
            $this->conn->rollback();
            return json_encode(['status'=>'failed', 'msg'=>$e->getMessage()]);
        }
    }

    public function update_summary_metrics($date) {
        if (empty($date)) return;
        $date = date("Y-m-d", strtotime($date));
        $month = (int)date("m", strtotime($date));
        $year = (int)date("Y", strtotime($date));

        // Delete existing daily record
        $stmt = $this->conn->prepare("DELETE FROM summary_daily_metrics WHERE metric_date = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();

        // Calculate and Insert new daily record
        $sql = "INSERT INTO summary_daily_metrics (metric_date, total_sales, total_purchases, total_expenses, total_discount, total_cogs, net_profit)
                SELECT 
                    ?,
                    COALESCE(SUM(CASE WHEN type = 'sale' THEN total_amount ELSE 0 END), 0),
                    COALESCE(SUM(CASE WHEN type = 'purchase' THEN total_amount ELSE 0 END), 0),
                    COALESCE(SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END), 0),
                    COALESCE(SUM(CASE WHEN type = 'sale' THEN discount ELSE 0 END), 0),
                    COALESCE((SELECT SUM(ti.quantity * i.cost) FROM transaction_items ti JOIN transactions t2 ON ti.transaction_id = t2.id JOIN item_list i ON ti.item_id = i.id WHERE t2.type = 'sale' AND DATE(t2.transaction_date) = ?), 0),
                    0
                FROM transactions 
                WHERE DATE(transaction_date) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $date, $date, $date);
        $stmt->execute();
        
        $stmt = $this->conn->prepare("UPDATE summary_daily_metrics SET net_profit = total_sales - total_cogs - total_expenses WHERE metric_date = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();

        // Update monthly summary
        $stmt = $this->conn->prepare("DELETE FROM summary_monthly_metrics WHERE metric_year = ? AND metric_month = ?");
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        
        $sql_m = "INSERT INTO summary_monthly_metrics (metric_year, metric_month, total_sales, total_purchases, total_expenses, total_discount, total_cogs, net_profit)
                  SELECT 
                    ?, ?,
                    SUM(total_sales), SUM(total_purchases), SUM(total_expenses), SUM(total_discount), SUM(total_cogs), SUM(net_profit)
                  FROM summary_daily_metrics
                  WHERE YEAR(metric_date) = ? AND MONTH(metric_date) = ?";
        $stmt = $this->conn->prepare($sql_m);
        $stmt->bind_param("iiii", $year, $month, $year, $month);
        $stmt->execute();
        
        // Clear relevant caches
        if(file_exists(__DIR__ . '/Cache.php')){
            require_once __DIR__ . '/Cache.php';
            $cache = new Cache();
            $cache->delete('dashboard_kpi_metrics');
            $cache->delete('monthly_sales_purchase_api');
        }
    }
    function search_pos_items(){
        extract($_GET);
        $q = isset($q) ? $q : '';
        $data = array();
        $qry = $this->conn->query("SELECT i.*, c.name as category FROM `item_list` i LEFT JOIN category_list c ON i.category_id = c.id WHERE i.status = 1 AND (i.name LIKE '%{$q}%' OR i.description LIKE '%{$q}%' OR c.name LIKE '%{$q}%') ORDER BY i.name ASC LIMIT 20");
        while($row = $qry->fetch_assoc()){
            $data[] = array(
                'id' => $row['id'],
                'text' => $row['name'],
                'price' => $row['selling_price'],
                'mrp' => $row['mrp'],
                'available' => $row['quantity'],
                'category' => $row['category']
            );
        }
        return json_encode(array('results' => $data));
    }

    function search_parties(){
        extract($_GET);
        $q = isset($q) ? $q : '';
        $type = isset($type) && strtolower($type) == 'vendor' ? 'Supplier' : (isset($type) && strtolower($type) == 'supplier' ? 'Supplier' : 'Customer');
        
        $data = array();
        $qry = $this->conn->query("SELECT id, display_name as name, email, contact FROM `entity_list` WHERE entity_type = '{$type}' AND status = 1 AND (display_name LIKE '%{$q}%' OR email LIKE '%{$q}%' OR contact LIKE '%{$q}%') ORDER BY display_name ASC LIMIT 10");
        while($row = $qry->fetch_assoc()){
            $data[] = array(
                'id' => $row['id'],
                'text' => $row['name'],
                'subtext' => ($row['contact'] ? $row['contact'] : '') . ($row['email'] ? " | " . $row['email'] : '')
            );
        }
        return json_encode(array('results' => $data));
    }

    function manage_pos_change_transfer($date, $adjustments){
        if(!is_array($adjustments) || empty($adjustments)) return;

        $user_id = $this->settings->userdata('id') ?: 1;
        $remarks = "[POS] Daily Adjustments";
        
        // 1. Identify Cash Account dynamically
        $cash_id = 0;
        $cash_qry = $this->conn->query("SELECT id FROM account_list WHERE name = 'Cash' LIMIT 1");
        if($cash_qry->num_rows > 0) $cash_id = $cash_qry->fetch_assoc()['id'];
        if(!$cash_id) return; // Cannot proceed without Cash account

        $date = date('Y-m-d', strtotime($date));
        
        // 2. Search for existing adjustment transfer on this date
        $chk = $this->conn->query("SELECT id, reference_code, total_amount FROM `transactions` WHERE type='transfer' AND remarks = '{$remarks}' AND DATE(transaction_date) = '{$date}' LIMIT 1");
        
        if($chk->num_rows > 0){
            $row = $chk->fetch_assoc();
            $tid = $row['id'];
            $code = $row['reference_code'];
            $daily_total = floatval($row['total_amount']);
        } else {
            $code = $this->generate_reference_code('transfer', $date);
            if(!$code) $code = 'TR-' . date('YmdHis') . rand(100,999);
            
            $this->conn->query("INSERT INTO `transactions` (`reference_code`, `type`, `total_amount`, `remarks`, `created_by`, `transaction_date`) VALUES ('{$code}', 'transfer', 0, '{$remarks}', '{$user_id}', '{$date}')");
            $tid = $this->conn->insert_id;
            $daily_total = 0;
        }

        foreach($adjustments as $acc_id => $amt){
            $amt = floatval($amt);
            if($amt == 0) continue;
            
            $daily_total += $amt;
            
            // Update or Insert Target Account Entry (Type 1 - In)
            $chk_to = $this->conn->query("SELECT id, amount FROM transaction_list WHERE ref_table='transactions' AND ref_id='{$tid}' AND account_id='{$acc_id}' AND type=1");
            if($chk_to->num_rows > 0){
                $torow = $chk_to->fetch_assoc();
                $new_to_amt = floatval($torow['amount']) + $amt;
                $this->conn->query("UPDATE transaction_list SET amount = '{$new_to_amt}' WHERE id = '{$torow['id']}'");
            } else {
                $this->conn->query("INSERT INTO transaction_list (account_id, amount, type, trans_code, ref_table, ref_id, date_created) VALUES ('{$acc_id}', '{$amt}', 1, '{$code}', 'transactions', '{$tid}', '{$date} 23:59:59')");
            }
            $this->update_account_balance($acc_id);
        }

        // Update Master Transaction Total
        $this->conn->query("UPDATE `transactions` SET total_amount = '{$daily_total}' WHERE id = '{$tid}'");

        // Update or Insert Cash Account Entry (Type 2 - Out)
        $chk_from = $this->conn->query("SELECT id, amount FROM transaction_list WHERE ref_table='transactions' AND ref_id='{$tid}' AND account_id='{$cash_id}' AND type=2");
        if($chk_from->num_rows > 0){
            $frow = $chk_from->fetch_assoc();
            $this->conn->query("UPDATE transaction_list SET amount = '{$daily_total}' WHERE id = '{$frow['id']}'");
        } else {
            $this->conn->query("INSERT INTO transaction_list (account_id, amount, type, trans_code, ref_table, ref_id, date_created) VALUES ('{$cash_id}', '{$daily_total}', 2, '{$code}', 'transactions', '{$tid}', '{$date} 23:59:59')");
        }
        $this->update_account_balance($cash_id);
    }
}


// Instantiate helper but only run action handlers if this file is accessed directly
$Master = new Master();
$sysset = new SystemSettings();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    switch ($action) {
    case 'save_vendor': echo $Master->save_vendor(); break;

    case 'delete_vendor': echo $Master->delete_vendor(); break;
    case 'save_customer': echo $Master->save_customer(); break;
    case 'search_customers': echo $Master->search_parties(); break;
    case 'search_parties': echo $Master->search_parties(); break;

    case 'get_customer_details': echo $Master->get_customer_details(); break;
    case 'delete_customer': echo $Master->delete_customer(); break;
    case 'save_item': echo $Master->save_item(); break;
    case 'delete_item': echo $Master->delete_item(); break;
    case 'save_purchase': echo $Master->save_purchase(); break;
    case 'delete_po': echo $Master->delete_po(); break;
    case 'save_sale': echo $Master->save_sale(); break;
    case 'delete_sale': echo $Master->delete_sale(); break;
    case 'save_category': echo $Master->save_category(); break;
    case 'delete_category': echo $Master->delete_category(); break;
    case 'save_account': echo $Master->save_account(); break;
    case 'delete_account': echo $Master->delete_account(); break;
    case 'save_expense': echo $Master->save_expense(); break;
    case 'delete_expense': echo $Master->delete_expense(); break;
    case 'pay_expense': echo $Master->pay_expense(); break;
   
	case 'get_outstanding_bills': echo $Master->get_outstanding_bills(); break;
	case 'save_bulk_payment': echo $Master->save_bulk_payment(); break;
	// case 'save_payment': echo $Master->save_payment(); break; // Deprecated
	case 'delete_payment': echo $Master->delete_payment(); break;
    case 'save_adjustment': echo $Master->save_adjustment(); break;
    case 'delete_adjustment': echo $Master->delete_adjustment(); break;
    case 'save_return': echo $Master->save_return(); break;
    case 'delete_return': echo $Master->delete_return(); break;
    case 'save_transfer': echo $Master->save_transfer(); break;
    case 'delete_transfer': echo $Master->delete_transfer(); break;
    case 'save_opening_balance': echo $Master->save_opening_balance(); break;
    case 'update_opening_balance_entry': echo $Master->update_opening_balance_entry(); break;
    case 'delete_opening_balance_entry': echo $Master->delete_opening_balance_entry(); break;
    case 'save_ref_code_setting': echo $Master->save_ref_code_setting(); break;
    case 'save_bulk_ref_codes': echo $Master->save_bulk_ref_codes(); break;
    case 'get_next_ref_code': echo $Master->get_next_ref_code(); break;
    case 'save_denomination': echo $Master->save_denomination(); break;
    case 'delete_denomination': echo $Master->delete_denomination(); break;
    case 'get_daily_balances': $Master->get_daily_balances(); break;
    case 'get_balances_by_date': echo $Master->get_balances_by_date(); break;
    case 'check_reconciliation_status': $Master->check_reconciliation_status(); break;
    case 'finalize_reconciliation': echo $Master->finalize_reconciliation(); break;
    case 'save_pos': echo $Master->save_pos(); break;
    case 'search_pos_items': echo $Master->search_pos_items(); break;
        default: break;
    }
}


