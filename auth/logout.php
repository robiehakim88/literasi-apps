<?php
require_once '../config/database.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
redirect('../auth/login.php');
?>