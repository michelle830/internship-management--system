<?php
/* INDEX PAGE - Internship Result Management System
Purpose: Landing page for the system
Redirects automatically to login.php
*/

echo "Welcome to Internship Result Management System!<br>";

header("Refresh:3; url=login.php");	// waits 3 seconds, then redirect
exit();