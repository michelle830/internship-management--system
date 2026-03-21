<?php
/*
LOGIN.php - User Login Page
Purpose: Authenticate Admin and Assessor users
*/

// Start session to store login information
session_start();

// Include database connection
include("includes/config.php");

// Initialize error message
$error = "";

// Check if the login form is submitted
if (isset($_POST['login'])) {
	
	// Get username and password from form
	$username = trim($_POST['username']);
	$password = trim($_POST['password']);

	// Prepare SQL statement to prevent SQL injection
	$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
	$stmt->bind_param("s", $username);
	$stmt->execute();

	// Get the result
	$result = $stmt->get_result();

	if($result->num_rows == 1) {
		// Fetch user data
		$row = $result->fetch_assoc();

		// Check password
		if (password_verify ($password, $row['password'])) {
			
			// Update last login time 
			$update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
			$update->bind_param("i", $row['user_id']);
			$update->execute();
			$update->close();
	
			// Store user info in session variables
			$_SESSION['user_id'] = $row['user_id'];
			$_SESSION['role'] = $row['role'];
			$_SESSION['full_name'] = $row['full_name'];
			$_SESSION['last_activity'] = time();

			// Redirect based on role
			if ($row['role'] == 'admin') {
				header("Location: admin_dashboard.php");
			} else {
				header("Location: assessor_dashboard.php");
			}
			exit();
		} else {
			$error = "Incorrect password!";
		} 
	} else {
		$error = "Username not found!";
	}
}
?>

<!-- HTML part: Display login form -->
<!DOCTYPE html>
<html>
<head>
	<title>Login - Internship System</title>
</head>
<body>
	<h2>Login</h2>
	
	<!-- Show error if exists -->
	<?php if($error != "") echo "<p style='color:red'>$error</p>";?>
	
	<!-- Login form -->
	<form method="POST">
		Username: <input type="text" name="username" required><br><br>
		Password: <input type="password" name="password" required><br><br>
		<button type="submit" name="login">Login</button>
	</form>
</body>
</html>