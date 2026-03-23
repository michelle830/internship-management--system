<?php
/*
CHANGE_PASSWORD.php
Purpose: Force users with default password to set a new one
Features:
- Session validation
- New password confirmation
- Secure password hashing
- Reset is_default_password flag
*/

session_start();
include("includes/config.php");

if (!isset($_SESSION['user_id'])) {
    	header("Location: login.php");
    	exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    	$new = $_POST['new_password'];
    	$confirm = $_POST['confirm_password'];

    	if ($new === $confirm) {
        	if (strlen($new) < 8 || !preg_match('/[^a-zA-Z0-9]/', $new)) {
            		$error = "Password must be at least 8 characters long and include at least one symbol.";
        	} else {
            		$hash = password_hash($new, PASSWORD_DEFAULT);
            		$update = $conn->prepare("UPDATE users SET password = ?, is_default_password = 0 WHERE user_id = ?");
            		$update->bind_param("si", $hash, $_SESSION['user_id']);
            		if ($update->execute()) {
                		$success = "Password changed successfully! Please log in again.";
                		session_destroy(); // force re-login
                		header("Refresh:2; url=login.php"); // redirect after 2 seconds
            		} else {
                		$error = "Error updating password.";
            		}
        	}
    	} else {
        	$error = "New passwords do not match.";
    	}
}
?>
<!DOCTYPE html>
<html>
<head>
    	<title>Change Password</title>
</head>
<body>
    	<h2>Change Your Password</h2>
    	<p>You must change your default password before accessing the system.</p>
    	<hr>

    	<!-- Feedback messages -->
    	<?php if($error != "") echo "<p style='color:red'>$error</p>"; ?>
    	<?php if($success != "") echo "<p style='color:green'>$success</p>"; ?>

    	<!-- Password change form -->
    	<form method="POST">
        	New Password: <input type="password" name="new_password" required><br><br>
        	Confirm New Password: <input type="password" name="confirm_password" required><br><br>
        	<button type="submit">Change Password</button>
    	</form>
</body>
</html>
