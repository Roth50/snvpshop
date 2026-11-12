<?php
require __DIR__ . '/../functions.php';
require_post();
unset($_SESSION['shop_admin']);
session_regenerate_id(true);
redirect('../index.php');
