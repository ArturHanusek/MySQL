<?php
header('Content-Type: application/json');

// OpenAPI schema
$openapi_schema = [
    'openapi' => '3.0.0',
    'info' => [
        'title' => 'Dynamic SQL Query API',
        'description' => 'API for executing SQL queries with dynamic database connections',
        'version' => '1.0.0'
    ],
    'servers' => [
        [
            'url' => 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']),
            'description' => 'API Server'
        ]
    ],
    'paths' => [
        '/' => [
            'get' => [
                'summary' => 'Get API Documentation',
                'description' => 'Returns OpenAPI schema',
                'responses' => [
                    '200' => [
                        'description' => 'Successful response with OpenAPI schema',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'post' => [
                'summary' => 'Execute SQL Query',
                'description' => 'Execute SQL query with provided database connection details',
                'security' => [
                    ['ApiKeyAuth' => []]
                ],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['host', 'dbname', 'username', 'password', 'query'],
                                'properties' => [
                                    'host' => [
                                        'type' => 'string',
                                        'description' => 'Database host'
                                    ],
                                    'dbname' => [
                                        'type' => 'string',
                                        'description' => 'Database name'
                                    ],
                                    'username' => [
                                        'type' => 'string',
                                        'description' => 'Database username'
                                    ],
                                    'password' => [
                                        'type' => 'string',
                                        'description' => 'Database password',
                                        'format' => 'password'
                                    ],
                                    'query' => [
                                        'type' => 'string',
                                        'description' => 'SQL query to execute'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'status' => [
                                            'type' => 'string',
                                            'enum' => ['success', 'error']
                                        ],
                                        'data' => [
                                            'type' => ['object', 'array', 'null']
                                        ],
                                        'message' => [
                                            'type' => 'string'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '400' => [
                        'description' => 'Bad Request',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'status' => [
                                            'type' => 'string',
                                            'enum' => ['error']
                                        ],
                                        'message' => [
                                            'type' => 'string'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    'components' => [
        'securitySchemes' => [
            'ApiKeyAuth' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-API-Key'
            ]
        ]
    ]
];

// API response structure
function sendResponse($status, $data = null, $message = '') {
    echo json_encode([
        'status' => $status,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

// Handle GET request - return OpenAPI schema
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode($openapi_schema, JSON_PRETTY_PRINT);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required_fields = ['host', 'dbname', 'username', 'password', 'query'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            sendResponse('error', null, "Field '$field' is required");
        }
    }

    // Validate API key
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
} else {
    sendResponse('error', null, 'Method not allowed');
}
?>