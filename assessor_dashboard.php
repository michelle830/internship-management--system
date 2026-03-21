<?php
/*
ASSESSOR_DASHBOARD.php
Purpose: Dashboard page for Assessor users
Features:
- Session validation
- Role validation
- Session timeout security
- Display last login
*/

session_start();
include("includes/config.php");

// Check if user is logged in 
if(!isset($_SESSION['user_id'])) {
	header("Location: login.php");
	exit();
}

// Check if user is assessor
if($_SESSION['role'] != 'assessor') {
	header("Location: login.php");
	exit();
}

// Session timeout (10 minutes)
if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 600)) {
	session_unset();
	session_destroy();
	header("Location:login.php");
	exit();
}

// Update last activity 
$_SESSION['last_activity'] = time();

// Fetch last login
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT last_login FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
	<title>Assessor Dashboard</title>
</head>
<body>
	<h1>Assessor Dashboard</h1>
	
	<p>Welcome, <?php echo $_SESSION['full_name']; ?>!</p>
	
	<p><strong>Last Login: </strong>
		<?php echo $user['last_login'] ? $user['last_login'] : "First Login"; ?>
	</p>
	
	<p><strong>Current Date & Time: </strong> <?php echo date("Y-m-d H:i:s"); ?></p>
	<hr>
	
	<h3>Navigation</h3>
	<ul>
		<li><a href="view_assigned_students.php">View Assigned Students</a></li>
		<li><a href="manage_assessments.php">Manage Assessments</a></li>
		<li><a href="logout.php">Logout</a></li>
	</ul>
</body>
</html>