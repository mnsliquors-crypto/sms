<?php
require_once 'config.php';
$id = 10;
$qry = $conn->query("SELECT * FROM `transactions` WHERE id = '{$id}'");
if($qry->num_rows > 0){
    echo json_encode($qry->fetch_assoc(), JSON_PRETTY_PRINT);
} else {
    echo "Record not found";
}
?>
