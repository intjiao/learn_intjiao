<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use app\model\SystemConfig;

class ConfigController extends BaseAdminController
{
    public function group($group)
    {
        $configs = SystemConfig::where('group', $group)->order('sort', 'asc')->select();
        return $this->success($configs);
    }

    public function update($name)
    {
        $value = $this->request->put('value');

        $config = SystemConfig::where('name', $name)->find();
        if (!$config) {
            return $this->error('配置项不存在');
        }

        $config->value = $value;
        $config->save();

        return $this->success(null, '更新成功');
    }
}
