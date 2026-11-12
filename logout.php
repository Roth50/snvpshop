<?php
require __DIR__ . '/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
}
unset($_SESSION['shop_user']);
flash('success', 'You have been logged out.');
redirect('index.php');
