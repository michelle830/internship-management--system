<?php
/*
MANAGE_STUDENTS.php
Purpose: Admin CRUD operations for student records
Features:
- Session & role validation
- Add new student (auto matric ID starting from A00001)
- Duplicate check with confirmation (manual add + bulk upload)
- Bulk upload students via CSV (skip header, confirm duplicates)
- Edit existing student
- Delete student
- Display all students
- Flash messages (session-based, one-time display)
*/

session_start();
include("includes/config.php");

// Ensure only Admins can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    	header("Location: login.php");
    	exit();
}

// Flash message handling
$message = "";
if(isset($_SESSION['flash_message'])) {
    	$message = $_SESSION['flash_message'];
    	unset($_SESSION['flash_message']); // clear after showing
}

// -------------------- ADD STUDENT --------------------
if(isset($_POST['add'])){
    	$student_name = trim($_POST['student_name']);
    	$programme = trim($_POST['programme']);

    	// Duplicate check
    	$check = $conn->prepare("SELECT student_id FROM students WHERE student_name=? AND programme=?");
    	$check->bind_param("ss", $student_name, $programme);
    	$check->execute();
    	$check->store_result();

    	if($check->num_rows > 0 && !isset($_POST['confirm_add'])) {
        	$_SESSION['flash_message'] = "Duplicate student found: $student_name ($programme). Do you want to add another anyway?";
        	$_SESSION['pending_student'] = ['name'=>$student_name, 'programme'=>$programme];
        	header("Location: manage_students.php?confirm=1");
        	exit();
    	}
    	$check->close();

    	// If confirmed or no duplicate, proceed
    	if(isset($_POST['confirm_add']) || $check->num_rows == 0) {
        	$result = $conn->query("SELECT matric_no FROM students ORDER BY student_id DESC LIMIT 1");
        	if($row = $result->fetch_assoc()) {
            		$last_matric = $row['matric_no'];
            		$num = intval(substr($last_matric, 1)) + 1;
            		$new_matric = "A" . str_pad($num, 5, "0", STR_PAD_LEFT);
        	} else {
            		$new_matric = "A00001";
        	}

        	$stmt = $conn->prepare("INSERT INTO students (matric_no, student_name, programme) VALUES (?, ?, ?)");
        	$stmt->bind_param("sss", $new_matric, $student_name, $programme);

        	if($stmt->execute()) {
            		$_SESSION['flash_message'] = "Student added successfully! Matric No: ".$new_matric;
        	} else {
            		$_SESSION['flash_message'] = "Error: Could not add student.";
        	}
        	$stmt->close();
        	header("Location: manage_students.php");
        	exit();
    	}
}

