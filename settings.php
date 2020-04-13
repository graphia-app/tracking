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

    if(array_key_exists("dbUsername", $settings) && array_key_exists("dbPassword", $settings))
        return new PDO($settings["db"], $settings["dbUsername"], $settings["dbPassword"]);

    try
    {
        $db = new PDO($settings["db"]);
    }
    catch(PDOException $e)
    {
        die("PDOException: " . $e->getMessage());
    }

    return $db;
}
?>
