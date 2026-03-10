<?php
require_once __DIR__ . '/../config.php';

session_destroy();
success('Logged out successfully.');