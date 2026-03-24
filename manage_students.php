<?php
/*
MANAGE_STUDENTS.php
Purpose: Admin CRUD operations for student records
Features:
- Session & role validation
- Add new student
- Edit existing student
- Delete student
- Display all students
*/

session_start();
include("includes/config.php");

// Ensure only Admins can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
	header("Location: login.php");
	exit();
}

$message = "";	// Feedback message

// Handle Add Student
if(isset($_POST['add'])){
    	$student_name = trim($_POST['student_name']);
    	$programme = trim($_POST['programme']);

    	// Get latest matric number
    	$result = $conn->query("SELECT matric_no FROM students ORDER BY student_id DESC LIMIT 1");
    	if($row = $result->fetch_assoc()) {
        	$last_matric = $row['matric_no']; // e.g. A00005
        	$num = intval(substr($last_matric, 1)) + 1;
        	$new_matric = "A" . str_pad($num, 5, "0", STR_PAD_LEFT);
    	} else {
        	$new_matric = "A00001"; // first student
    	}

    	// Insert new student with auto matric
    	$stmt = $conn->prepare("INSERT INTO students (matric_no, student_name, programme) VALUES (?, ?, ?)");
    	$stmt->bind_param("sss", $new_matric, $student_name, $programme);

    	if($stmt->execute()) {
        	$message = "Student added successfully! Matric No: ".$new_matric;
    	} else {
        	$message = "Error: Could not add student.";
    	}
   	$stmt->close();
}


// Handle Update Student
if(isset($_POST['update'])) {
	$student_id = $_POST['student_id'];
	$student_name = trim($_POST['student_name']);
	$programme = trim($_POST['programme']);
	
	$stmt = $conn->prepare("UPDATE students SET student_name=?, programme=? WHERE student_id=?");
	$stmt->bind_param("ssi", $student_name, $programme, $student_id);

	if($stmt->execute()) {
		$message = "Student updated successfully!";
	} else {
		$message = "Error: Could not update student.";
	}
	$stmt->close();
}

// Handle Delete Student
if(isset($_GET['delete'])) {
	$student_id = $_GET['delete'];
	
	$stmt = $conn->prepare("DELETE FROM students WHERE student_id=?");
	$stmt->bind_param("i", $student_id);

	if($stmt->execute()) {
		$message = "Student deleted successfully!";
	} else {
		$message = "Error: Could not delete student.";
	}
	$stmt->close();
}

// Fetch all students
$result = $conn->query("SELECT * FROM students ORDER BY student_id ASC");
?>

<!DOCTYPE html>
<html>
<head>
	<title>Manage Students</title>
</head>
<body>
	<h1>Manage Students</h1>
	<a href="admin_dashboard.php">Back to Dashboard</a>
	<hr>
	
	<!-- Show feedback message -->
	<?php if($message != "") echo "<p style = 'color:red'>$message</p>"; ?>
	
	<!-- Add Student Form -->
	<h3>Add New Student</h3>
	<form method="POST">
    		Name: <input type="text" name="student_name" required><br><br>
    		Programme: <input type="text" name="programme" required><br><br>
    		<button type="submit" name="add">Add Student</button>
	</form>

	<hr>
	
	<!-- Student List -->
	<h3>Student Records</h3>
	<table border="1" cellpadding="5">
		<tr>
			<th>ID</th><th>Matric No</th><th>Name</th><th>Programme</th><th>Actions</th>
		</tr>
		<?php while($row = $result->fetch_assoc()) {?>
		<tr>
			<td><?php echo htmlspecialchars($row['student_id']); ?></td>
			<td><?php echo htmlspecialchars($row['matric_no']); ?></td>
			<td><?php echo htmlspecialchars($row['student_name']); ?></td> 
			<td><?php echo htmlspecialchars($row['programme']); ?></td> 
			<td> 
				<!-- Edit Form --> 
				<form method="POST" style="display:inline;"> 
					<input type="hidden" name="student_id" value="<?php echo $row['student_id']; ?>"> 
					Name: <input type="text" name="student_name" value="<?php echo htmlspecialchars($row['student_name']); ?>"> 
					Programme: <input type="text" name="programme" value="<?php echo htmlspecialchars($row['programme']); ?>"> 
					<button type="submit" name="update">Update</button> 
				</form> 
				<!-- Delete Link --> 
				<a href="manage_students.php?delete=<?php echo $row['student_id']; ?>" onclick="return confirm('Delete this student?');">Delete</a> 
			</td> 
		</tr> 
		<?php } ?> 
	</table> 
</body> 
</html>