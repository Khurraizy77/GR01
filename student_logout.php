<?php
session_start();
unset($_SESSION['is_student'], $_SESSION['student_matric'], $_SESSION['student_name']);
header("Location: student_login.php");
exit;
?>
