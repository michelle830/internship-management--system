<?php
include("includes/config.php");

// Test database connection
if($conn) {
	echo "Database connection works!";
} else {
	echo "Database connection failed!";
}
?>