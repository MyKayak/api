<?php

function registerAdmin($username, $password){
    require_once "connect.php";
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username=:username");
    $stmt->execute(["username" => $username]);

    if($stmt->rowCount() > 0){
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (:username, :password)");
    $stmt->execute(["username" => $username, "password" => password_hash($password, PASSWORD_DEFAULT)]);

    return true;
}

function verifyAdminCredentials($username, $password){
    require_once "connect.php";
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username=:username");
    $stmt->execute(["username" => $username]);
    if($stmt->rowCount() > 0){
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if(password_verify($password, $admin['password'])){
            return create_token($admin['admin_id']);
        }
    }
    return false;
}

function create_token($admin_id) {
    try {
        $token = bin2hex(random_bytes(32));
    } catch (\Random\RandomException $e) {
        return false;
    }

    $hashed_token = hash("sha256", $token);
    $expiration_date = date('Y-m-d', strtotime('+30 days'));
    require "connect.php";

    $stmt = $conn->prepare("INSERT INTO tokens (admin_id, token, expiration_date) VALUES (:admin_id, :token, :expiration_date)");
    if ($stmt->execute([
        "admin_id" => $admin_id,
        "token" => $hashed_token,
        "expiration_date" => $expiration_date
    ])) {
        return $token;
    }

    return false;
}

function loginAdmin($token) {
    require_once "connect.php";
    $stmt = $conn->prepare("SELECT * FROM tokens WHERE token=:token");
    $stmt->execute(["token" => hash("sha256", $token)]);

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if($data && $data["expiration_date"] > date("Y-m-d")){
        return $data["admin_id"];
    }

    return false;
}

function requireAdmin() {
    $token = null;
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
    } elseif (isset($_COOKIE['token'])) {
        $token = $_COOKIE['token'];
    }

    if (!$token || !loginAdmin($token)) {
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }
}
