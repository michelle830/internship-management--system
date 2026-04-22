<?php
/*
DELETE_NOTIFICATION.php
Purpose:
- Handles requests from the assessor dashboard to permanently delete a notification
- Validates that the current session belongs to a logged in assessor
- Accepts a notification ID via POST and removes the corresponding row from the 'notifications' table
- Returns a JSON response indicating success (or failure if extended error handling is added)

Workflow:
1. Start session and include database configuration.
2. Verify user is logged in and has the 'assessor' role
3. Retrieve the notification ID from POST data and sanitize it
4. Execute a DELETE query to remove the notification with the given ID
5. Return a JSON response confirming the deletion
*/
session_start();
include("includes/config.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'assessor') {
    http_response_code(403);
    exit();
}

$id = intval($_POST['id']);
$stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
echo json_encode(["success" => true]);
?>