<?php
require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../trainer-config.php';
require_method('POST');
session_destroy();
success('Logged out successfully.');
