<?php
require_once '../config/database.php';
require_once '../config/functions.php';


// Checking if user is logged in and an admin, else redirect to login page with error message
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error_message'] = "You must be logged in as an administrator to access this page.";
    header('Location: ../login.php');
    exit;
}


// Fetching the export format from URL parameters, default is 'csv'
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';


// Query to fetch all users data sorted by join date in descending order
$query = "SELECT id, username, email, role, status, joined_date, last_login FROM users ORDER BY joined_date DESC";
$stmt = $pdo->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Check if any user data found
if (empty($users)) {
    $_SESSION['error_message'] = "No users found to export.";
    header('Location: users.php');
    exit;
}


// Set filename for download
$timestamp = date('Y-m-d_H-i-s');
$filename = "user_export_{$timestamp}";


// Handling different export formats like 'csv' or 'json'
if ($format === 'csv') {

    // Setting headers for CSV file download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    

    $output = fopen('php://output', 'w');
    

    // Writing the CSV headers to output stream
    fputcsv($output, array_keys($users[0]));
    

    // Writing user data rows to the CSV file
    foreach ($users as $user) {

        if (!empty($user['joined_date'])) {
            $user['joined_date'] = date('Y-m-d H:i:s', strtotime($user['joined_date']));
        }
        if (!empty($user['last_login'])) {
            $user['last_login'] = date('Y-m-d H:i:s', strtotime($user['last_login']));
        }
        
        fputcsv($output, $user);
    }
    

    // Closing the file pointer
    fclose($output);

} elseif ($format === 'json') {

    // Setting headers for JSON file download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    

    foreach ($users as &$user) {
        if (!empty($user['joined_date'])) {
            $user['joined_date'] = date('Y-m-d H:i:s', strtotime($user['joined_date']));
        }
        if (!empty($user['last_login'])) {
            $user['last_login'] = date('Y-m-d H:i:s', strtotime($user['last_login']));
        }
    }
    

    // Outputting the JSON encoded user data
    echo json_encode(['users' => $users], JSON_PRETTY_PRINT);

} else {
    // If invalid export format, return 400 Bad Request status and error message
    // Invalid format
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid export format. Use "csv" or "json".';
}

exit;
?> 