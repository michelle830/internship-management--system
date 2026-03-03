<?php
/*
MANAGE_INTERNSHIPS.php
Purpose: Admin assigns students to assessors and records internship details
Features:
- Session & role validation
- Add new internship
- Edit internship details
- Delete internship
- Display all internships
*/

session_start();
include("includes/config.php");

// Ensure only Admins can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
	header("Location: login.php");
	exit();
}

$message = ""; // Feedback message

// Handle Add Internship
if(isset($_POST['add'])) {
	$student_id = $_POST['student_id'];
	$assessor_id = $_POST['assessor_id'];
	$company_name = trim($_POST['company_name']);
	$supervisor_name = trim($_POST['supervisor_name']);
	$duration = trim($_POST['duration']);

	$stmt = $conn->prepare("INSERT INTO internships (student_id, assessor_id, company_name, supervisor_name, duration) VALUES (?, ?, ?, ?, ?)");
	$stmt->bind_param("iisss", $student_id, $assessor_id, $company_name, $supervisor_name, $duration);

	if($stmt->execute()) {
		$message = "Internship added successfully!";
	} else {
		$message = "Error: Could not add internship.";
	}	
	$stmt->close();
}

// Handle Update Internship
if(isset($_POST['update'])) {
	$internship_id = $_POST['internship_id'];
	$company_name = trim($_POST['company_name']);
	$supervisor_name = trim($_POST['supervisor_name']);
    	$duration = trim($_POST['duration']);

    	$stmt = $conn->prepare("UPDATE internships SET company_name=?, supervisor_name=?, duration=? WHERE internship_id=?");
    	$stmt->bind_param("sssi", $company_name, $supervisor_name, $duration, $internship_id);
	
    	if($stmt->execute()) {
        	$message = "Internship updated successfully!";
    	} else {
        	$message = "Error: Could not update internship.";
    	}
    	$stmt->close();
}

// Handle Delete Internship
if(isset($_GET['delete'])) {
    	$internship_id = $_GET['delete'];

    	$stmt = $conn->prepare("DELETE FROM internships WHERE internship_id=?");
    	$stmt->bind_param("i", $internship_id);

    	if($stmt->execute()) {
        	$message = "Internship deleted successfully!";
    	} else {
        	$message = "Error: Could not delete internship.";
    	}
    	$stmt->close();
}

// Fetch all internships with student + assessor names
$sql = "SELECT i.internship_id, s.matric_no, s.student_name, u.full_name AS assessor_name, i.company_name, i.supervisor_name, i.duration
        FROM internships i
        JOIN students s ON i.student_id = s.student_id
        JOIN users u ON i.assessor_id = u.user_id
        ORDER BY i.internship_id ASC";
$result = $conn->query($sql);

// Fetch students and assessors for dropdowns
$students = $conn->query("SELECT student_id, matric_no, student_name FROM students ORDER BY student_name ASC");
$assessors = $conn->query("SELECT user_id, full_name FROM users WHERE role='assessor' ORDER BY full_name ASC");
?>

<!DOCTYPE html>
<html>
<head>
    	<title>Manage Internships</title>
</head>
<body>
    	<h1>Manage Internships</h1>
    	<a href="admin_dashboard.php">Back to Dashboard</a>
    	<hr>

    	<!-- Show feedback message -->
    	<?php if($message != "") echo "<p style='color:red'>$message</p>"; ?>

    	<!-- Add Internship Form -->
    	<h3>Add New Internship</h3>
    	<form method="POST">
        	Student:
        	<select name="student_id" required>
            		<option value="">-- Select Student --</option>
            		<?php while($s = $students->fetch_assoc()) { ?>
                		<option value="<?php echo $s['student_id']; ?>">
                    		<?php echo htmlspecialchars($s['student_name'])." (".$s['matric_no'].")"; ?>
                		</option>
            		<?php } ?>
        	</select><br><br>

        	Assessor:
        	<select name="assessor_id" required>
            		<option value="">-- Select Assessor --</option>
            		<?php while($a = $assessors->fetch_assoc()) { ?>
                		<option value="<?php echo $a['user_id']; ?>">
                    			<?php echo htmlspecialchars($a['full_name']); ?>
                		</option>
            		<?php } ?>
        	</select><br><br>

        	Company: <input type="text" name="company_name" required><br><br>
        	Supervisor: <input type="text" name="supervisor_name" required><br><br>
        	Duration: <input type="text" name="duration" required><br><br>
        	<button type="submit" name="add">Add Internship</button>
    	</form>
    	<hr>

    	<!-- Internship List -->
    	<h3>Internship Records</h3>
    	<table border="1" cellpadding="5">
        	<tr>
            		<th>ID</th><th>Student</th><th>Assessor</th><th>Company</th><th>Supervisor</th><th>Duration</th><th>Actions</th>
        	</tr>
        	<?php while($row = $result->fetch_assoc()) { ?>
        	<tr>
            		<td><?php echo htmlspecialchars($row['internship_id']); ?></td>
            		<td><?php echo htmlspecialchars($row['student_name'])." (".$row['matric_no'].")"; ?></td>
            		<td><?php echo htmlspecialchars($row['assessor_name']); ?></td>
            		<td><?php echo htmlspecialchars($row['company_name']); ?></td>
            		<td><?php echo htmlspecialchars($row['supervisor_name']); ?></td>
            		<td><?php echo htmlspecialchars($row['duration']); ?></td>
            		<td>
                		<!-- Edit Form -->
                		<form method="POST" style="display:inline;">
                    			<input type="hidden" name="internship_id" value="<?php echo $row['internship_id']; ?>">
                    			Company: <input type="text" name="company_name" value="<?php echo htmlspecialchars($row['company_name']); ?>">
                    			Supervisor: <input type="text" name="supervisor_name" value="<?php echo htmlspecialchars($row['supervisor_name']); ?>">
                    			Duration: <input type="text" name="duration" value="<?php echo htmlspecialchars($row['duration']); ?>">
                    			<button type="submit" name="update">Update</button>
                		</form>
                		<!-- Delete Link -->
                		<a href="manage_internships.php?delete=<?php echo $row['internship_id']; ?>" 
                   		onclick="return confirm('Delete this internship?');">Delete</a>
            		</td>
        	</tr>
        	<?php } ?>
    	</table>
</body>
</html>