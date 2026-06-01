<?php
require_once 'components/connector.php';
check_csrf();
session_start();
session_destroy();
header("Location: login.php");
exit();
?>
