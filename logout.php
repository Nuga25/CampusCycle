<?php
session_start();
session_destroy();
header("Location: /CampusCycle/index.php");
exit();
?>