<?php
require_once('../../config.php');
require_once('../../classes/SearchService.php');

// Must be logged in
if (!isset($_SESSION['userdata']['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchService = new SearchService($conn);

$results = $searchService->search($q);

header('Content-Type: application/json');
echo json_encode($results);
exit;
