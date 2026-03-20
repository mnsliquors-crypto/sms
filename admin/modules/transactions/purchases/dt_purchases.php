<?php
require_once('../../../../config.php');

// DataTables server-side processing for Purchases
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length'];
$columnIndex = $_POST['order'][0]['column'];
$columnName = $_POST['columns'][$columnIndex]['data'];
$columnSortOrder = $_POST['order'][0]['dir'];
$searchValue = $_POST['search']['value'];

// Column mapping for sorting
$columns = array(
    0 => 'p.id',
    1 => 'p.date_created',
    2 => 'p.reference_code',
    3 => 's.display_name',
    4 => 'p.total_amount',
    5 => 'p.tax',
    6 => 'p.total_amount', // grand total logic handled in sql
    7 => 'total_paid',
    8 => 'outstanding',
    9 => 'status'
);

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " AND (p.reference_code LIKE ? OR s.display_name LIKE ?) ";
   $searchParams = ["%$searchValue%", "%$searchValue%"];
} else {
    $searchParams = [];
}

// Total records
$totalRecords = $conn->query("SELECT COUNT(*) FROM transactions WHERE type='purchase'")->fetch_array()[0];

// Total records with filter
$stmt = $conn->prepare("SELECT COUNT(*) FROM transactions p INNER JOIN entity_list s ON p.entity_id = s.id AND s.entity_type = 'Supplier' WHERE p.type='purchase' $searchQuery");
if($searchValue != ''){
    $stmt->bind_param("ss", ...$searchParams);
}
$stmt->execute();
$totalRecordwithFilter = $stmt->get_result()->fetch_array()[0];

// Order by
$orderBy = "p.date_created $columnSortOrder, p.id $columnSortOrder";
if(isset($columns[$columnIndex])) {
    $col = $columns[$columnIndex];
    if($col == 'total_paid') $orderBy = "COALESCE(pay.total_paid, 0) + COALESCE(ret.total_returned, 0) $columnSortOrder";
    else if($col == 'outstanding') $orderBy = "(p.total_amount + COALESCE(p.tax,0)) - (COALESCE(pay.total_paid, 0) + COALESCE(ret.total_returned, 0)) $columnSortOrder";
    else $orderBy = "$col $columnSortOrder";
}

// Fetch records
$sql = "SELECT p.*, s.display_name as vendor, COALESCE(pay.total_paid, 0) as total_paid, COALESCE(ret.total_returned, 0) as total_returned
        FROM `transactions` p 
        INNER JOIN entity_list s ON p.entity_id = s.id AND s.entity_type = 'Supplier'
        LEFT JOIN (SELECT parent_id, SUM(total_amount) as total_paid FROM transactions WHERE type = 'payment' GROUP BY parent_id) pay ON p.id = pay.parent_id 
        LEFT JOIN (SELECT parent_id, SUM(total_amount) as total_returned FROM transactions WHERE type = 'return' GROUP BY parent_id) ret ON p.id = ret.parent_id 
        WHERE p.type = 'purchase' $searchQuery 
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
    $total_amount = floatval($row_data['total_amount']);
    $vat_amount = floatval($row_data['tax'] ?? 0);
    $total_with_vat = $total_amount + $vat_amount;
    $outstanding = $total_with_vat - $total_paid - $total_returned;
    
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
                   <a class="dropdown-item view_data" href="javascript:void(0)" data-id="'.$row_data['id'].'"><span class="fa fa-eye text-dark"></span> View</a>
                   <div class="dropdown-divider"></div>
                   <a class="dropdown-item" href="'.base_url.'admin?page=transactions/purchases/manage_purchase&id='.$row_data['id'].'"><span class="fa fa-edit text-primary"></span> Edit</a>
                   <div class="dropdown-divider"></div>';
    
    if(!$is_fully_paid) {
        $action .= '<a class="dropdown-item record_payment" href="javascript:void(0)" data-id="'.$row_data['id'].'" data-party-id="'.$row_data['entity_id'].'" data-type="2"><span class="fa fa-money-bill text-success"></span> Record Payment</a>
                    <div class="dropdown-divider"></div>';
    }

    $action .= '   <a class="dropdown-item" href="'.base_url.'admin?page=transactions/returns/manage_return&from_purchase_id='.$row_data['id'].'"><span class="fa fa-undo text-secondary"></span> Return Items</a>
                   <div class="dropdown-divider"></div>
                   <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="'.$row_data['id'].'"><span class="fa fa-trash text-danger"></span> Delete</a>
               </div>';

    $data[] = array(
        "index" => $i++,
        "date" => date("d-m-Y", strtotime($row_data['transaction_date'])),
        "code" => $row_data['reference_code'],
        "vendor" => $row_data['vendor'],
        "sub_total" => '<span class="badge badge-light border">'.number_format($total_amount, 2).'</span>',
        "vat" => '<span class="badge badge-info">'.number_format($vat_amount, 2).'</span>',
        "total" => '<span class="badge badge-primary shadow-sm">'.number_format($total_with_vat, 2).'</span>',
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
