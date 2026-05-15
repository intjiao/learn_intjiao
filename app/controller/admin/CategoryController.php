<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use app\model\Category;

class CategoryController extends BaseAdminController
{
    public function tree()
    {
        $type = $this->request->param('type', '');

        $where = [];
        if ($type) {
            $where[] = ['type', '=', $type];
        }

        $list = Category::where($where)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        $tree = $this->buildTree($list);
        return $this->success($tree);
    }

    public function read($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->error('分类不存在');
        }

        return $this->success($category);
    }

    public function save()
    {
        $data = $this->request->post();

        if (empty($data['name'])) {
            return $this->error('分类名称不能为空');
        }

        if (!empty($data['parent_id'])) {
            $parent = Category::find($data['parent_id']);
            $data['level'] = ($parent->level ?? 0) + 1;
        } else {
            $data['parent_id'] = 0;
            $data['level'] = 1;
        }

        $category = Category::create([
            'name' => $data['name'],
            'type' => $data['type'] ?? 'question',
            'parent_id' => $data['parent_id'] ?? 0,
            'level' => $data['level'] ?? 1,
            'icon' => $data['icon'] ?? '',
            'sort' => $data['sort'] ?? 0,
            'status' => 1,
        ]);

        return $this->success($category, '添加成功');
    }

    public function update($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->error('分类不存在');
        }

        $data = $this->request->put();
        $category->save($data);
        return $this->success(null, '更新成功');
    }

    public function delete($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->error('分类不存在');
        }

        $hasChildren = Category::where('parent_id', $id)->count();
        if ($hasChildren > 0) {
            return $this->error('请先删除子分类');
        }

        $category->delete();
        return $this->success(null, '删除成功');
    }

    protected function buildTree(array $data, int $parentId = 0): array
    {
        $tree = [];
        foreach ($data as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = $this->buildTree($data, $item['id']);
                $node = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'level' => $item['level'],
                    'icon' => $item['icon'],
                    'sort' => $item['sort'],
                    'status' => $item['status'],
                ];
                if (!empty($children)) {
                    $node['children'] = $children;
                }
                $tree[] = $node;
            }
        }
        return $tree;
    }
}
