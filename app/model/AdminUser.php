<?php
namespace app\model;

use think\Model;

class AdminUser extends Model
{
    protected $table = 'admin_user';
    protected $pk = 'id';
    protected $hidden = ['password'];

    public function setPasswordAttr($value): string
    {
        if ($value === null || $value === '') return '';
        if (strpos($value, '$2y$') === 0 || strpos($value, '$2a$') === 0 || strpos($value, '$argon') === 0) {
            return $value;
        }
        return password_hash($value, PASSWORD_DEFAULT);
    }

    public function checkPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }
}
