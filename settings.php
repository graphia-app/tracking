<?php
function loadSettings()
{
    $settingsFilename = dirname(__FILE__) . "/settings.json";
    $settings = json_decode(file_get_contents($settingsFilename), true);
    if($settings === NULL)
    {
        error_log("Failed to load " . $settingsFilename);
        die();
    }

    return $settings;
}

function database()
{
    $settings = loadSettings();

    if(!array_key_exists("db", $settings))
        die();

    if(array_key_exists("dbUsername", $settings) && array_key_exists("dbPassword", $settings))
        return new PDO($settings["db"], $settings["dbUsername"], $settings["dbPassword"]);

    return new PDO($settings["db"]);
}
?>
