/**
 * index.php
 * Web3 Wallet Sign in demo
 *
 * @author Valentin Quelquejay <valquel@pm.me>
 */

<?php
require_once "lib/Keccak/Keccak.php";
require_once "lib/Elliptic/EC.php";
require_once "lib/Elliptic/Curves.php";
require_once "lib/JWT/jwt_helper.php";

use Elliptic\EC;
use kornrunner\Keccak;

require_once "config.php";

//api and cors config
function cors()
{
    // Allow from allowed origins
    $allowed_origins = [getenv("FRONTEND_URL")]; //to set as ENV VAR
    if (isset($_SERVER["HTTP_ORIGIN"])) {
        if (in_array($_SERVER["HTTP_ORIGIN"], $allowed_origins)) {
            header("Access-Control-Allow-Origin: {$_SERVER["HTTP_ORIGIN"]}");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Max-Age: 86400"); // cache for 1 day
        }
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
        if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"])) {
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        }

        if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"])) {
            header(
                "Access-Control-Allow-Headers: {$_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"]}"
            );
        }

        exit(0);
    }
}

cors();

// Initialize the session (could use a more secure storage mechanism, good enough)
session_start();

$requestMethod = $_SERVER["REQUEST_METHOD"];
$uri = $_SERVER["REQUEST_URI"];

function pubKeyToAddress($pubkey)
{
    return "0x" .
        substr(
            Keccak::hash(substr(hex2bin($pubkey->encode("hex")), 1), 256),
            24
        );
}

function verifyMessageSignature($message, $signature, $address)
{
    $msglen = strlen($message);
    $hash = Keccak::hash(
        "\x19Ethereum Signed Message:\n{$msglen}{$message}",
        256
    );
    $sign = [
        "r" => substr($signature, 2, 64),
        "s" => substr($signature, 66, 64),
    ];
    $recid = ord(hex2bin(substr($signature, 130, 2))) - 27;
    if ($recid != ($recid & 1)) {
        return false;
    }

    $ec = new EC("secp256k1");
    $pubkey = $ec->recoverPubKey($hash, $sign, $recid);
    error_log($address);

    return strtolower($address) == pubKeyToAddress($pubkey); //we do not check checksums, but is shoud not matter
}

if ($requestMethod === "GET" && $uri === "/nonce") {
    // Generating and returning a nonce for GET request
    $nonce = bin2hex(random_bytes(32));
    $_SESSION["nonce"] = $nonce;
    header("Content-Type: text/plain");
    http_response_code(200);
    echo $nonce;
    exit();
} elseif ($requestMethod === "POST" && $uri === "/verify") {
    header("Content-Type: application/json"); //default resp to json
    try {
        $data = json_decode(file_get_contents("php://input"));
        // Check if 'address' is present in the request body
        if (!isset($data->address)) {
            http_response_code(422);
            echo json_encode(["error" => "Address missing."]);
            exit();
        }

        // Check if 'signature' and 'nonce' are present in the request
        if (!isset($data->signature) || !isset($data->nonce)) {
            http_response_code(422);
            echo json_encode(["error" => "Signature or nonce missing."]);
            exit();
        }

        // Check if we have a session nonce, and it matches with the request
        if (!isset($_SESSION["nonce"]) || $_SESSION["nonce"] != $data->nonce) {
            http_response_code(422);
            echo json_encode(["error" => "Server/client nonce mismatch"]);
            exit();
        }

        $address = $data->address; //from user input
        $nonce = $_SESSION["nonce"]; //from session

        $message = "I own address: {$address}\nrequest: {$nonce}";
        $verificationOK = verifyMessageSignature(
            $message,
            $data->signature,
            $data->address
        );

        // If verification is successful
        if ($verificationOK) {
            http_response_code(200);
            echo json_encode(["message" => "Signature OK."]);
            /* 
                Signature ok --> Do what needs to be done.
            */

        } else {
            http_response_code(500);
            echo json_encode(["error" => "Signature NOK."]);
        }
        session_unset(); //clear nonce
        exit();
    } catch (Exception $e) {
        session_unset(); //clear nonce
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Internal server error."]);
    }
}

// If the request doesn't match the `/nonce` or `/verify` condition, return a 404 error
http_response_code(404);
echo json_encode(["error" => "Not Found"]);
?>
