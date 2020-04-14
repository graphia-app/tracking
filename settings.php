<?php
function loadSettings()
{
    $settingsFilename = dirname(__FILE__) . "/settings.json";
    $settings = json_decode(file_get_contents($settingsFilename), true);
    if($settings === NULL)
        die("Failed to load " . $settingsFilename);

    return $settings;
}

function database()
{
    $settings = loadSettings();

    if(!array_key_exists("db", $settings))
        die("Settings does not have db key");

    try
    {
        if(array_key_exists("dbUsername", $settings) && array_key_exists("dbPassword", $settings))
            $db = new PDO($settings["db"], $settings["dbUsername"], $settings["dbPassword"]);
        else
            $db = new PDO($settings["db"]);

        $db->exec("CREATE TABLE IF NOT EXISTS log (
            ip TEXT,
            email TEXT,
            locale TEXT,
            product TEXT,
            version TEXT,
            os TEXT,
            time INTEGER)");

        $db->exec("CREATE TABLE IF NOT EXISTS emails (
            address TEXT,
            code TEXT,
            verified INTEGER DEFAULT 0)");
    }
    catch(PDOException $e)
    {
        die("PDOException: " . $e->getMessage());
    }

    return $db;
}
?>
