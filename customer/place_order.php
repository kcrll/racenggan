<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['customer']);
flash('info', 'Orders are recorded by the RACEL manager. Your account is for viewing and tracking your orders.');
redirect('customer/dashboard.php');
