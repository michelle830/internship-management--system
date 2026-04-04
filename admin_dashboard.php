<?php
/*
ADMIN_DASHBOARD.php
Purpose: Dashboard page for Admin users
Features: 
- Session validation
- Role validation
- Session timeout security
- Display last login
- Auto-updating current date & time (JavaScript refresh every second)
*/
 
session_start();
include("includes/config.php");

// Session Validation

// Check if user is logged in 
if(!isset($_SESSION['user_id'])) {
    	header("Location: login.php");
    	exit();
}

// Check if user is admin 
if($_SESSION['role'] != 'admin') {
    	header("Location: login.php");
    	exit();
}

// Session Timeout 

// Auto logout after 10 minutes of inactivity
if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 600)) {
    	session_unset();
    	session_destroy();
    	header("Location: login.php");
    	exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Fetch Last Login
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT last_login FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    	<title>Admin Dashboard</title>
		<link rel="stylesheet" href="css/style.css">

    	<script>
        	// Auto-update current time every second
        	function updateTime() {
            		const now = new Date();
            		document.getElementById("currentTime").innerHTML = 
                		now.getFullYear() + "-" +
                		String(now.getMonth()+1).padStart(2,'0') + "-" +
                		String(now.getDate()).padStart(2,'0') + " " +
                		String(now.getHours()).padStart(2,'0') + ":" +
                		String(now.getMinutes()).padStart(2,'0') + ":" +
                		String(now.getSeconds()).padStart(2,'0');
        	}
        	setInterval(updateTime, 1000);
        	window.onload = updateTime;
    	</script>
</head>
<body>
<div class="container">

    <!-- Navbar -->
	<div class="navbar">
		<a href="#">Dashboard</a>
		<a href="manage_students.php">Students</a>
		<a href="manage_internships.php">Internships</a>
		<a href="manage_assessments.php">Assessments</a>
		<a href="register_user.php">Register User</a>
		<a href="logout.php">Logout</a>
    </div>

	<!-- Welcome Card -->
	<div class="card">
		<h2>Welcome, <?php echo $_SESSION['full_name']; ?> 👋🏻</h2>
		<p><strong>Role:</strong> <?php echo strtoupper($_SESSION['role']); ?></p>
		<p><strong>Last login:</strong>
		    <?php echo $user['last_login'] ? $user['last_login'] : "First Login"; ?>
        </p>
		<p><strong>Current Time:</strong> <span id="currentTime"></span></p>
    </div>

	<!-- Stats -->
	 <div class="stats">
		<div class="card stat-box">
			<h3>Total Students</h3>
			<p>120</p>
        </div>

		<div class="card stat-box">
			<h3>Internships</h3>
			<p>45</p>
        </div>

		<div class="card stat-box">
			<h3>Assessments</h3>
			<p>30</p>
        </div>
    </div>

	<!-- Quick Actions -->
	 <div class="card">
		<h2>Quick Actions</h2>
		<button onclick="location.href='manage_students.php'">Manage Students</button>
		<button onclick="location.href='manage_internships.php'">Manage Internships</button>
		<button onclick="location.href='register_user.php'">Register User</button>
    </div>

</div>

</body>
</html>


