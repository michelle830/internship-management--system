<?php
/*
SEARCH_STUDENTS.php
Purpose: Handle live search requests from assessor dashboard
Features:
- Session & role validation (assessor only)
- Accepts query string via AJAX (GET parameter 'q')
- If query provided: filter students by name or matric no
- If query empty: return first 5 assigned students
- Displays table with No, Name, Matric No, and "View Report Card" button
- Shows error message "Student not found" if no matches
- Provides link to view_assigned_students.php for full list
*/

session_start();
include("includes/config.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'assessor') {
    	exit("Unauthorized");
}

$user_id = $_SESSION['user_id'];
$search = $_GET['q'] ?? '';

if ($search) {
    	$sql = "SELECT s.student_id, s.student_name, s.matric_no
		FROM internships i
            	JOIN students s ON i.student_id = s.student_id
            	WHERE i.assessor_id = ?
              	AND (s.student_name LIKE ? OR s.matric_no LIKE ?)
            	ORDER BY s.student_name ASC LIMIT 5";
    	$stmt = $conn->prepare($sql);
    	$like = "%".$search."%";
    	$stmt->bind_param("iss", $user_id, $like, $like);
} else {
    	// Default: show first 5 students
    	$sql = "SELECT s.student_id, s.student_name, s.matric_no
            	FROM internships i
            	JOIN students s ON i.student_id = s.student_id
            	WHERE i.assessor_id = ?
            	ORDER BY s.student_name ASC LIMIT 5";
    	$stmt = $conn->prepare($sql);
    	$stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    	$no = 1;
    	echo "<table border='1' cellpadding='5'>
            	<tr><th>No</th><th>Name</th><th>Matric No</th><th>Action</th></tr>";
    	while($s = $result->fetch_assoc()) {
        	echo "
		<tr>
                	<td>".$no++."</td>
                	<td>".htmlspecialchars($s['student_name'])."</td>
                	<td>".htmlspecialchars($s['matric_no'])."</td>
                	<td>
                    		<form action='student_records.php' method='POST' style='display:inline;'>
                        		<input type='hidden' name='student_id' value='".$s['student_id']."'>
                        		<button type='submit'>View Report Card</button>
                    		</form>
                	</td>
              	</tr>";
    	}
    	echo "</table><p><a href='view_assigned_students.php'>View All</a></p>";
} else {
    	echo "<p style='color:red;'>Student not found</p>";
}
$stmt->close();