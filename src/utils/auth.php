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

function verifyUserCredentials($email, $password){
    // TODO: implement
}