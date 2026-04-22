<?php
/* CONFIG.PHP - Database Connection
Purpose: Connects PHP to MySQL database
*/

// MySQL connection details
$host = "localhost";
$user ="root";
$password = "root";
$database = "COMP1044_Database";

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}
?>