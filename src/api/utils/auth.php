<?php

function verifyApiKey($apiKey){
    require_once "connect.php";
    $stmt = $conn->prepare("SELECT * FROM api_keys WHERE api_key=':api_key' AND is_active=TRUE")->execute(["api_key" => hash("sha256", $apiKey)])->fetch(PDO::FETCH_ASSOC);

    return $stmt->rowCount() > 0;
}

function verifyAdminApiKey($apiKey){
    require_once "connect.php";
    $stmt = $conn->prepare("SELECT * FROM admin_api_keys WHERE api_key=':api_key' AND is_active=TRUE")->execute(["api_key" => hash("sha256", $apiKey)])->fetch(PDO::FETCH_ASSOC);

    return $stmt->rowCount() > 0;
}

function verifyUserCredentials($email, $password){
    // TODO: implement
}