<?php
/*
REGISTER_USER.php
Purpose: Admin creates new user accounts (Admin/Assessor)
Features:
- Session & role validation
- Password strength validation (>= 8 chars, must include symbol)
- Password confirmation
- Username uniqueness check
- Secure password hashing
- Audit logging (records who created the account and when)
*/

session_start();
include("includes/config.php");

// Session & Role Validation: Only Admins can access this page
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
	header("Location: login.php");
	exit();
}

$message = "";	// Variable to store feedback messages

// Handle Form Submission 
if(isset($_POST['register'])) {
	// Collect and sanitise form inputs 
	$username = trim($_POST['username']);
	$full_name = trim($_POST['full_name']);
	$role = $_POST['role'];
	$password = $_POST['password'];
	$confirm_pw = $_POST['confirm_password'];

	// Step 1: Check if username already exists
	$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$stmt->store_result();

	if($stmt->num_rows > 0) {
		$message = "Username already exists. Please choose another.";
	} else {
		// Step 2: Validate password strength
		if(strlen($password) < 8 || !preg_match('/[^a-zA-Z0-9]/', $password)) {
			$message = "Password must be at least 8 characters long and include at least one symbol.";
		} elseif($password != $confirm_pw) {
			$message = "Passwords do not match!";
		} else {
			// Step 3: Hash password securely
			$hashed_password = password_hash($password, PASSWORD_DEFAULT);

			// Step 4: Insert new user into database with audit logging
			$stmt = $conn->prepare("INSERT INTO users (username, full_name, role, password, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
			$stmt->bind_param("sssss", $username, $full_name, $role, $hashed_password, $_SESSION['full_name']);
			
			if($stmt->execute()) {
				$message = "User registered successfully!";
			} else {
				$message = "Error: Could not register user.";
			}
			$stmt->close();
		}
	}
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Register New User</title>
</head>
<body>
	<h1>Register New User</h1>
	<a href="admin_dashboard.php">Back to Dashboard</a>
	<hr>
	
	<!-- Show feedback message -->
	<?php if($message != "") echo "<p style='color:red'>$message</p>";?>
	
	<!-- Registration Form -->
	<form method="POST">
		Username: <input type="text" name="username" required><br><br>
		Full Name: <input type="text" name="full_name" required><br><br>
		Role:
			<select name="role" required>
				<option value="admin">Admin</option>
				<option value="assessor">Assessor</option>
			</select><br><br>
			Password: <input type="password" name="password" required><br><br>
			Confirm Password: <input type="password" name="confirm_password" required><br><br>
			<button type="submit" name="register">Register User</button>
	</form>
</body>
</html>
			