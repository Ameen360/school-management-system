<?php
ob_start();
session_start();
if (!isset($_SESSION['uid'])) {
    header('Location: ../index.php');
    exit();
}
?>