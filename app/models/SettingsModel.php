<?php

namespace APP\Models;

use ArrayIterator;

class SettingsModel extends AbstractModel
{
    public $Language;
    public $Currency;

    protected static $tableName = "settings";

    protected static array $tableSchema = [
        "UserId"          => self::DATA_TYPE_INT,
        "Language"          => self::DATA_TYPE_STR,
        "Currency"          => self::DATA_TYPE_STR,
    ];

    protected static string $primaryKey = "UserId";

    /**
     * @version 1.0
     * @author Feras Barahemeh
     *
     * method to get setting by each user
     * @param $UserId int ID user  you want get settings
     * @return int|mixed
     */
    public function getSettings(int $UserId): mixed
    {
        $sql = "SELECT * FROM " . static::$tableName . " WHERE " . static::$primaryKey . " = " .  $UserId;

        return $this->getRow($sql);
    }

    /**
     * method to change language in database
     * @param int $userId the id user he wants change language
     * @param string $lang the current language
     * @return mixed
     */
    public static function changeLanguage(int $userId, string $lang): mixed
    {
        $sql = "UPDATE " . static::$tableName . " SET Language = '" . $lang . "' WHERE UserId = " . $userId;
        return AbstractModel::executeQuery($sql);
    }

    /**
     * Get Language User From Database
     * @param int $userId
     * @return mixed
     */
    public static function getLanguage(int $userId): mixed
    {
        $sql = "SELECT `Language` FROM " . static::$tableName . " WHERE `UserId` = " . $userId;
        return (new SettingsModel())->getRow($sql)->Language;
    }
    /**
     * Get currency User From Database
     * @param int $userId
     * @return mixed
     */
    public static function getCurrency(int $userId): mixed
    {
        $sql = "SELECT `Currency` FROM " . static::$tableName . " WHERE `UserId` = " . $userId;
        return (new SettingsModel())->getRow($sql)->Currency;
    }
}
