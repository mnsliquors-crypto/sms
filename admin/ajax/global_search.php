<?php
require_once(__DIR__ . '/../../config.php');

// Must be logged in
if(!isset($_SESSION['userdata']['id'])){
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 2) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$results = [];

/**
 * Optimized Search Logic:
 * 1. Exact title match (Score 1000)
 * 2. Title starts with (Score 500)
 * 3. FULLTEXT match (Score varies)
 */
$sql = "SELECT source_id, title, transaction_date, amount, type, subtitle, view_url, edit_url, 
        (CASE 
            WHEN title = ? THEN 1000 
            WHEN title LIKE ? THEN 500
            ELSE 0 
         END) + (MATCH(title, subtitle, search_data) AGAINST(? IN BOOLEAN MODE) * 10) as score 
        FROM `search_index` 
        WHERE title = ? 
        OR title LIKE ? 
        OR MATCH(title, subtitle, search_data) AGAINST(? IN BOOLEAN MODE)
        OR subtitle LIKE ?
        ORDER BY score DESC, transaction_date DESC 
        LIMIT 20";

$stmt = $conn->prepare($sql);

$like_start = "$q%";
$like_any = "%$q%";

// If query has hyphens or spaces, use phrase search for FT to avoid broad tokenization
$boolean_q = (strpos($q, '-') !== false || strpos($q, ' ') !== false) ? '"' . $q . '"' : "$q*";

$stmt->bind_param("sssssss", $q, $like_start, $boolean_q, $q, $like_start, $boolean_q, $like_any);
$stmt->execute();
$res = $stmt->get_result();

$raw_results = [];
$master_exact_match = false;
$master_types = ['customer', 'vendor', 'item', 'user'];

while($row = $res->fetch_assoc()){
    $raw_results[] = $row;
    // Check if we have an exact title match for a master type
    if(strtolower($row['title']) == strtolower($q) && in_array($row['type'], $master_types)){
        $master_exact_match = true;
    }
}

foreach($raw_results as $row){
    // If we have an exact master match, exclude related transactions that only matched via entity name
    if($master_exact_match){
        // Exclude if it's a transaction AND its title is NOT an exact match for the query
        if(!in_array($row['type'], $master_types) && strtolower($row['title']) != strtolower($q)){
            // This is a "related" record, exclude it as per user request
            continue;
        }
    }
    
    $results[] = [
        'id' => $row['source_id'],
        'reference_code' => $row['title'],
        'transaction_date' => $row['transaction_date'],
        'total_amount' => $row['amount'],
        'type' => $row['type'],
        'party' => $row['subtitle'],
        'view_url' => $row['view_url'],
        'edit_url' => $row['edit_url'],
        'score' => $row['score']
    ];
}

// Final safety limit
$results = array_slice($results, 0, 10);

header('Content-Type: application/json');
echo json_encode($results);
exit;
