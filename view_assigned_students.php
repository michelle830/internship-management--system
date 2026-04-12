<?php
/*
VIEW_ASSIGNED_STUDENTS.php
Purpose: Display students assigned to the logged-in assessor
Features:
- Session & role validation (assessor only)
- List of students with internship details
*/

session_start();
include("includes/config.php");

// Ensure only Assessors can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'assessor') {
	header("Location: login.php");
    	exit();
}

$assessor_id = $_SESSION['user_id'];

// Fetch students assigned to this assessor
$sql = "SELECT i.internship_id, s.student_id, s.student_name, s.matric_no
	FROM internships i
        JOIN students s ON i.student_id = s.student_id
        WHERE i.assessor_id = ?
        ORDER BY s.student_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assessor_id);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    	<title>View Assigned Students</title>
		<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">

	<div class="navbar">
		<a href="assesor_dashboard.php">🏠 Dashboard</a>
		<a href="view_assigned_students.php" class="active">👥 Students</a>
		<a href="manage_assessments.php">📝 Assessments</a>
		<a href="student_records.php">📊 Records</a>
		<a href="logout.php">🚪 Logout</a>
    </div>

	<div class="hero-card">
		<div class="icon-title">
			<span>👥</span>
    	    <h1>Assigned Students</h1>
        </div>
    	<p>View the students assigned to you for internship assessment.</p>
    </div>

	<div class="card">
		<h2>Student List</h2>
		<div class="table-wrapper">
			<table>
        	    <tr>
            		<th>Internship ID</th>
            		<th>Student ID</th>
            		<th>Name</th>
            		<th>Matric No</th>
        	    </tr>
        	
				<?php while($row = $students->fetch_assoc()) { ?>
        	    <tr>
            		<td><?php echo htmlspecialchars($row['internship_id']); ?></td>
            		<td><?php echo htmlspecialchars($row['student_id']); ?></td>
            		<td><?php echo htmlspecialchars($row['student_name']); ?></td>
            		<td><?php echo htmlspecialchars($row['matric_no']); ?></td>
        	    </tr>
        	    <?php } ?>
    	    </table>
        </div>
    </div>
	
</div>
</body>
</html>
