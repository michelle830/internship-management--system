<?php
/*
MANAGE_ASSESSMENTS.php
Purpose: Assessors record evaluations for students
Features:
- Session & role validation (assessor only)
- Submit new assessment (criteria + comments)
- Total score = (sum of all criteria / 800) * 100
- Display submitted assessments with breakdown
*/

session_start();
include("includes/config.php");

// Ensure only Assessors can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'assessor') {
	header("Location: login.php");
	exit();
}

$message = "";

// Handle Assessment Submission
if(isset($_POST['submit_assessment'])) {
	$student_id   = $_POST['student_id'];
    	$assessor_id  = $_SESSION['user_id'];

    	// Collect all criteria (raw scores 0–100)
    	$task_project       = $_POST['task_project'];
    	$health_safety      = $_POST['health_safety'];
    	$theory_application = $_POST['theory_application'];
    	$report_presentation= $_POST['report_presentation'];
    	$language_clarity   = $_POST['language_clarity'];
    	$lifelong_learning  = $_POST['lifelong_learning'];
    	$project_management = $_POST['project_management'];
    	$time_management    = $_POST['time_management'];
    	$comments           = trim($_POST['comments']);

    	// Validate scores
    	$criteria = [$task_project,$health_safety,$theory_application,$report_presentation,
                 	$language_clarity,$lifelong_learning,$project_management,$time_management];
    	$valid = true;
   	foreach($criteria as $c){
        	if(!is_numeric($c) || $c < 0 || $c > 100){
            		$valid = false;
            		break;
        	}
    	}

    	if(!$valid){
        	$message = "Error: Each score must be between 0 and 100.";
    	} else {
        	// Calculate total score as percentage
        	$sum_scores = array_sum($criteria); // sum of all 8 criteria
       		$total_score = 
			    ($task_project * 0.10) +
				($health_safety * 0.10) +
				($theory_application * 0.10) +
				($report_presentation * 0.15) +
				($language_clarity * 0.10) +
				($lifelong_learning * 0.15) +
				($project_management * 0.15) +
				($time_management * 0.15);

        	$stmt = $conn->prepare("SELECT internship_id FROM internships WHERE student_id = ? AND assessor_id = ? LIMIT 1");
        	$stmt->bind_param("ii", $student_id, $assessor_id);
        	$stmt->execute();
        	$result = $stmt->get_result();
        	$internship = $result->fetch_assoc();
        	$stmt->close();

        	if($internship) {
            		$internship_id = $internship['internship_id'];
            		$stmt = $conn->prepare("INSERT INTO assessments 
                		(internship_id, assessor_id, task_project, health_safety, theory_application, report_presentation, language_clarity, lifelong_learning, project_management, time_management, total_score, comments, created_at) 
                		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            		$stmt->bind_param("iiddddddddds", $internship_id, $assessor_id,
                	$task_project, $health_safety, $theory_application, $report_presentation, $language_clarity, $lifelong_learning, $project_management, $time_management, $total_score, $comments);

            		if($stmt->execute()) {
                		$message = "Assessment submitted successfully!";
            		} else {
                		$message = "Error: Could not submit assessment.";
            		}
            		$stmt->close();
        	} else {
            		$message = "Error: Internship not found for this student.";
        	}
    	}
}

// Fetch students assigned to this assessor
$sql = "SELECT i.internship_id, s.student_id, s.student_name, s.matric_no
	FROM internships i
        JOIN students s ON i.student_id = s.student_id
        WHERE i.assessor_id = ?
        ORDER BY s.student_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();

// Fetch submitted assessments
$sql2 = "SELECT a.assessment_id, s.student_name, s.matric_no,
                a.task_project, a.health_safety, a.theory_application,
                a.report_presentation, a.language_clarity, a.lifelong_learning,
                a.project_management, a.time_management,
                a.total_score, a.comments, a.created_at
        FROM assessments a
        JOIN internships i ON a.internship_id = i.internship_id
        JOIN students s ON i.student_id = s.student_id
        WHERE a.assessor_id = ?
        ORDER BY a.created_at DESC";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $_SESSION['user_id']);
$stmt2->execute();
$assessments = $stmt2->get_result();
$stmt2->close();
?>

<!DOCTYPE html>
<html>
<head>
    	<title>Manage Assessments</title>
		<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">

	<div class="navbar">
		<a href="assessor_dashboard.php">Dashboard</a>
		<a href="view_assigned_students.php">Assigned Students</a>
		<a href="manage_assessments.php">Assessments</a>
		<a href="student_records.php">Student Records</a>
		<a href="logout.php">Logout</a>
    </div>

	<div class="hero-card">
		<div class="icon-title">
			<span>📝</span>
    	    <h1>Manage Assessments</h1>
        </div>
    	<p> Submit and review internship assessments for your assigned students.</p>
    </div>   

    <!-- Feedback message -->
    <?php if($message != ""): ?>
		<div class="card">
			<div class="<?php echo(strpos($message, 'successfully') !== false) ? 'success' : 'error'; ?>">
				<?php echo htmlspecialchard($message); ?>
	        </div>
	    </div>
	<?php endif; ?>

    <!-- Assessment Form -->
	<div class="card">
    	<h3>Submit New Assessment</h3>
    	<form method="POST">
			<label>Student</label>
        	<select name="student_id" required>
            	<option value="">-- Select Student --</option>
            	<?php while($s = $students->fetch_assoc()) { ?>
                	<option value="<?php echo $s['student_id']; ?>">
                    	<?php echo htmlspecialchars($s['student_name'])." (".$s['matric_no'].")"; ?>
                	</option>
            	<?php } ?>
        	</select>

			<label>Task / Project</label>
		    <input type="number" name="task_project" min="0" max="100" required> 

			<label>Health & Safety</label>
        	<input type="number" name="health_safety" min="0" max="100" required> 

        	<label>Theory Application</label>
			<input type="number" name="theory_application" min="0" max="100" required> 

        	<label>Report Presentation</label>
			<input type="number" name="report_presentation" min="0" max="100" required> 

        	<label>Language Clarity</label>
			<input type="number" name="language_clarity" min="0" max="100" required> 

        	<label>Lifelong Learning</label>
			<input type="number" name="lifelong_learning" min="0" max="100" required> 

        	<label>Project Management</label>
			<input type="number" name="project_management" min="0" max="100" required> 

        	<label>Time Management</label>
			<input type="number" name="time_management" min="0" max="100" required> 

        	<label>Comments</label>
        	<textarea name="comments" rows="4"></textarea>

        	<button type="submit" name="submit_assessment">Submit Assessment</button>
    	</form>
	</div>

    <!-- Assessment Records -->
	<div class="card">
    	<h3>Submitted Assessments</h3>
		<div class="table-wrapper">
			<table>
        	    <tr>
            		<th>ID</th>
					<th>Student</th>
					<th>Matric No</th>
            		<th>Task</th>
					<th>Safety</th>
					<th>Theory</th>
					<th>Report</th>
            		<th>Language</th>
					<th>Lifelong</th>
					<th>Project Mgmt</th>
					<th>Time Mgmt</th>
            		<th>Total (%)</th>
					<th>Comments</th>
					<th>Date</th>
        	</tr>
        	<?php while($row = $assessments->fetch_assoc()) { ?>
        	<tr>
            		<td><?php echo htmlspecialchars($row['assessment_id']); ?></td>
            		<td><?php echo htmlspecialchars($row['student_name']); ?></td>
            		<td><?php echo htmlspecialchars($row['matric_no']); ?></td>
            		<td><?php echo htmlspecialchars($row['task_project']); ?></td>
            		<td><?php echo htmlspecialchars($row['health_safety']); ?></td>
            		<td><?php echo htmlspecialchars($row['theory_application']); ?></td>
            		<td><?php echo htmlspecialchars($row['report_presentation']); ?></td>
           		    <td><?php echo htmlspecialchars($row['language_clarity']); ?></td>
            		<td><?php echo htmlspecialchars($row['lifelong_learning']); ?></td>
            		<td><?php echo htmlspecialchars($row['project_management']); ?></td>
            		<td><?php echo htmlspecialchars($row['time_management']); ?></td>
            		<td><?php echo number_format($row['total_score'], 2); ?>%</td>
            		<td><?php echo htmlspecialchars($row['comments']); ?></td>
            		<td><?php echo htmlspecialchars($row['created_at']); ?></td>
       		    </tr>
        	    <?php } ?>
    	    </table>
	    </div>
	</div>

</div>
</body>

