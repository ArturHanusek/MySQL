<?php
session_start();

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Initialize variables
$host = $dbname = $username = $password = "";
$encrypted_values = [];
$errors = [];
$success = false;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $fields = ['host', 'dbname', 'username', 'password'];
    
    foreach ($fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst($field) . " is required";
        } else {
            ${$field} = sanitize_input($_POST[$field]);
            // Encrypt the value using bcrypt
            $encrypted_values[$field] = password_hash(${$field}, PASSWORD_BCRYPT);
        }
    }
    
    if (empty($errors)) {
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Configuration Encryptor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .encrypted-value {
            word-break: break-all;
            background-color: #f8f9fa;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Database Configuration Encryptor</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success">
            <h2>Encrypted Values:</h2>
            <?php foreach($encrypted_values as $field => $value): ?>
                <div class="form-group">
                    <strong><?php echo ucfirst($field); ?>:</strong>
                    <div class="encrypted-value"><?php echo $value; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
            <label for="host">Host:</label>
            <input type="text" id="host" name="host" value="<?php echo $host; ?>">
        </div>

        <div class="form-group">
            <label for="dbname">Database Name:</label>
            <input type="text" id="dbname" name="dbname" value="<?php echo $dbname; ?>">
        </div>

        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo $username; ?>">
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password">
        </div>

        <input type="submit" value="Encrypt Values">
    </form>
</body>
</html>
