<?php
require_once 'includes/session_helper.php';
require_once 'config/config.php';

// Clear session data
session_destroy();
redirect('login.php');
?>
