<?php
/*
MARK_NOTIFICATION_READ.php
Purpose:
- Handles requests from the assessor dashboard to mark a single notification as read
- Validates that the current session belongs to a logged-in assessor
- Accepts a notification ID via POST and updates the 'is_read' flag in the database
- Returns a JSON response indicating success or failure
- Logs the attempted notification ID to the PHP error log for debugging

Workflow:
1. Start session and include database configuration
2. Verify user is logged in and has the 'assessor' role
3. Retrieve the notification ID from POST data and sanitize it
4. Execute an UPDATE query to set 'is_read = 1' for the given notification
5. Return a JSON response with success status or error details
6. Write a notification ID to the error log for traceability
*/
session_start();
include("includes/config.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'assessor') {
    http_response_code(403);
    exit();
}

$id = intval($_POST['id']);
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->error ?: "No rows updated"]);
}
error_log("Marking notification as read: " . $id);
?>
