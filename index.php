<?php
require_once("settings.php");

function baseUrl()
{
    return sprintf("%s://%s",
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
        $_SERVER['SERVER_NAME']
    );
}

function verifyEmail($email)
{
    if(preg_match("/^anon_[a-f0-9]{7}@.*$/", $email) === 1)
    {
        // User opted for anonyimity
        return;
    }

    try
    {
        $db = database();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $select = "SELECT COUNT(address) FROM emails WHERE address = '$email'";
        $statement = $db->prepare($select);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        $alreadyKnown = $row['COUNT(address)'] > 0;

        if($alreadyKnown)
            return;

        $code = md5(rand());
        $insert = "INSERT INTO emails (address, code) VALUES(:address, :code)";
        $statement = $db->prepare($insert);
        $statement->bindParam(':address', $email);
        $statement->bindParam(':code', $code);

        $statement->execute();

        // Send them an email to verify it's a real address
        $subject = "Email Verification";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf8\r\n";
        $headers .= "From: Graphia <info@graphia.app>\r\n";

        $link = baseUrl() . "/verifyemail.php?email=$email&code=$code";
        $body = file_get_contents("email/template.html");
        $body = str_replace("__VERIFY_LINK__", $link, $body);

        mail($email, $subject, $body, $headers);
    }
    catch(Exception $e)
    {
        die($e);
    }
}

function logEvent($ip, $email, $locale, $product, $version, $os, $time)
{
    try
    {
        $db = database();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $insert = "INSERT INTO log
            (ip, email, locale, product, version, os, time)
            VALUES(:ip, :email, :locale, :product, :version, :os, :time)";
        $statement = $db->prepare($insert);
        $statement->bindParam(':ip', $ip);
        $statement->bindParam(':email', $email);
        $statement->bindParam(':locale', $locale);
        $statement->bindParam(':product', $product);
        $statement->bindParam(':version', $version);
        $statement->bindParam(':os', $os);
        $statement->bindParam(':time', $time);

        $statement->execute();
    }
    catch(Exception $e)
    {
        die($e);
    }

    verifyEmail($email);
}

$reply = array("error" => "none", "content" => "");

if(!isset($_POST["request"]))
{
    $reply["error"] = "missing_request";
    exit(json_encode($reply));
}

$request = json_decode($_POST["request"], true);

if($request === false)
{
    $reply["error"] = "malformed_request";
    exit(json_encode($reply));
}

if(!empty($_SERVER["HTTP_CLIENT_IP"]))
    $ip = $_SERVER["HTTP_CLIENT_IP"];
elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
    $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
else
    $ip = $_SERVER["REMOTE_ADDR"];

switch($request["action"])
{
case "getip":
    $reply["content"] = $ip;
    break;

case "submit":
    $payload = $request["payload"];
    $email = $payload["email"];
    $locale = $payload["locale"];
    $product = $payload["product"];
    $version = $payload["version"];
    $os = $payload["os"];

    logEvent($ip, $email, $locale, $product, $version, $os, time());
    break;

default:
    $reply["error"] = "unknown_action";
    break;
}

exit(json_encode($reply));
?>
