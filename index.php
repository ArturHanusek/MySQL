<?php
header('Content-Type: application/json');

// API response structure
function sendResponse($status, $data = null, $message = '') {
    echo json_encode([
        'status' => $status,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', null, 'Only POST method is allowed');
}

// Get and validate JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['host', 'dbname', 'username', 'password', 'query'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        sendResponse('error', null, "Field '$field' is required");
    }
}

// Validate API key (you should implement proper authentication)
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($api_key !== 'your_secure_api_key') {
    sendResponse('error', null, 'Invalid API key');
}

try {
    // Create PDO connection using provided credentials
    $dsn = "mysql:host={$input['host']};dbname={$input['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $input['username'], $input['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Prepare and execute the query
    $stmt = $pdo->prepare($input['query']);
    $stmt->execute();

    // Handle different query types
    if (stripos($input['query'], 'SELECT') === 0) {
        // For SELECT queries, fetch all results
        $result = $stmt->fetchAll();
        sendResponse('success', $result);
    } else {
        // For INSERT, UPDATE, DELETE queries, return affected rows
        $affectedRows = $stmt->rowCount();
        sendResponse('success', ['affected_rows' => $affectedRows]);
    }

} catch (PDOException $e) {
    sendResponse('error', null, 'Database error: ' . $e->getMessage());
} catch (Exception $e) {
    sendResponse('error', null, 'Server error: ' . $e->getMessage());
}
?>
