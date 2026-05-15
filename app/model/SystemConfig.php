<?php
namespace app\model;

use app\BaseModel\BaseModel;

class SystemConfig extends BaseModel
{
    protected $table = 'system_config';
    protected $pk = 'id';

    protected $type = ['options' => 'json'];

    public static function getConfigValue(string $name, $default = null)
    {
        $config = self::where('name', $name)->find();
        return $config ? $config->value : $default;
    }

    public static function setConfigValue(string $name, $value): bool
    {
        $config = self::where('name', $name)->find();
        if ($config) {
            $config->value = $value;
            return $config->save();
        }
        return false;
    }
}
