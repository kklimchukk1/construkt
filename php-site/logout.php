<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

logoutUser();
header('Location: /');
exit;
