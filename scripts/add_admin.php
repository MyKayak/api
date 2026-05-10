<?php

require_once __DIR__ . '/../src/utils/auth.php';

if ($argc < 3) {
    echo "Usage: php add_admin.php <username> <password>\n";
    exit(1);
}

$username = $argv[1];
$password = $argv[2];

if (registerAdmin($username, $password)) {
    echo "Admin '$username' created successfully.\n";
} else {
    echo "Failed to create admin. Username might already exist.\n";
}
