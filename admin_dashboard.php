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
		<a href="admin_dashboard.php" class="active">🏠 Dashboard</a>
		<a href="manage_students.php">🎓 Students</a>
		<a href="manage_internships.php">🏢 Internships</a>
		<a href="register_user.php">👤 Register User</a>
		<a href="logout.php">🚪 Logout</a>
    </div>

	<!-- Welcome Card -->
	<div class="hero-card">
		<img src="https://cdn-icons-png.flaticon.com/512/3062/3062634.png" width="60" style="position:absolute; top:20px; right:20px;">
		<div class="icon-title">
			<span>💼</span>
			<h1>Admin Dashboard</h1>
        </div>

		<p>Welcome, <strong><?php echo $_SESSION['full_name']; ?></strong> 👋🏻</p>
		<p>You are logged in as an administrator. Manage your system efficiently here.</p>
		<p><strong>Role:</strong> <?php echo strtoupper($_SESSION['role']); ?></p>
		<p><strong>Last login:</strong>
		    <?php echo $user['last_login'] ? $user['last_login'] : "First Login"; ?>
        </p>
		<p><strong>Current Time:</strong> <span id="currentTime"></span></p>
    </div>

	<!-- Quick Actions -->
	<div class="card quick-actions-card">
		<h2>🚀 Quick Actions</h2>
		<div class="action-row">

			<div class="action-item">
			    <a href="manage_students.php" class="btn action-btn">🎓 Manage Students</a>
				<span class="help-icon">ℹ︎
					<span class="tooltip-text">View, add, edit, and delete student records.</span>
                </span>
            </div>

			<div class="action-item">
			    <a href="manage_internships.php" class="btn action-btn">🏢 Manage Internships</a>
				<span class="help-icon">ℹ︎
					<span class="tooltip-text">Assign students to internships and manage company details.</span>
                </span>
            </div>

			<div class="action-item">
			    <a href="register_user.php" class="btn action-btn">👤 Register User</a>
				<span class="help-icon">ℹ︎
					<span class="tooltip-text">Create login accounts for assessors or other users.</span>
                </span>
            </div>

        </div>
    </div>

	<!-- Stats -->
	 <div class="stats">
		<div class="stat-box">
			<h3>🎓 Total Students</h3>
			<p>120</p>
        </div>

		<div class="stat-box">
			<h3>🏢 Internships</h3>
			<p>45</p>
        </div>

		<div class="stat-box">
			<h3>📝 Assessments</h3>
			<p>30</p>
        </div>
    </div>

	<!-- System Features -->
	<div class="card">
        <h2>✨ System Features</h2>
        <div class="feature-grid">
            <div class="feature-box">
                <h3><img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png" width="24"> Student Management</h3>
                <p>Add, edit, delete, and manage student records efficiently.</p>
            </div>

            <div class="feature-box">
                <h3>🏢 Internship Management</h3>
                <p>Assign internships and track placements easily.</p>
            </div>

            <div class="feature-box">
                <h3>📝 Assessment System</h3>
                <p>Enter marks and evaluate student performance.</p>
            </div>

            <div class="feature-box">
                <h3>📊 Reports</h3>
                <p>Generate report cards and review results.</p>
            </div>
         </div>
    </div>

</div>
</body>
</html>


