<?php
/*
ASSESSOR_DASHBOARD.php
Purpose: Dashboard page for Assessor users
Features:
- Session & role validation (assessor only)
- Session timeout security (10 minutes)
- Display last login timestamp
- Auto-updating current date & time (JavaScript refresh every second)
- Live search box with AJAX filtering (students by name or matric no)
- Default view shows first 5 assigned students
- Each student row includes "View Report Card" button
- Link to view_assigned_students.php for full list
- Notification polling for new assignments/updates
*/

session_start();
include("includes/config.php");

// Check login and role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'assessor') {
    	header("Location: login.php");
    	exit();
}

// Session timeout (10 minutes)
if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 600)) {
    	session_unset();
    	session_destroy();
    	header("Location: login.php?expired=1");
    	exit();
}
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
        	window.onload = function() {
            		updateTime();
            		loadStudents(""); // load default students
            		checkNotifications(); // initial notification check
        	};

        	// Live search with AJAX
        	function loadStudents(query) {
            		const xhr = new XMLHttpRequest();
            		xhr.open("GET", "search_students.php?q=" + encodeURIComponent(query), true);
            		xhr.onload = function() {
                		if (xhr.status === 200) {
                    			document.getElementById("results").innerHTML = xhr.responseText;
                		}	
            		};
            		xhr.send();
        	}

        	// Poll for notifications
        	function checkNotifications() {
            		fetch("fetch_notifications.php")
                		.then(response => response.json())
                		.then(data => {
                    			data.forEach(notification => {
                        			// Simple alert (replace with toast for better UX)
                        			alert(notification.message);
                    			});
                		});
        	}
        	setInterval(checkNotifications, 30000); // check every 30 seconds
    	</script>
</head>
<body>
<div class="container">

	<div class="navbar">
		<a href="assessor_dashboard.php" class="active">🏠 Dashboard</a>
		<a href="view_assigned_students.php">👥 Students</a>
		<a href="manage_assessments.php">📝 Assessments</a>
		<a href="student_records.php">📊 Records</a>
		<a href="logout.php">🚪 Logout</a>
    </div>

	<div class="hero-card">
		<div class="icon-title">
			<span>🧑‍🏫</span>
    	    <h1>Assessor Dashboard</h1>
        </div>

    	<p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong> 👋🏻</p>

    	<p><strong>Last Login:</strong>
        	<?php echo $user['last_login'] ? htmlspecialchars($user['last_login']) : "First Login"; ?>
    	</p>

    	<p><strong>Current Date & Time:</strong> <span id="currentTime"></span></p>
    </div>

	<div class="card">
    	<h2>🔍 Search Students</h2>
    	<input type="text" id="searchBox" placeholder="Enter name or matric no" 
               onkeyup="loadStudents(this.value)">
    	<div id="results"></div>
    </div>

    <div class="card">
    	<h2>⚡️ Quick Actions</h2>
		<div class="action-row">
        	<a href="view_assigned_students.php" class="btn">👥 View Assigned Students</a>
        	<a href="manage_assessments.php" class="btn">📝 Manage Assessments</a>
        	<a href="student_records.php" class="btn">📊 Student Records</a>
        </div>
    </div>

</div>
</body>

