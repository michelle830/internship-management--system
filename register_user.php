<?php
/*
REGISTER_USER.php
Purpose: Admin creates new user accounts (Admin/Assessor)
Features:
- Session & role validation
- Username uniqueness check
- Secure default password hashing
- Flag new accounts with is_default_password = 1
- Audit logging (records who created the account and when)
*/

session_start();
include("includes/config.php");

// Session & Role Validation: Only Admins can access this page
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    	header("Location: login.php");
    	exit();
}

$message = ""; // Feedback messages

// Handle Form Submission
if(isset($_POST['register'])) {
    	$username   = trim($_POST['username']);
    	$full_name  = trim($_POST['full_name']);
    	$role       = $_POST['role'];

    	// Step 1: Check if username already exists
    	$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    	$stmt->bind_param("s", $username);
    	$stmt->execute();
    	$stmt->store_result();

    	if($stmt->num_rows > 0) {
        	$message = "Username already exists. Please choose another.";
    	} else {
        	// Step 2: Assign default password
        	$default_password = "Welcome123"; // Admin-defined default
        	$hashed_password  = password_hash($default_password, PASSWORD_DEFAULT);

        	// Step 3: Insert new user into database with audit logging
        	$stmt = $conn->prepare("INSERT INTO users (username, full_name, role, password, created_by, created_at, is_default_password) 
            		VALUES (?, ?, ?, ?, ?, NOW(), 1)");
        	$stmt->bind_param("sssss", $username, $full_name, $role, $hashed_password, $_SESSION['full_name']);

        	if($stmt->execute()) {
            		$message = "User registered successfully! Default password is 'Welcome123'.";
        	} else {
            		$message = "Error: Could not register user.";
        	}
        	$stmt->close();
    	}
}
?>
<!DOCTYPE html>
<html>
<head>
    	<title>Register New User</title>
		<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">

	<div class="navbar">
		<a href="admin_dashboard.php">Dashboard</a>
		<a href="manage_students.php">Students</a>
		<a href="manage_internships.php">Internships</a>
		<a href="manage_assessments.php">Assessments</a>
		<a href="register_user.php">Register User</a>
		<a href="logout.php">Logout</a>
    </div>

	<div class="card">
    	<h1>Register New User</h1>
    	<p>Create a new admin or assessor account here.</p>
    </div>

    <!-- Show feedback message -->
	<div class="card">
    	<?php 
		if($message != "") {
			$class = (strpos($message, 'successfully') !== false) ? 'success' : 'error';
			echo "<div class='$class'>$message</div>";
		}
		?>

    	<!-- Registration Form -->
    	<form method="POST">
        	<label>Username</label>
			<input type="text" name="username" required>


        	<label>Full Name</label>
			<input type="text" name="full_name" required>

        	<label>Role</label>
        	<select name="role" required>
				<option value="">-- Select Role -- </option>
            	<option value="admin">Admin</option>
            	<option value="assessor">Assessor</option>
        	</select>

        	<button type="submit" name="register">Register User</button>
    	</form>
    </div>
</div>
</body>
</html>

