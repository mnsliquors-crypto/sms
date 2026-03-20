<?php
require_once('../../../../config.php');

// DataTables server-side processing for Sales
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length'];
$columnIndex = $_POST['order'][0]['column'];
$columnName = $_POST['columns'][$columnIndex]['data'];
$columnSortOrder = $_POST['order'][0]['dir'];
$searchValue = $_POST['search']['value'];

// Column mapping for sorting
$columns = array(
    0 => 't.id',
    1 => 't.transaction_date',
    2 => 't.reference_code',
    3 => 'c.display_name',
    4 => 't.total_amount',
    5 => 't.discount',
    6 => 't.total_amount', // grand total
    7 => 'total_paid',
    8 => 'outstanding',
    9 => 'status'
);

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " AND (t.reference_code LIKE ? OR c.display_name LIKE ?) ";
   $searchParams = ["%$searchValue%", "%$searchValue%"];
} else {
    $searchParams = [];
}

// Total number of records without filtering
$totalRecords = $conn->query("SELECT COUNT(*) FROM transactions WHERE type='sale'")->fetch_array()[0];

// Total number of records with filtering
$stmt = $conn->prepare("SELECT COUNT(*) FROM transactions t LEFT JOIN entity_list c ON t.entity_id = c.id AND c.entity_type = 'Customer' WHERE t.type='sale' $searchQuery");
if($searchValue != ''){
    $stmt->bind_param("ss", ...$searchParams);
}
$stmt->execute();
$totalRecordwithFilter = $stmt->get_result()->fetch_array()[0];

// Order by
$orderBy = "t.transaction_date $columnSortOrder, t.id $columnSortOrder";
if(isset($columns[$columnIndex])) {
    $col = $columns[$columnIndex];
    if($col == 'total_paid') $orderBy = "COALESCE(pay.total_paid, 0) + COALESCE(ret.total_returned, 0) $columnSortOrder";
    else if($col == 'outstanding') $orderBy = "t.total_amount - (COALESCE(pay.total_paid, 0) + COALESCE(ret.total_returned, 0)) $columnSortOrder";
    else $orderBy = "$col $columnSortOrder";
}

// Fetch records
$sql = "SELECT t.id, t.transaction_date, t.reference_code, t.total_amount, t.discount, t.entity_id, t.remarks, c.display_name as customer_name, COALESCE(pay.total_paid, 0) as total_paid, COALESCE(ret.total_returned, 0) as total_returned
        FROM transactions t 
        LEFT JOIN entity_list c ON t.entity_id = c.id AND c.entity_type = 'Customer'
        LEFT JOIN (SELECT parent_id, SUM(total_amount) as total_paid FROM transactions WHERE type = 'payment' GROUP BY parent_id) pay ON t.id = pay.parent_id 
        LEFT JOIN (SELECT parent_id, SUM(total_amount) as total_returned FROM transactions WHERE type = 'return' GROUP BY parent_id) ret ON t.id = ret.parent_id 
        WHERE t.type = 'sale' $searchQuery 
        ORDER BY $orderBy 
        LIMIT ?, ?";


$stmt = $conn->prepare($sql);
if($searchValue != ''){
    $params = array_merge($searchParams, [$row, $rowperpage]);
    $stmt->bind_param("ssii", ...$params);
} else {
    $stmt->bind_param("ii", $row, $rowperpage);
}
$stmt->execute();
$records = $stmt->get_result();

$data = array();
$i = $row + 1;
while ($row_data = $records->fetch_assoc()) {
    $total_paid = floatval($row_data['total_paid']);
    $total_returned = floatval($row_data['total_returned']);
    $grand_total = floatval($row_data['total_amount']);
    $discount = floatval($row_data['discount']);
    $sub_total = $grand_total + $discount;
    $outstanding = $grand_total - $total_paid - $total_returned;
    
    if($outstanding <= 0.01):
        $status = "Paid in Full";
        $badge_class = "badge-success";
        $is_fully_paid = true;
    elseif(($total_paid + $total_returned) > 0):
        $status = "Partially Paid";
        $badge_class = "badge-warning";
        $is_fully_paid = false;
    else:
        $status = "Outstanding";
        $badge_class = "badge-danger";
        $is_fully_paid = false;
    endif;

    $action = '<button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">Action <span class="sr-only">Toggle Dropdown</span></button>
               <div class="dropdown-menu" role="menu">
                   <a class="dropdown-item" href="'.base_url.'admin?page=transactions/sales/view_sale&id='.$row_data['id'].'"><span class="fa fa-eye text-dark"></span> View</a>
                   <div class="dropdown-divider"></div>
                   <a class="dropdown-item" href="'.base_url.'admin?page=transactions/sales/manage_sale&id='.$row_data['id'].'"><span class="fa fa-edit text-primary"></span> Edit</a>
                   <div class="dropdown-divider"></div>';
    
    if(!$is_fully_paid) {
        $action .= '<a class="dropdown-item record_payment" href="javascript:void(0)" data-id="'.$row_data['id'].'" data-party-id="'.$row_data['entity_id'].'" data-type="1"><span class="fa fa-money-bill text-success"></span> Record Payment</a>
                    <div class="dropdown-divider"></div>';
    }

    $action .= '   <a class="dropdown-item" href="'.base_url.'admin?page=transactions/returns/manage_return&from_sale_id='.$row_data['id'].'"><span class="fa fa-undo text-secondary"></span> Return Items</a>
                   <div class="dropdown-divider"></div>
                   <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="'.$row_data['id'].'"><span class="fa fa-trash text-danger"></span> Delete</a>
               </div>';

    $data[] = array(
        "index" => $i++,
        "date" => date("d-m-Y", strtotime($row_data['transaction_date'])),
        "code" => $row_data['reference_code'] . (strpos($row_data['remarks'] ?? '', '[POS]') !== false ? ' <span class="badge badge-info shadow-sm" title="POS Entry">POS</span>' : ''),
        "customer" => $row_data['customer_name'] ?? 'Walk-in',

        "sub_total" => '<span class="badge badge-light border">'.number_format($sub_total, 2).'</span>',
        "discount" => '<span class="badge badge-warning">'.number_format($discount, 2).'</span>',
        "total" => '<span class="badge badge-primary shadow-sm">'.number_format($grand_total, 2).'</span>',
        "paid" => '<span class="badge badge-success">'.number_format($total_paid + $total_returned, 2).'</span>',
        "outstanding" => '<span class="badge badge-danger">'.number_format(max(0, $outstanding), 2).'</span>',
        "status" => '<span class="badge '.$badge_class.'">'.$status.'</span>',
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
