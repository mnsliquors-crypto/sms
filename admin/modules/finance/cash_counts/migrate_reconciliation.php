<?php
require_once('../../config.php');

$migrations = [
    "ALTER TABLE `cash_denominations` ADD COLUMN IF NOT EXISTS `reconciliation_status` TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE `cash_denominations` ADD COLUMN IF NOT EXISTS `finalized_by` INT NULL DEFAULT NULL",
    "ALTER TABLE `cash_denominations` ADD COLUMN IF NOT EXISTS `finalized_at` DATETIME NULL DEFAULT NULL",
];

$results = [];
foreach($migrations as $sql){
    if($conn->query($sql)){
        $results[] = ['ok', $sql];
    } else {
        $results[] = ['err', $sql . ' | ' . $conn->error];
    }
}

header('Content-Type: application/json');
echo json_encode($results);
?>
