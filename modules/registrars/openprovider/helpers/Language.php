<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

class Language
{
    public static function load(): array
    {
        $lang = $_SESSION['Language'] ?? '';
        global $_LANG;
        $langFile = $lang ? $lang . '.php' : 'english.php';
        $langPath = __DIR__ . '/../lang/' . $langFile;
        $overridePath = __DIR__ . '/../lang/overrides/' . $langFile;

        if (file_exists($langPath)) {
            require $langPath;
        }

        if (file_exists($overridePath)) {
            require $overridePath;
        }

        return $_LANG;
    }
}