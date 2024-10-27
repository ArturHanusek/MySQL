<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allow CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

header('Content-Type: application/json');

// OpenAPI schema
$openapi_schema = [
    'openapi' => '3.0.0',
    'info' => [
        'title' => 'Dynamic SQL Query API',
        'description' => 'API for executing SQL queries with dynamic database connections',
        'version' => '1.0.0'
    ],
    'paths' => [
        '/' => [
            'get' => [
                'summary' => 'Get API Documentation',
                'responses' => [
                    '200' => [
                        'description' => 'OpenAPI schema'
                    ]
                ]
            ],
            'post' => [
                'summary' => 'Execute SQL Query',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['host', 'dbname', 'username', 'password', 'query'],
                                'properties' => [
                                    'host' => [
                                        'type' => 'string'
                                    ],
                                    'dbname' => [
                                        'type' => 'string'
                                    ],
                                    'username' => [
                                        'type' => 'string'
                                    ],
                                    'password' => [
                                        'type' => 'string'
                                    ],
                                    'query' => [
                                        'type' => 'string'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Query results'
                    ]
                ]
            ]
        ]
    ]
];

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode($openapi_schema);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON input
        $jsonInput = file_get_contents('php://input');
        if (!$jsonInput) {
            throw new Exception('No input provided');
        }

        // Decode JSON
        $input = json_decode($jsonInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }

        // Validate required fields
        $required = ['host', 'dbname', 'username', 'password', 'query'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Create PDO connection
        $dsn = "mysql:host={$input['host']};dbname={$input['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $input['username'], $input['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Execute query
        $stmt = $pdo->prepare($input['query']);
        $stmt->execute();

        // Get results
        if (stripos($input['query'], 'SELECT') === 0) {
            $result = $stmt->fetchAll();
        } else {
            $result = [
                'affected_rows' => $stmt->rowCount()
            ];
        }

        // Return success response
        echo json_encode([
            'status' => 'success',
            'data' => $result
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle invalid methods
http_response_code(405);
echo json_encode([
    'status' => 'error',
    'message' => 'Method not allowed'
]);
?>