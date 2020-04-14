<!DOCTYPE html>
<html lang="en">
<head>
<title>Email Verification</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<style>
html, body
{
    height: 100%;
}
</style>
<body>
<div class="container h-100">
    <div class="row h-100 justify-content-center align-items-center">
        <div class="container rounded bg-secondary">
            <div class="row">
                <div class="col">
                    <div class="p-5 text-white text-center">

<?php
require_once("settings.php");

if(isset($_GET["email"]) && isset($_GET["code"]))
{
    $email = $_GET["email"];
    $code = $_GET["code"];

    $db = database();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $insert = "UPDATE emails SET verified = 1 WHERE " .
        "address = '$email' AND code = '$code'";
    $statement = $db->prepare($insert);
    $statement->execute();
    $rowsAffected = $statement->rowCount();

    if($rowsAffected > 0)
        echo "<h3>Thank you for verifying your email address!</h3>";
    else
        echo "<h3>Your email address has already been verified.</h3>";
}
?>
                    </div>
                </div>
            </div>
        </div>
        <div>You may now <a href="javascript:close();">close</a> this window.</div>
    </div>
</div>
</body>
</html>
