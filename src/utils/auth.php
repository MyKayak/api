<?php

function verifyApiKey($apiKey){
    require_once "connect.php";
    $stmt = $conn->prepare("SELECT * FROM api_keys WHERE api_key=:api_key AND is_active=TRUE");
    $stmt->execute(["api_key" => hash("sha256", $apiKey)]);

    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

function verifyAdminApiKey($apiKey){
    require_once "connect.php";
    $stmt = $conn->prepare("SELECT * FROM admin_api_keys WHERE api_key=:api_key AND is_active=TRUE");
    $stmt->execute(["api_key" => hash("sha256", $apiKey)]);

    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

function registerUser($username, $email, $password){
    require_once "connect.php";
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=:email");
    $stmt->execute(["email" => $email]);

    if($stmt->rowCount() > 0){
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
    $stmt->execute(["username" => $username, "email" => $email, "password" => password_hash($password, PASSWORD_DEFAULT)]);

    return true;
}

function verifyUserCredentials($email, $password){
    require_once "connect.php";
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=:email");
    $stmt->execute(["email" => $email]);
    if($stmt->rowCount() > 0){
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if(password_verify($password, $user['password'])){
            return create_token($user['user_id']);
        }
    }
    return false;
}

function create_token($user_id) {
    try {
        $token = bin2hex(random_bytes(32));
    } catch (\Random\RandomException $e) {
        return false;
    }

    $hashed_token = hash("sha256", $token);
    $expiration_date = date('Y-m-d', strtotime('+30 days'));
    require "connect.php";

    $stmt = $conn->prepare("INSERT INTO tokens (user_id, token, expiration_date) VALUES (:user_id, :token, :expiration_date)");
    if ($stmt->execute([
        "user_id" => $user_id,
        "token" => $hashed_token,
        "expiration_date" => $expiration_date
    ])) {
        return $token;
    }

    return false;
}

function loginUser($token) {
    require_once "connect.php";
    $stmt = $conn->prepare("SELECT * FROM tokens WHERE token=:token");
    $stmt->execute(["token" => hash("sha256", $token)]);

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if($data && $data["expiration_date"] > date("Y-m-d")){
        return $data["user_id"];
    }

    return false;
}
