<?php

namespace BitterPeaceV\LoginAuth;

use pocketmine\Server;

class Database
{
    public static $db = null;

    public static function openDatabase(string $dbfile)
    {
        self::$db = new \SQLite3($dbfile, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);

        self::$db->exec(
            "CREATE TABLE IF NOT EXISTS user (".
            "name TEXT NOT NULL PRIMARY KEY,".
            "uuid TEXT NOT NULL,".
            "iv TEXT NOT NULL)"
        );
    }

    public static function closeDatabase()
    {
        self::$db->close();
    }

    public static function getUserData(string $name): array
    {
        if (self::$db == null) return [];

        $sql = "SELECT * FROM user WHERE name = :name";
        $stmt = self::$db->prepare($sql);
        $stmt->bindValue(":name", \strtolower($name), SQLITE3_TEXT);
        $stmt->execute();

        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        if (!$result) $result = [];
        $stmt = null;

        return $result;
    }

    public static function registerUserData(string $name, string $uuid, string $iv): bool
    {
        if (self::$db == null) return false;

        $sql = "INSERT INTO user (name, uuid, iv) VALUES (:name, :uuid, :iv)";
        $stmt = self::$db->prepare($sql);
        $stmt->bindValue(":name", \strtolower($name), SQLITE3_TEXT);
        $stmt->bindValue(":uuid", $uuid, SQLITE3_TEXT);
        $stmt->bindValue(":iv", $iv, SQLITE3_TEXT);

        $stmt->execute();
        $stmt = null;
        
        return true;
    }
}
