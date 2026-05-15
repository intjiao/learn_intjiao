<?php
namespace app\model;

use app\BaseModel\BaseModel;

class User extends BaseModel
{
    protected $table = 'user';
    protected $pk = 'id';

    protected $hidden = ['password'];

    public function group()
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }

    public function checkPassword(string $password): bool
    {
        if (empty($this->password)) return false;
        return password_verify($password, $this->password);
    }

    public function getGenderTextAttr($value, $data): string
    {
        $map = [0 => '未知', 1 => '男', 2 => '女'];
        return $map[$data['gender'] ?? 0] ?? '未知';
    }
}
