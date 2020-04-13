<?php
require_once("settings.php");

function logEvent($ip, $email, $locale, $product, $version, $os, $time)
{
    try
    {
        $db = database();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        #$db->exec("DROP TABLE log");
        $db->exec("CREATE TABLE IF NOT EXISTS log (
            ip TEXT,
            email TEXT,
            locale TEXT,
            product TEXT,
            version TEXT,
            os TEXT,
            time INTEGER)");

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
        error_log($e);
        return false;
    }

    return true;
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
