<?php
/*
STUDENT_RECORDS.php
Purpose: Allow assessors to view and edit student report card
Features:
- Session & role validation (assessor only)
- Dropdown at top to select student
- Each student has only ONE record (update latest if exists)
- Report card includes student info, supervisor name, assessor name
- Editable criteria breakdown with total score
- Download report card as PDF
*/

session_start();
include("includes/config.php");

// Load Dompdf
require __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;

// Ensure only Assessors can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'assessor') {
	header("Location: login.php");
	exit();
}

$assessor_id = $_SESSION['user_id'];
$message = "";

// Fetch students assigned to this assessor
$sql = "SELECT s.student_id, s.student_name, s.matric_no
        FROM internships i
        JOIN students s ON i.student_id = s.student_id
        WHERE i.assessor_id = ?
        ORDER BY s.student_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assessor_id);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();

// Handle student selection
$selected_student = $_POST['student_id'] ?? null;

// Handle save/update
if(isset($_POST['save_record'])) {
    	$student_id = $_POST['student_id'];
    	$comments   = trim($_POST['comments']);

    	// Collect criteria
    	$task_project = $_POST['task_project'];
    	$health_safety = $_POST['health_safety'];
    	$theory_application = $_POST['theory_application'];
    	$report_presentation= $_POST['report_presentation'];
    	$language_clarity = $_POST['language_clarity'];
    	$lifelong_learning = $_POST['lifelong_learning'];
    	$project_management = $_POST['project_management'];
    	$time_management = $_POST['time_management'];

    	$criteria = [$task_project,$health_safety,$theory_application, $report_presentation, $language_clarity,$lifelong_learning,$project_management,$time_management];

    	// Calculate total score = (sum of all criteria / 800) * 100
    	$sum_scores = array_sum($criteria);
    	$total_score =
		    ($task_project * 0.10) +
			($health_safety * 0.10) +
			($theory_application * 0.10) + 
			($report_presentation * 0.15) + 
			($language_clarity * 0.10) +
			($lifelong_learning * 0.15) +
			($project_management * 0.15) +
			($time_management * 0.15);

    	// Find internship_id
    	$stmt = $conn->prepare("SELECT internship_id FROM internships WHERE student_id=? AND assessor_id=? LIMIT 1");
    	$stmt->bind_param("ii", $student_id, $assessor_id);
    	$stmt->execute();
    	$result = $stmt->get_result();
    	$internship = $result->fetch_assoc();
    	$stmt->close();

    	if($internship) {
        	$internship_id = $internship['internship_id'];

        	// Check if record exists
        	$stmt = $conn->prepare("SELECT assessment_id FROM assessments WHERE internship_id=? AND assessor_id=? LIMIT 1");
        	$stmt->bind_param("ii", $internship_id, $assessor_id);
        	$stmt->execute();
        	$existing = $stmt->get_result()->fetch_assoc();
        	$stmt->close();

        	if($existing) {
           		// Update existing record
            		$stmt = $conn->prepare("UPDATE assessments 
                		SET task_project=?, health_safety=?, theory_application=?, report_presentation=?, language_clarity=?, lifelong_learning=?, project_management=?, time_management=?, total_score=?, comments=?, created_at=NOW()
                		WHERE assessment_id=? AND assessor_id=?");
            		$stmt->bind_param("iiiiiiiidsii", $task_project, $health_safety, $theory_application, $report_presentation, $language_clarity, $lifelong_learning, $project_management, $time_management, $total_score, $comments, $existing['assessment_id'], $assessor_id);
            		$stmt->execute();
            		$stmt->close();
            		$message = "Report card updated successfully!";
        	} else {
            		// Insert new record
            		$stmt = $conn->prepare("INSERT INTO assessments 
                		(internship_id, assessor_id, task_project, health_safety, theory_application, report_presentation, language_clarity, lifelong_learning, project_management, time_management, total_score, comments, created_at) 
                		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            		$stmt->bind_param("iiddddddddds", $internship_id, $assessor_id, $task_project, $health_safety, $theory_application, $report_presentation, $language_clarity, $lifelong_learning, $project_management, $time_management, $total_score, $comments);
            		$stmt->execute();
            		$stmt->close();
            		$message = "Report card created successfully!";
        	}
   	} else {
       		$message = "Error: Internship not found for this student.";
    	}
}

// Fetch record + student info for selected student
$student_info = null;
if($selected_student) {
    	$sql2 = "SELECT s.student_name, s.matric_no,
                    i.company_name, i.supervisor_name, i.duration, i.start_date, i.end_date, ass.full_name AS assessor_name, a.task_project, a.health_safety, a.theory_application, a.report_presentation, a.language_clarity, a.lifelong_learning, a.project_management, a.time_management, a.total_score, a.comments, a.created_at
             	FROM internships i
             	JOIN students s ON i.student_id = s.student_id
             	JOIN users ass ON i.assessor_id = ass.user_id
             	LEFT JOIN assessments a ON a.internship_id = i.internship_id AND a.assessor_id = i.assessor_id
             	WHERE s.student_id=? AND i.assessor_id=? LIMIT 1";
    	$stmt2 = $conn->prepare($sql2);
    	$stmt2->bind_param("ii", $selected_student, $assessor_id);
    	$stmt2->execute();
    	$student_info = $stmt2->get_result()->fetch_assoc();
    	$stmt2->close();
}

// Handle PDF download
if (isset($_POST['download_pdf']) && $student_info) {
    	$html = "
    	<h2 style='text-align:center;'>Student Report Card</h2>
    	<h3>Student Information</h3>
    	<p><strong>Name:</strong> {$student_info['student_name']}</p>
    	<p><strong>Matric No:</strong> {$student_info['matric_no']}</p>
    	<p><strong>Company:</strong> {$student_info['company_name']}</p>
    	<p><strong>Supervisor:</strong> {$student_info['supervisor_name']}</p>
    	<p><strong>Duration:</strong> {$student_info['duration']}</p>
    	<p><strong>Start Date:</strong> {$student_info['start_date']}</p>
    	<p><strong>End Date:</strong> {$student_info['end_date']}</p>
    	<p><strong>Assessor:</strong> {$student_info['assessor_name']}</p>
    	<hr>
    	<h3>Assessment Scores</h3>
    	<table border='1' cellpadding='5' cellspacing='0' width='100%'>
        	<tr><th>Criteria</th><th>Score (/100)</th></tr>
        	<tr><td>Task/Project</td><td>{$student_info['task_project']}</td></tr>
        	<tr><td>Health & Safety</td><td>{$student_info['health_safety']}</td></tr>
        	<tr><td>Theory Application</td><td>{$student_info['theory_application']}</td></tr>
        	<tr><td>Report Presentation</td><td>{$student_info['report_presentation']}</td></tr>
        	<tr><td>Language Clarity</td><td>{$student_info['language_clarity']}</td></tr>
        	<tr><td>Lifelong Learning</td><td>{$student_info['lifelong_learning']}</td></tr>
        	<tr><td>Project Management</td><td>{$student_info['project_management']}</td></tr>
        	<tr><td>Time Management</td><td>{$student_info['time_management']}</td></tr>
        	<tr><td>Total Score</td><td>".number_format($student_info['total_score'],2)."%</td></tr>
    	</table>
    	<p><strong>Comments:</strong> {$student_info['comments']}</p>
    	";

    	$dompdf = new Dompdf();
    	$dompdf->loadHtml($html);
    	$dompdf->setPaper('A4', 'portrait');
    	$dompdf->render();
	$filename = $student_info['student_name'] . "_" . $student_info['matric_no'] . "_report_card.pdf";
    	$dompdf->stream($filename, ["Attachment" => true]);
    	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    	<title>Student Report Card</title>
		<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">

	<div class="navbar">
		<a href="assessor_dashhboard.php">Dashboard</a>
		<a href="view_assigned_students.php">Assigned Students</a>
		<a href="manage_assessments.php">Assessments</a>
		<a href="student_records.php">Student Records</a>
		<a href="logout.php">Logout</a>
    </div>

	<div class="hero-card">
		<div class="icon-title">
			<span>📊</span>
		    <h1>Student Report Card</h1>
        </div>
		<p>View and manage assessment report cards for assigned students.</p>
    </div>

    <?php if($message): ?>
		<div class="card">
			<div class="<?php echo (strpos($message,'successfully') !== false) ? 'success' : 'error' ; ?>">
				<?php echo htmlspecialchars($message); ?>
	        </div>
	    </div>
	<?php endif; ?>

    <!-- Student selector -->
	 <div class="card">
		<h3>Select Student</h3>
		<form method="POST">
    	    <label>Select Student:</label>
    	    <select name="student_id" onchange="this.form.submit()">
        	    <option value="">-- Select --</option>
        	    <?php while($s = $students->fetch_assoc()) { ?>
            		<option value="<?php echo $s['student_id']; ?>" 
                		<?php if($selected_student == $s['student_id']) echo "selected"; ?>>
                		<?php echo htmlspecialchars($s['student_name'])." (".$s['matric_no'].")"; ?>
            		</option>
        	    <?php } ?>
    	    </select>
        </form>
	</div>

    <!-- Report Card -->
    <?php if($student_info) { ?>

	    <!-- Student Info -->
		<div class="card">
			<<h3>Student Information</h3>
    	    <p><strong>Name:</strong> <?php echo htmlspecialchars($student_info['student_name']); ?></p>
    	    <p><strong>Matric No:</strong> <?php echo htmlspecialchars($student_info['matric_no']); ?></p>
    	    <p><strong>Company:</strong> <?php echo htmlspecialchars($student_info['company_name']); ?></p>
    	    <p><strong>Supervisor:</strong> <?php echo htmlspecialchars($student_info['supervisor_name']); ?></p>
    	    <p><strong>Duration:</strong> <?php echo htmlspecialchars($student_info['duration']); ?> months</p>
    	    <p><strong>Start Date:</strong> <?php echo htmlspecialchars($student_info['start_date']); ?></p>
    	    <p><strong>End Date:</strong> <?php echo htmlspecialchars($student_info['end_date']); ?></p>
    	    <p><strong>Assessor:</strong> <?php echo htmlspecialchars($student_info['assessor_name']); ?></p>
	    </div>

		<!-- Report Card-->
		<div class="card">
    	    <h3>Assessment Report Card</h3>
    	    
			<form method="POST">
        	    <input type="hidden" name="student_id" value="<?php echo $selected_student; ?>">

				<div class="table-wrapper">
					<table>
            		    <tr>
							<th>Criteria</th>
							<th>Score (/100)</th>
						</tr>
            		    
						<tr>
							<td>Task/Project</td>
                		    <td><input type="number" name="task_project" value="<?php echo $student_info['task_project'] ?? ''; ?>" min="0" max="100"> / 100</td>
						</tr>
            		    <tr>
							<td>Health & Safety</td>
                		    <td><input type="number" name="health_safety" value="<?php echo $student_info['health_safety'] ?? ''; ?>" min="0" max="100"> / 100</td>
						</tr>
            		 
						<tr>
							<td>Theory Application</td>
                		    <td><input type="number" name="theory_application" value="<?php echo $student_info['theory_application'] ?? ''; ?>" min="0" max="100"> / 100</td>
						</tr>
            		
						<tr>
							<td>Report Presentation</td>
                		    <td><input type="number" name="report_presentation" value="<?php echo $student_info['report_presentation'] ?? ''; ?>" min="0" max="100"> / 100</td>
						</tr>
            		
						<tr>
							<td>Language Clarity</td>
                		    <td><input type="number" name="language_clarity" value="<?php echo $student_info['language_clarity'] ?? ''; ?>" min="0" max="100"> / 100</td>
						</tr>
            		
						<tr>
							<td>Lifelong Learning</td>
                		    <td><input type="number" name="lifelong_learning" value="<?php echo $student_info['lifelong_learning'] ?? ''; ?>" min="0" max="100"> / 100</td>
						</tr>
            		
						<tr>
							<td>Project Management</td>
               			    <td><input type="number" name="project_management" value="<?php echo $student_info['project_management'] ?? ''; ?>" min="0" max="100"> / 100</td>
						</tr>
            		
						<tr>
							<td>Time Management</td>
                		    <td><input type="number" name="time_management" value="<?php echo $student_info['time_management'] ?? ''; ?>" min="0" max="100"> / 100</td>
						</tr>
            		
						<tr>
							<td>Total Score</td>
                		    <td>
								<?php echo isset($student_info['total_score']) ? number_format($student_info['total_score'],2)."%" : "N/A"; ?>
							</td>
						</tr>
            		
						<tr>
							<td>Comments</td>
                			<td>
								<textarea name="comments"><?php echo $student_info['comments'] ?? ''; ?></textarea>
							</td>
						</tr>
        	        </table>
	            </div>

				<div style="margin-top:15px;">
        	        <button type="submit" name="save_record">Save Report Card</button>
        	        <button type="submit" name="download_pdf">Download PDF</button>
	            </div>
    	    </form>
	    </div>
    <?php } ?>
	
</div>
</body>
</html>
