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

        	setInterval(updateTime, 1000);
        	window.onload = function() {
            		updateTime();
            		loadStudents(""); // load default students
            		checkNotifications(); // initial notification check
        	};

        	// Poll for notifications
        	function checkNotifications() {
            	fetch("fetch_notifications.php")
            		.then(response => response.json())
            		.then(data => {
						document.getElementById("notifCount").innerText = data.length;

						let html = "";

						if (data.length === 0) {
							html = "<p>No new notifications</p>";
						} else {
                			data.forEach(notification => {
                        		html += "<p>" + notification.message + "</p>";
                    		});
                        }

						document.getElementById("notifList").innerHTML = html;
                	});
        	}

			document.addEventListener("DOMContentLoaded", function () {
				updateTime();
				loadStudents("");
				checkNotifications();

				setInterval(updateTime, 1000);
				setInterval(checkNotifications, 30000);

				document.getElementById("notificationBox").onclick = function () {
					const panel = document.getElementById("notifPanel");
					panel.style.display = (panel.style.display === "none") ? "block" : "none";
				};
			});

			document.addEventListener("DOMContentLoaded", function () {

				document.getElementById("notificationBox").onclick = function () {
					const panel = document.getElementById("notifPanel");
					panel.style.display = (panel.style.display === "none") ? "block" : "none";
				};

				document.getElementById("closeNotif").onclick = function () {
					document.getElementById("notifPanel").style.display = "none";
				};
			});
    	</script>

</head>
<body>
<div class="container">

	<div class="navbar">
		<div id="notificationBox" class="notif-box">
			🔔 <span id="notifCount">0</span>
        </div>

		<a href="assessor_dashboard.php" class="active">🏠 Dashboard</a>
		<a href="view_assigned_students.php">👥 Students</a>
		<a href="manage_assessments.php">📝 Assessments</a>
		<a href="student_records.php">📊 Records</a>
		<a href="logout.php">🚪 Logout</a>
    </div>

	<div id="notifPanel" class="card notif-panel" style="display:none;">

		<div class="notif-header">
		    <h3>🔔 Notifications</h3>
			<span id="closeNotif" class="close-btn">❌</span>
        </div>

		<div id="notifList">No notifications</div>

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

    <div class="card quick-actions">
    	<h2>⚡️ Quick Actions</h2>
		<div class="action-row">

			<div class="action-item">
        	    <a href="view_assigned_students.php" class="btn action-btn">👥 View Assigned Students</a>
				<span class="help-icon">ℹ︎
					<span class="tooltip-text">View the students assigned to you.</span>
                </span>
            </div>

			<div class="action-item">
        	    <a href="manage_assessments.php" class="btn action-btn">📝 Manage Assessments</a>
				<span class="help-icon">ℹ︎
					<span class="tooltip-text">Enter marks and evaluate students.</span>
                </span>
            </div>

			<div class="action-item">
                <a href="student_records.php" class="btn action-btn">📊 Student Records</a>
				<span class="help-icon">ℹ︎
					<span class="tooltip-text">View student results and reports.</span>
                </span>
            </div>

        </div>
    </div>

</div>
</body>
</html>

