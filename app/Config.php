<?php

class Config {
    private static ?array $data = null;

    public static function get(): array {
        if (self::$data === null) { // lit le fichier une seule fois
            $file = __DIR__ . '/config.json';
            if (!file_exists($file)) throw new Exception("MISSING CONFIG FILE");
            self::$data = json_decode(file_get_contents($file), true);
        }
        return self::$data;
    }
}
