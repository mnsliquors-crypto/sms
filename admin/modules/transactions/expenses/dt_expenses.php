<?php
require_once('../../../../config.php');

// DataTables server-side processing for Expenses
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length'];
$columnIndex = $_POST['order'][0]['column'];
$columnName = $_POST['columns'][$columnIndex]['data'];
$columnSortOrder = $_POST['order'][0]['dir'];
$searchValue = $_POST['search']['value'];

// Column mapping for sorting
$columns = array(
    0 => 'e.id',
    1 => 'e.transaction_date',
    2 => 'e.id', // formatted code
    3 => 'user_name',
    4 => 'account',
    5 => 'e.total_amount',
    6 => 'e.remarks',
    7 => 'e.status'
);

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " AND (e.id LIKE ? OR e.remarks LIKE ? OR a.name LIKE ? OR u.firstname LIKE ? OR u.lastname LIKE ?) ";
   $searchParams = ["%$searchValue%", "%$searchValue%", "%$searchValue%", "%$searchValue%", "%$searchValue%"];
} else {
    $searchParams = [];
}

// Total records
$totalRecords = $conn->query("SELECT COUNT(*) FROM transactions WHERE type='expense'")->fetch_array()[0];

// Total records with filter
$stmt = $conn->prepare("SELECT COUNT(*) FROM transactions e LEFT JOIN account_list a ON e.account_id = a.id LEFT JOIN users u ON e.created_by = u.id WHERE e.type='expense' $searchQuery");
if($searchValue != ''){
    $stmt->bind_param("sssss", ...$searchParams);
}
$stmt->execute();
$totalRecordwithFilter = $stmt->get_result()->fetch_array()[0];

// Order by
$orderBy = "e.date_created $columnSortOrder, e.id $columnSortOrder";
if(isset($columns[$columnIndex])) {
    $col = $columns[$columnIndex];
    if($col == 'user_name') $orderBy = "u.firstname $columnSortOrder, u.lastname $columnSortOrder";
    else if($col == 'account') $orderBy = "a.name $columnSortOrder";
    else $orderBy = "$col $columnSortOrder";
}

// Fetch records
$sql = "SELECT e.id, e.transaction_date, e.total_amount, e.remarks, e.status, a.name as account, concat(u.firstname,' ',u.lastname) as user_name 
        FROM `transactions` e 
        LEFT JOIN account_list a ON e.account_id = a.id 
        LEFT JOIN users u ON e.created_by = u.id 
        WHERE e.type = 'expense' $searchQuery 
        ORDER BY $orderBy 
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
if($searchValue != ''){
    $params = array_merge($searchParams, [$row, $rowperpage]);
    $stmt->bind_param("sssssii", ...$params);
} else {
    $stmt->bind_param("ii", $row, $rowperpage);
}
$stmt->execute();
$records = $stmt->get_result();

$data = array();
$i = $row + 1;
while ($row_data = $records->fetch_assoc()) {
    $status = $row_data['status'] == 1 ? '<span class="badge badge-success">Paid</span>' : '<span class="badge badge-warning">Pending</span>';
    
    $action = '<button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">Action <span class="sr-only">Toggle Dropdown</span></button>
               <div class="dropdown-menu" role="menu">';
    
    if($row_data['status'] == 0) {
        $action .= '<a class="dropdown-item pay_data" href="javascript:void(0)" data-id="'.$row_data['id'].'"><span class="fa fa-check text-success"></span> Accept Payment</a>
                    <div class="dropdown-divider"></div>';
    }

    $action .= '   <a class="dropdown-item" href="'.base_url.'admin?page=transactions/expenses/view_expense&id='.$row_data['id'].'"><span class="fa fa-eye text-dark"></span> View</a>
                   <div class="dropdown-divider"></div>
                   <a class="dropdown-item" href="'.base_url.'admin?page=transactions/expenses/manage_expense&id='.$row_data['id'].'"><span class="fa fa-edit text-primary"></span> Edit</a>
                   <div class="dropdown-divider"></div>
                   <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="'.$row_data['id'].'"><span class="fa fa-trash text-danger"></span> Delete</a>
               </div>';

    $data[] = array(
        "index" => $i++,
        "date" => date("d-m-Y", strtotime($row_data['transaction_date'])),
        "code" => "EXP-".sprintf("%'.04d", $row_data['id']),
        "user" => htmlspecialchars($row_data['user_name']),
        "account" => htmlspecialchars($row_data['account'] ?? 'N/A'),
        "amount" => number_format($row_data['total_amount'], 2),
        "remarks" => '<p class="truncate-1 m-0">'.htmlspecialchars($row_data['remarks']).'</p>',
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