// -------------------- BULK UPLOAD --------------------
if(isset($_POST['bulk_upload'])) {
    	if($_FILES['student_file']['error'] == 0) {
        	$file = fopen($_FILES['student_file']['tmp_name'], "r");
        	$rowCount = 0;
        	$duplicates = [];

        	$isHeader = true;
        	while(($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            		if($isHeader) { $isHeader = false; continue; }

            		$student_name = trim($data[0]);
            		$programme = trim($data[1]);
            		if($student_name == "" || $programme == "") continue;

            		// Check for duplicates
            		$check = $conn->prepare("SELECT student_id FROM students WHERE student_name=? AND programme=?");
           		$check->bind_param("ss", $student_name, $programme);
            		$check->execute();
            		$check->store_result();

            		if($check->num_rows > 0) {
                		$duplicates[] = ['name'=>$student_name, 'programme'=>$programme];
                		$check->close();
                		continue;
            		}
            		$check->close();

            		// Generate matric number
            		$result = $conn->query("SELECT matric_no FROM students ORDER BY student_id DESC LIMIT 1");
            		if($row = $result->fetch_assoc()) {
                		$last_matric = $row['matric_no'];
                		$num = intval(substr($last_matric, 1)) + 1;
                		$new_matric = "A" . str_pad($num, 5, "0", STR_PAD_LEFT);
            		} else {
                		$new_matric = "A00001";
            		}

            		$stmt = $conn->prepare("INSERT INTO students (matric_no, student_name, programme) VALUES (?, ?, ?)");
            		$stmt->bind_param("sss", $new_matric, $student_name, $programme);
            		if($stmt->execute()) $rowCount++;
            		$stmt->close();
        	}
        	fclose($file);

        	if(count($duplicates) > 0) {
            		$_SESSION['pending_bulk'] = $duplicates;
            		$_SESSION['flash_message'] = "$rowCount students uploaded. Found ".count($duplicates)." duplicates. Do you want to add them anyway?";
            		header("Location: manage_students.php?confirm_bulk=1");
            		exit();
        	} else {
            		$_SESSION['flash_message'] = "$rowCount students uploaded successfully!";
            		header("Location: manage_students.php");
            		exit();
        	}
    	} else {
        	$_SESSION['flash_message'] = "Error: Could not upload file.";
        	header("Location: manage_students.php");
        	exit();
    	}	
}

// Handle Confirm Bulk Upload
if(isset($_POST['confirm_bulk_add']) && isset($_SESSION['pending_bulk'])) {
    	$addedCount = 0;
    	foreach($_SESSION['pending_bulk'] as $dup) {
        	$student_name = $dup['name'];
        	$programme = $dup['programme'];

        	$result = $conn->query("SELECT matric_no FROM students ORDER BY student_id DESC LIMIT 1");
        	if($row = $result->fetch_assoc()) {
            		$last_matric = $row['matric_no'];
            		$num = intval(substr($last_matric, 1)) + 1;
            		$new_matric = "A" . str_pad($num, 5, "0", STR_PAD_LEFT);
        	} else {
            		$new_matric = "A00001";
        	}

        	$stmt = $conn->prepare("INSERT INTO students (matric_no, student_name, programme) VALUES (?, ?, ?)");
        	$stmt->bind_param("sss", $new_matric, $student_name, $programme);
        	if($stmt->execute()) $addedCount++;
        	$stmt->close();
    	}
    	unset($_SESSION['pending_bulk']);
    	$_SESSION['flash_message'] = "$addedCount duplicate students added successfully!";
    	header("Location: manage_students.php");
    	exit();
}

// -------------------- UPDATE STUDENT --------------------
if(isset($_POST['update'])) {
    	$student_id = $_POST['student_id'];
    	$student_name = trim($_POST['student_name']);
    	$programme = trim($_POST['programme']);

    	$stmt = $conn->prepare("UPDATE students SET student_name=?, programme=? WHERE student_id=?");
    	$stmt->bind_param("ssi", $student_name, $programme, $student_id);

    	if($stmt->execute()) {
        	$_SESSION['flash_message'] = "Student updated successfully!";
    	} else {
        	$_SESSION['flash_message'] = "Error: Could not update student.";
    	}
    	$stmt->close();
    	header("Location: manage_students.php");
    	exit();
}

// -------------------- DELETE STUDENT --------------------
if(isset($_GET['delete'])) {
    	$student_id = $_GET['delete'];

   	$stmt = $conn->prepare("DELETE FROM students WHERE student_id=?");
    	$stmt->bind_param("i", $student_id);

    	if($stmt->execute()) {
        	$_SESSION['flash_message'] = "Student deleted successfully!";
    	} else {
        	$_SESSION['flash_message'] = "Error: Could not delete student.";
    	}
    	$stmt->close();
    	header("Location: manage_students.php");
    	exit();
}

// -------------------- EXPORT STUDENTS TO CSV --------------------
if(isset($_GET['export_csv'])) {
    	header('Content-Type: text/csv; charset=utf-8');
    	header('Content-Disposition: attachment; filename=students.csv');

    	$output = fopen('php://output', 'w');
    	// Write header row
    	fputcsv($output, ['ID', 'Matric No', 'Name', 'Programme']);

    	$result = $conn->query("SELECT * FROM students ORDER BY student_id ASC");
    	while($row = $result->fetch_assoc()) {
        	fputcsv($output, [$row['student_id'], $row['matric_no'], $row['student_name'], $row['programme']]);
    	}
    	fclose($output);
    	exit();
}

// -------------------- FETCH STUDENTS --------------------
$result = $conn->query("SELECT * FROM students ORDER BY student_id ASC");
?>

<!DOCTYPE html>
<html>
<head>
    	<title>Manage Students</title>
		<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">

	<div class="navbar">
		<a href="admin_dashboard.php">Dashboard</a>
		<a href="manage_students.php">Students</a>
		<a href="manage_internships.php">Internships</a>
		<a href="register_user.php">Register User</a>
		<a href="logout.php">Logout</a>
    </div>

	<div class="hero-card">
		<div class="icon-title">
			<span>🎓</span>
			<h1>Manage Students</h1>
        </div>
		<p>Add, update, delete, upload, and export student records.</p>
    </div>

    	<!-- Show feedback message only if not in confirmation mode -->
    	<?php if($message != "" && !isset($_GET['confirm']) && !isset($_GET['confirm_bulk'])): ?>
			<div class="card">
				<div class="info-box"><<?php echo htmlspecialchars($message); ?></div>
		    </div>
    	<?php endif; ?>

    	<!-- Confirmation for manual add -->
    	<?php if(isset($_GET['confirm']) && isset($_SESSION['pending_student'])): ?>
			<div class="card">
				<h3>Duplicate Student Found</h3>
        	    <p><?php echo htmlspecialchars($message); ?></p>
        	    <form method="POST">
            		<input type="hidden" name="student_name" value="<?php echo htmlspecialchars($_SESSION['pending_student']['name']); ?>">
            		<input type="hidden" name="programme" value="<?php echo htmlspecialchars($_SESSION['pending_student']['programme']); ?>">
            		<button type="submit" name="confirm_add">Yes, Add Anyway</button>
            		<a href="manage_students.php" class="btn btn-secondary">Cancel</a>
        	    </form>
		    </div>
    	<?php endif; ?>

    	<!-- Confirmation for bulk upload -->
    	<?php if(isset($_GET['confirm_bulk']) && isset($_SESSION['pending_bulk'])): ?>
			<div class="card">
				<h3>Duplicate Students Found in CSV</h3>
        	    <p><?php echo htmlspecialchars($message); ?></p>
        	    <form method="POST">
            		<button type="submit" name="confirm_bulk_add">Yes, Add Duplicates</button>
            		<a href="manage_students.php" class="btn btn-secondary">Cancel</a>
        	    </form>
        	    <ul style="margin-top: 15px;">
            		<?php foreach($_SESSION['pending_bulk'] as $dup): ?>
                		<li><?php echo htmlspecialchars($dup['name'])." (".htmlspecialchars($dup['programme']).")"; ?></li>
            		<?php endforeach; ?>
        	    </ul>
			</div>
    	<?php endif; ?>

    	<!-- Add Student Form -->
		<div class="card">
            <h3>Add New Student</h3>
    	    <form method="POST">
        	    <label>Name</label>
				<input type="text" name="student_name" required>

        	    <label>Programme</label>
				<input type="text" name="programme" required>

        	    <button type="submit" name="add">Add Student</button>
    	    </form>
		</div>

    	<!-- Bulk Upload Form -->
		<div class="card">
    	    <h3>Bulk Upload Students (CSV)</h3>
    	    <form method="POST" enctype="multipart/form-data">
				<label> Select CSV file</label>
        	    <input type="file" name="student_file" accept=".csv" required>
        	    <button type="submit" name="bulk_upload">Upload</button>
    	    </form>
		</div>

    	<!-- Student List -->
		<div class="card">
    	    <h3>Student Records</h3>
			<div class="table-wrapper">
				<table>
					<tr>
						<th>ID</th>
            		    <th>Matric No</th>
						<th>Name</th>
						<th>Programme</th>
						<th>Actions</th>
        	        </tr>
        	        <?php while($row = $result->fetch_assoc()) { ?>
        	        <tr>
            		    <td><?php echo htmlspecialchars($row['student_id']); ?></td>
            		    <td><?php echo htmlspecialchars($row['matric_no']); ?></td>
            		    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
            		    <td><?php echo htmlspecialchars($row['programme']); ?></td>
            		    <td>
                		<!-- Edit Form -->
                		    <form method="POST">
                    			<input type="hidden" name="student_id" value="<?php echo $row['student_id']; ?>">
								
								<label>Name</label>
                    			<input type="text" name="student_name" value="<?php echo htmlspecialchars($row['student_name']); ?>" required>
								
								<label>Programme</label>
                    			<input type="text" name="programme" value="<?php echo htmlspecialchars($row['programme']); ?>" required>

                    			<button type="submit" name="update">Update</button>
								<!-- Delete Link -->
								<a href="manage_students.php?delete=<?php echo $row['student_id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this student?');">Delete</a>
                		</form>
            		</td>
        	    </tr>
        	    <?php } ?>
    	    </table>
		</div>

	    <form method="GET" action="manage_students.php" style="margin-top: 20px;">
    		<button type="submit" name="export_csv">Export Records to CSV</button>
	    </form>
	</div>

</div>
</body>
