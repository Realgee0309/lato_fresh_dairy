<?php
require_once 'config.php';
require_login();

log_audit('logout', "User {$_SESSION['username']} logged out");

session_unset();
session_destroy();
setcookie('remember_user', '', time() - 3600, '/');

redirect('login.php');
?>