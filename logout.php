<?php
require_once __DIR__ . '/includes/auth.php';
ludoteca_start_session();
$_SESSION = [];
session_destroy();
header('Location: login.php');
