<?php
/*
MANAGE_INTERNSHIPS.php
Purpose: Admin CRUD operations for internship records
Features:
- Session & role validation
- Add new internship (manual form with duplicate check)
- Bulk upload internships via CSV (skip header, confirm duplicates)
- Confirm bulk duplicates (optional override)
- Edit existing internship
- Delete internship
- Display all internships
- Flash messages (session-based, one-time display)
- Export internships to CSV (download records with headers)
*/
session_start();
include("includes/config.php");

// Ensure only Admins can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    	header("Location: login.php");
    	exit();
}

$message = "";

// Helper function to validate duration
function validateDuration($start_date, $end_date, $duration) {
    	$start = new DateTime($start_date);
    	$end = new DateTime($end_date);
    	$expected_end = clone $start;
    	$expected_end->modify("+$duration months");
    	return $end->format("Y-m-d") == $expected_end->format("Y-m-d");
}

if(isset($_POST['add'])) {
    	$student_id = $_POST['student_id'];
    	$assessor_id = $_POST['assessor_id'];
    	$company_name = trim($_POST['company_name']);
    	$supervisor_name = trim($_POST['supervisor_name']);
    	$duration = trim($_POST['duration']);
    	$start_date = $_POST['start_date'];
    	$end_date = $_POST['end_date'];

    	// Duplicate check
    	$check = $conn->prepare("SELECT internship_id 
                             	FROM internships 
                             	WHERE student_id=? AND assessor_id=? 
                               	AND company_name=? AND start_date=?");
    	$check->bind_param("iiss", $student_id, $assessor_id, $company_name, $start_date);
    	$check->execute();
    	$check->store_result();

    	if($check->num_rows > 0) {
        	$_SESSION['flash_message'] = "Error: Duplicate internship already exists.";
        	$check->close();
    	} elseif(!in_array($duration, ["3","6","9","12"])) {
        	$_SESSION['flash_message'] = "Error: Duration must be 3, 6, 9, or 12 months.";
        	$check->close();
    	} elseif(!validateDuration($start_date, $end_date, $duration)) {
        	$_SESSION['flash_message'] = "Error: End date must be exactly $duration months after start date.";
        	$check->close();
    	} else {
        	$check->close();
        	$stmt = $conn->prepare("INSERT INTO internships (student_id, assessor_id, company_name, supervisor_name, duration, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        	$stmt->bind_param("iisssss", $student_id, $assessor_id, $company_name, $supervisor_name, $duration, $start_date, $end_date);
        	$_SESSION['flash_message'] = $stmt->execute() ? "Internship added successfully!" : "Error: Could not add internship.";
        	$stmt->close();
    	}

    	// Redirect so flash message shows once and clears
    	header("Location: manage_internships.php");
    	exit();
}

// -------------------- BULK UPLOAD INTERNSHIPS --------------------
if(isset($_POST['bulk_upload'])) {
    	if($_FILES['internship_file']['error'] == 0) {
        	$file = fopen($_FILES['internship_file']['tmp_name'], "r");
        	$rowCount = 0;
        	$duplicates = [];
        	$isHeader = true;

        	while(($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            		if($isHeader) { $isHeader = false; continue; }

            		// CSV format: student_name, assessor_name, company_name, supervisor_name, duration, start_date, end_date
            		$student_name = trim($data[0]);
            		$assessor_name = trim($data[1]);
            		$company_name = trim($data[2]);
            		$supervisor_name = trim($data[3]);
            		$duration = trim($data[4]);
            		$start_date = trim($data[5]);
            		$end_date = trim($data[6]);

            		if($student_name==""||$assessor_name==""||$company_name==""||$duration==""||$start_date==""||$end_date=="") continue;

            		// Lookup student_id
            		$student_lookup = $conn->prepare("SELECT student_id FROM students WHERE student_name=?");
            		$student_lookup->bind_param("s", $student_name);
            		$student_lookup->execute();
            		$student_result = $student_lookup->get_result();
            		if($row = $student_result->fetch_assoc()) {
                		$student_id = $row['student_id'];
            		} else { continue; }
            		$student_lookup->close();

            		// Lookup assessor_id
            		$assessor_lookup = $conn->prepare("SELECT user_id FROM users WHERE full_name=? AND role='assessor'");
            		$assessor_lookup->bind_param("s", $assessor_name);
            		$assessor_lookup->execute();
            		$assessor_result = $assessor_lookup->get_result();
            		if($row = $assessor_result->fetch_assoc()) {
                		$assessor_id = $row['user_id'];
            		} else { continue; }
            		$assessor_lookup->close();

            		// Duplicate check
            		$check = $conn->prepare("SELECT internship_id FROM internships WHERE student_id=? AND assessor_id=? AND company_name=? AND start_date=?");
            		$check->bind_param("iiss", $student_id, $assessor_id, $company_name, $start_date);
            		$check->execute();
            		$check->store_result();

            		if($check->num_rows > 0) {
                		$duplicates[] = compact('student_name','assessor_name','company_name','supervisor_name','duration','start_date','end_date');
                		$check->close();
                		continue;
            		}
            		$check->close();

            		if(!in_array($duration, ["3","6","9","12"])) continue;
            		if(!validateDuration($start_date, $end_date, $duration)) continue;

            		$stmt = $conn->prepare("INSERT INTO internships (student_id, assessor_id, company_name, supervisor_name, duration, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            		$stmt->bind_param("iisssss", $student_id, $assessor_id, $company_name, $supervisor_name, $duration, $start_date, $end_date);
            		if($stmt->execute()) $rowCount++;
            		$stmt->close();
        	}
        	fclose($file);

        	if(count($duplicates) > 0) {
            		$_SESSION['pending_bulk_internships'] = $duplicates;
            		$_SESSION['flash_message'] = "$rowCount internships uploaded. Found ".count($duplicates)." duplicates. Do you want to add them anyway?";
            		header("Location: manage_internships.php?confirm_bulk=1");
            		exit();
        	} else {
            		$_SESSION['flash_message'] = "$rowCount internships uploaded successfully!";
            		header("Location: manage_internships.php");
            		exit();
        	}
    	} else {
        	$_SESSION['flash_message'] = "Error: Could not upload file.";
        	header("Location: manage_internships.php");
        	exit();
    	}
}

// -------------------- CONFIRM BULK UPLOAD --------------------
if(isset($_POST['confirm_bulk_add']) && isset($_SESSION['pending_bulk_internships'])) {
    	$addedCount = 0;
    	foreach($_SESSION['pending_bulk_internships'] as $dup) {
        	$student_name = $dup['student_name'];
        	$assessor_name = $dup['assessor_name'];
        	$company_name = $dup['company_name'];
        	$supervisor_name = $dup['supervisor_name'];
        	$duration = $dup['duration'];
        	$start_date = $dup['start_date'];
        	$end_date = $dup['end_date'];

        	// Lookup student_id
        	$student_lookup = $conn->prepare("SELECT student_id FROM students WHERE student_name=?");
        	$student_lookup->bind_param("s", $student_name);
        	$student_lookup->execute();
        	$student_result = $student_lookup->get_result();
        	if($row = $student_result->fetch_assoc()) {
            		$student_id = $row['student_id'];
        	} else { continue; }
        	$student_lookup->close();

        	// Lookup assessor_id
        	$assessor_lookup = $conn->prepare("SELECT user_id FROM users WHERE full_name=? AND role='assessor'");
        	$assessor_lookup->bind_param("s", $assessor_name);
        	$assessor_lookup->execute();
        	$assessor_result = $assessor_lookup->get_result();
        	if($row = $assessor_result->fetch_assoc()) {
            		$assessor_id = $row['user_id'];
        	} else { continue; }
        	$assessor_lookup->close();

        	if(!in_array($duration, ["3","6","9","12"])) continue;
        	if(!validateDuration($start_date, $end_date, $duration)) continue;

        	$stmt = $conn->prepare("INSERT INTO internships (student_id, assessor_id, company_name, supervisor_name, duration, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        	$stmt->bind_param("iisssss", $student_id, $assessor_id, $company_name, $supervisor_name, $duration, $start_date, $end_date);
        	if($stmt->execute()) $addedCount++;
        	$stmt->close();
    	}
    	unset($_SESSION['pending_bulk_internships']);
    	$_SESSION['flash_message'] = "$addedCount duplicate internships added successfully!";
    	header("Location: manage_internships.php");
    	exit();
}


// -------------------- UPDATE INTERNSHIP --------------------
if(isset($_POST['update'])) {
    	$internship_id = $_POST['internship_id'];
    	$company_name = trim($_POST['company_name']);
    	$supervisor_name = trim($_POST['supervisor_name']);
    	$duration = trim($_POST['duration']);
    	$start_date = $_POST['start_date'];
    	$end_date = $_POST['end_date'];

    	if(!in_array($duration, ["3","6","9","12"])) {
        	$_SESSION['flash_message'] = "Error: Duration must be 3, 6, 9, or 12 months.";
    	} elseif(!validateDuration($start_date, $end_date, $duration)) {
        	$_SESSION['flash_message'] = "Error: End date must be exactly $duration months after start date.";
    	} else {
        	$stmt = $conn->prepare("UPDATE internships 
                                	SET company_name=?, supervisor_name=?, duration=?, start_date=?, end_date=? 
                                	WHERE internship_id=?");
        	$stmt->bind_param("sssssi", $company_name, $supervisor_name, $duration, $start_date, $end_date, $internship_id);
        	$_SESSION['flash_message'] = $stmt->execute() ? "Internship updated successfully!" : "Error: Could not update internship.";
        	$stmt->close();
    	}
    	header("Location: manage_internships.php");
    	exit();
}

// -------------------- DELETE INTERNSHIP --------------------
if(isset($_GET['delete'])) {
    	$internship_id = $_GET['delete'];
    	if(is_numeric($internship_id)) {
        	$stmt = $conn->prepare("DELETE FROM internships WHERE internship_id=?");
        	$stmt->bind_param("i", $internship_id);
        	if($stmt->execute()) {
            		$_SESSION['flash_message'] = "Internship deleted successfully!";
        	} else {
            		$_SESSION['flash_message'] = "Error: Could not delete internship.";
        	}
        	$stmt->close();
    	} else {
        	$_SESSION['flash_message'] = "Invalid internship ID.";
    	}
    	// Redirect so flash message shows once
    	header("Location: manage_internships.php");
    	exit();
}

// -------------------- EXPORT INTERNSHIPS TO CSV --------------------
if(isset($_GET['export_csv'])) {
    	header('Content-Type: text/csv; charset=utf-8');
    	header('Content-Disposition: attachment; filename=internships.csv');

    	$output = fopen('php://output', 'w');

    	// Write header row
    	fputcsv($output, ['Internship ID','Matric No','Student Name','Assessor Name','Company','Supervisor','Duration','Start Date','End Date']);

    	// Fetch all internships
    	$sql = "SELECT i.internship_id, s.matric_no, s.student_name, 
                   u.full_name AS assessor_name, i.company_name, 
                   i.supervisor_name, i.duration, i.start_date, i.end_date
            	FROM internships i
            	JOIN students s ON i.student_id = s.student_id
            	JOIN users u ON i.assessor_id = u.user_id
            	ORDER BY i.internship_id ASC";
    	$result = $conn->query($sql);

    	while($row = $result->fetch_assoc()) {
        	fputcsv($output, $row);
    	}

    	fclose($output);
    	exit();
}

// -------------------- FETCH ALL INTERNSHIPS --------------------
$sql = "SELECT i.internship_id, s.matric_no, s.student_name, 
               u.full_name AS assessor_name, i.company_name, 
               i.supervisor_name, i.duration, i.start_date, i.end_date
        FROM internships i
        JOIN students s ON i.student_id = s.student_id
        JOIN users u ON i.assessor_id = u.user_id
        ORDER BY i.internship_id ASC";
$result = $conn->query($sql);

$students = $conn->query("SELECT student_id, matric_no, student_name 
                          FROM students ORDER BY student_name ASC");
$assessors = $conn->query("SELECT user_id, full_name 
                           FROM users WHERE role='assessor' 
                           ORDER BY full_name ASC");
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

    	<!-- Show flash message once -->
    	<?php if(isset($_SESSION['flash_message'])): ?>
        	<p><?php echo htmlspecialchars($_SESSION['flash_message']); ?></p>
        	<?php unset($_SESSION['flash_message']); ?>
    	<?php endif; ?>

   	<!-- Confirmation for bulk upload -->
    	<?php if(isset($_GET['confirm_bulk']) && isset($_SESSION['pending_bulk_internships'])): ?>
        	<form method="POST">
            		<button type="submit" name="confirm_bulk_add">Yes, Add Duplicates</button>
            		<a href="manage_internships.php">Cancel</a>
        	</form>
        	<ul>
            		<?php foreach($_SESSION['pending_bulk_internships'] as $dup): ?>
                		<li>
                    			Student: <?php echo htmlspecialchars($dup['student_name']); ?>,
                    			Assessor: <?php echo htmlspecialchars($dup['assessor_name']); ?>,
                    			Company: <?php echo htmlspecialchars($dup['company_name']); ?>,
                    			Supervisor: <?php echo htmlspecialchars($dup['supervisor_name']); ?>,
                    			Duration: <?php echo htmlspecialchars($dup['duration']); ?> months,
                    			Start Date: <?php echo htmlspecialchars($dup['start_date']); ?>,
                    			End Date: <?php echo htmlspecialchars($dup['end_date']); ?>
               	 		</li>
            		<?php endforeach; ?>
        	</ul>
        	<hr>
    	<?php endif; ?>

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
        	Supervisor: <input type="text" name="supervisor_name"><br><br>
        	Duration:
        	<select name="duration" required>
           		<option value="">-- Select Duration --</option>
            		<option value="3">3 Months</option>
            		<option value="6">6 Months</option>
            		<option value="9">9 Months</option>
            		<option value="12">12 Months</option>
        	</select><br><br>
        	Start Date: <input type="date" name="start_date" required><br><br>
        	End Date: <input type="date" name="end_date" required><br><br>
        	<button type="submit" name="add">Add Internship</button>
    	</form>
    	<hr>

    	<!-- Bulk Upload Form -->
    	<h3>Bulk Upload Internships (CSV)</h3>
    	<form method="POST" enctype="multipart/form-data">
        	<input type="file" name="internship_file" accept=".csv" required>
        	<button type="submit" name="bulk_upload">Upload</button>
    	</form>
    	<hr>

    	<!-- Internship Records -->
    	<h3>Internship Records</h3>
    	<table border="1" cellpadding="5">
        	<tr>
            		<th>ID</th><th>Student</th><th>Assessor</th><th>Company</th>
            		<th>Supervisor</th><th>Duration</th><th>Start Date</th><th>End Date</th><th>Actions</th>
        	</tr>
        	<?php while($row = $result->fetch_assoc()) { ?>
        	<tr>
            		<td><?php echo htmlspecialchars($row['internship_id']); ?></td>
            		<td><?php echo htmlspecialchars($row['student_name'])." (".$row['matric_no'].")"; ?></td>
            		<td><?php echo htmlspecialchars($row['assessor_name']); ?></td>
            		<td><?php echo htmlspecialchars($row['company_name']); ?></td>
            		<td><?php echo htmlspecialchars($row['supervisor_name']); ?></td>
            		<td><?php echo htmlspecialchars($row['duration']); ?></td>
            		<td><?php echo htmlspecialchars($row['start_date']); ?></td>
            		<td><?php echo htmlspecialchars($row['end_date']); ?></td>
            		<td>
                		<!-- Edit Form -->
                		<form method="POST" style="display:inline;">
                    			<input type="hidden" name="internship_id" value="<?php echo $row['internship_id']; ?>">
                    Company: <input type="text" name="company_name" value="<?php echo htmlspecialchars($row['company_name']); ?>">
                    Supervisor: <input type="text" name="supervisor_name" value="<?php echo htmlspecialchars($row['supervisor_name']); ?>">
                    Duration:
                    			<select name="duration" required>
                        			<option value="3" <?php if($row['duration']=="3") echo "selected"; ?>>3 Months</option>
                        			<option value="6" <?php if($row['duration']=="6") echo "selected"; ?>>6 Months</option>
                        			<option value="9" <?php if($row['duration']=="9") echo "selected"; ?>>9 Months</option>
                        			<option value="12" <?php if($row['duration']=="12") echo "selected"; ?>>12 Months</option>
                    			</select><br><br>
                    			Start Date: <input type="date" name="start_date" value="<?php echo htmlspecialchars($row['start_date']); ?>">
                    			End Date: <input type="date" name="end_date" value="<?php echo htmlspecialchars($row['end_date']); ?>">
                    			<button type="submit" name="update">Update</button>
                		</form>
                		<!-- Delete Link -->
                		<a href="manage_internships.php?delete=<?php echo $row['internship_id']; ?>" onclick="return confirm('Delete this internship?');">Delete</a>
            		</td>
        	</tr>
        	<?php } ?>
    	</table>
	<!-- Export Records -->
	<form method="GET" action="manage_internships.php">
    		<button type="submit" name="export_csv">Export Records to CSV</button>
	</form>
	<hr>
</body>
</html>
