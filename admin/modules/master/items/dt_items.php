<?php
require_once('../../../../config.php');

// DataTables server-side processing
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length'];
$columnIndex = $_POST['order'][0]['column'];
$columnName = $_POST['columns'][$columnIndex]['data'];
$columnSortOrder = $_POST['order'][0]['dir'];
$searchValue = $_POST['search']['value'];

$response = array();

// Search 
$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " AND (i.name LIKE ? OR i.unit LIKE ? OR c.name LIKE ?) ";
   $searchParams = ["%$searchValue%", "%$searchValue%", "%$searchValue%"];
} else {
    $searchParams = [];
}

// Total number of records without filtering
$totalRecords = $conn->query("SELECT COUNT(*) FROM item_list")->fetch_array()[0];

// Total number of records with filtering
$stmt = $conn->prepare("SELECT COUNT(*) FROM item_list i LEFT JOIN category_list c ON i.category_id = c.id WHERE 1 $searchQuery");
if($searchValue != ''){
    $stmt->bind_param("sss", ...$searchParams);
}
$stmt->execute();
$totalRecordwithFilter = $stmt->get_result()->fetch_array()[0];

// Fetch records
$orderBy = "i.name"; // Default
if($columnName == 'name') $orderBy = "i.name";
else if($columnName == 'category') $orderBy = "c.name";
else if($columnName == 'cost') $orderBy = "i.cost";
else if($columnName == 'quantity') $orderBy = "i.quantity";

$sql = "SELECT i.id, i.name, i.unit, i.cost, i.reorder_level, i.quantity, i.status, c.name as category,
        (SELECT ti.unit_price FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE ti.item_id = i.id AND t.type = 'purchase' ORDER BY t.transaction_date DESC, t.id DESC LIMIT 1) as last_p_price,
        (SELECT ti.unit_price FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE ti.item_id = i.id AND t.type = 'sale' ORDER BY t.transaction_date DESC, t.id DESC LIMIT 1) as last_s_price
        FROM item_list i 
        LEFT JOIN category_list c ON i.category_id = c.id 
        WHERE 1 $searchQuery 
        ORDER BY $orderBy $columnSortOrder 
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
if($searchValue != ''){
    $params = array_merge($searchParams, [$row, $rowperpage]);
    $stmt->bind_param("sssii", ...$params);
} else {
    $stmt->bind_param("ii", $row, $rowperpage);
}
$stmt->execute();
$records = $stmt->get_result();

$data = array();
$i = $row + 1;
while ($row_data = $records->fetch_assoc()) {
    $available = floatval($row_data['quantity']);
    $restock = ($available <= $row_data['reorder_level']);
    
    $status = $row_data['status'] == 1 ? '<span class="badge badge-success rounded-pill">Active</span>' : '<span class="badge badge-danger rounded-pill">Inactive</span>';
    if($restock) $status .= '<br><span class="badge badge-warning rounded-pill">Restock Needed</span>';

    $action = '<button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">Action <span class="sr-only">Toggle Dropdown</span></button>
               <div class="dropdown-menu" role="menu">
                   <a class="dropdown-item" href="'.base_url.'admin/?page=master/items/view&id='.$row_data['id'].'"><span class="fa fa-eye text-dark"></span> View</a>
                   <div class="dropdown-divider"></div>
                   <a class="dropdown-item" href="'.base_url.'admin/?page=master/items/manage&id='.$row_data['id'].'"><span class="fa fa-edit text-primary"></span> Edit</a>
                   <div class="dropdown-divider"></div>
                   <a class="dropdown-item" href="'.base_url.'admin?page=transactions/sales/manage_sale&item_id='.$row_data['id'].'"><span class="fa fa-shopping-cart text-success"></span> Quick Sale</a>
                   <div class="dropdown-divider"></div>
                   <a class="dropdown-item" href="'.base_url.'admin?page=purchases/manage_purchase&item_id='.$row_data['id'].'"><span class="fa fa-truck text-info"></span> Quick Purchase</a>
                   <div class="dropdown-divider"></div>
                   <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="'.$row_data['id'].'"><span class="fa fa-trash text-danger"></span> Delete</a>
               </div>';

    $data[] = array(
        "index" => $i++,
        "name" => '<a href="'.base_url.'admin/?page=master/items/view&id='.$row_data['id'].'" class="font-weight-bold text-navy">'.$row_data['name'].'</a>',
        "unit" => $row_data['unit'],
        "category" => $row_data['category'],
        "cost" => number_format($row_data['cost'], 2),
        "last_p" => number_format($row_data['last_p_price'] ?? 0, 2),
        "quantity" => number_format($available, 2),
        "last_s" => number_format($row_data['last_s_price'] ?? 0, 2),
        "reorder" => number_format($row_data['reorder_level'], 2),
        "status" => $status,
        "action" => $action
    );
}

$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data
);

echo json_encode($response);
