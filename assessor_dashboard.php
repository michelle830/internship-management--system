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
    	header("Location: login.php");
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
    	</script>
</head>
<body>
    	<h1>Assessor Dashboard</h1>
    
    	<p>Welcome, <?php echo $_SESSION['full_name']; ?>!</p>
    
    	<p><strong>Last Login: </strong>
        	<?php echo $user['last_login'] ? $user['last_login'] : "First Login"; ?>
    	</p>
    
    	<p><strong>Current Date & Time: </strong> <span id="currentTime"></span></p>
    	<hr>
    
    	<h3>Search Students</h3>
    		<input type="text" id="searchBox" placeholder="Enter name or matric no" onkeyup="loadStudents(this.value)" size="30">
    		<div id="results"></div>
    
    	<hr>
    	<h3>Navigation</h3>
    	<ul>
        	<li><a href="view_assigned_students.php">View Assigned Students</a></li>
        	<li><a href="manage_assessments.php">Manage Assessments</a></li>
        	<li><a href="student_records.php">View Student Records</a></li>
        	<li><a href="logout.php">Logout</a></li>
    	</ul>
</body>
</html>
