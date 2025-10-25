<?php
error_reporting(0);         // No mostrar ningún warning, notice ni error
ini_set('display_errors', 0);
// Identificador de su tienda
define("USERNAME", "77053934");

// Clave de Test o Producción
define("PASSWORD", "prodpassword_uqwvNEJYWhcELNDIu4nCWi0qbpFDeeJAMDxuW6p0sHkkq");

// Clave Pública de Test o Producción
define("PUBLIC_KEY", "77053934:publickey_R31O9dmqvNAYh80n1RK8BBMn5VDdV4v88d8b9PXeb7OGz9");

// Clave HMAC-SHA-256 de Test o Producción
define("HMAC_SHA256", "LBgCPeb4FQd4naAK8r3t6kQLVXaOat0B5Q5TQ7JwIYAtl1I51");

function formToken($data)
{
    // URL de Web Service REST
    $url = "https://api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment";

    // Encabezado Basic con concatenación de "usuario:contraseña" en base64
    $auth = USERNAME . ":" . PASSWORD;

    $headers = array(
        "Authorization: Basic " . base64_encode($auth),
        "Content-Type: application/json"
    );

    $body = [
        "amount" => $data["amount"] * 100,
        "currency" => $data["currency"],
        "orderId" => $data["orderId"],
        "customer" => [
            "email" => $_POST["email"],
            "billingDetails" => [
                "firstName" =>  $_POST["firstName"],
                "lastName" =>  $_POST["lastName"],
                "phoneNumber" =>  $_POST["phoneNumber"],
                "identityType" =>  $_POST["identityType"],
                "identityCode" =>  $_POST["identityCode"],
                "address" =>  $_POST["address"],
                "country" =>  $_POST["country"],
                "city" =>  $_POST["city"],
                "state" =>  $_POST["state"],
                "zipCode" =>  $_POST["zipCode"],
            ]
        ],
    ];

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $raw_response = curl_exec($curl);

    $response = json_decode($raw_response, true);

    $formToken = @$response["answer"]["formToken"];

    return $formToken;
}

function checkHash($key)
{
    $krAnswer = str_replace('\/', '/',  $_POST["kr-answer"]);

    $calculateHash = hash_hmac("sha256", $krAnswer, $key);

    return ($calculateHash == $_POST["kr-hash"]);
}
