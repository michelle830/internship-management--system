<?php
/*
FETCH_NOTIFICATIONS.php
Purpose: Fetch unread notifications for the logged-in assessor
Features:
- Session validation
- Retrieve unread notifications
- Mark notifications as read after fetching
- Return JSON response
*/

session_start();
include("includes/config.php");

// Ensure assessor is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'assessor') {
    	http_response_code(403);
    	echo json_encode(["error" => "Unauthorized"]);
    	exit();
}

$assessor_id = $_SESSION['user_id'];

// Fetch unread notifications
$stmt = $conn->prepare("SELECT id, message, is_read, created_at FROM notifications WHERE assessor_id = ? ORDER BY is_read ASC, created_at DESC");
$stmt->bind_param("i", $assessor_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
		$row['is_read'] = (int)$row['is_read'];
    	$notifications[] = $row;
}

echo json_encode($notifications);
?>
