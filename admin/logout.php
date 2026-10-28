<?php
require_once __DIR__ . '/../includes/functions.php';
start_session();
$_SESSION = [];
session_destroy();
redirect('login.php');
