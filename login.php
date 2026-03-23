<?php
/*
LOGIN.php - User Login Page
Purpose: Authenticate Admin and Assessor users
Features:
- Session validation
- Role-based redirection
- Last login update
- Default password enforcement (redirect to change_password.php)
*/

session_start();
include("includes/config.php");

$error = "";

if (isset($_POST['login'])) {
    	$username = trim($_POST['username']);
    	$password = trim($_POST['password']);

    	$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    	$stmt->bind_param("s", $username);
    	$stmt->execute();
    	$result = $stmt->get_result();

    	if ($result->num_rows == 1) {
        	$row = $result->fetch_assoc();

        	if (password_verify($password, $row['password'])) {
            		// Update last login
            		$update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            		$update->bind_param("i", $row['user_id']);
            		$update->execute();
            		$update->close();

            		// Store session info
            		$_SESSION['user_id'] = $row['user_id'];
            		$_SESSION['role'] = $row['role'];
            		$_SESSION['full_name'] = $row['full_name'];
            		$_SESSION['last_activity'] = time();

            		// 🚨 Check if still using default password
            		if ($row['is_default_password'] == 1) {
                		header("Location: change_password.php");
                		exit();
            		}

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
<!DOCTYPE html>
<html>
<head>
    	<title>Login - Internship System</title>
</head>
<body>
    	<h2>Login</h2>
    	<?php if($error != "") echo "<p style='color:red'>$error</p>";?>
    	<form method="POST">
        	Username: <input type="text" name="username" required><br><br>
        	Password: <input type="password" name="password" required><br><br>
        	<button type="submit" name="login">Login</button>
    	</form>
</body>
</html>
