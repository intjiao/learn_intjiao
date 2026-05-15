<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use app\model\Knowledge;
use app\model\Category;

class KnowledgeController extends BaseAdminController
{
    public function list()
    {
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 15);
        $keyword = $this->request->param('keyword', '');
        $categoryId = $this->request->param('category_id', '');
        $type = $this->request->param('type', '');
        $status = $this->request->param('status', '');

        $where = [];
        if ($keyword) {
            $where[] = ['title', 'like', "%{$keyword}%"];
        }
        if ($categoryId !== '') {
            $where[] = ['category_id', '=', (int)$categoryId];
        }
        if ($type) {
            $where[] = ['type', '=', $type];
        }
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }

        $query = Knowledge::where($where);
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $categories = Category::where('type', 'knowledge')->column('name', 'id');

        foreach ($list as &$item) {
            $item['category_name'] = $categories[$item['category_id']] ?? '未分类';
            $item['file_size_format'] = $this->formatFileSize($item['file_size'] ?? 0);
            $item['views'] = $item['view_count'] ?? 0;
        }

        return $this->page($list, $total, $page, $limit);
    }

    public function read($id)
    {
        $knowledge = Knowledge::find($id);
        if (!$knowledge) {
            return $this->error('知识条目不存在');
        }

        Knowledge::where('id', $id)->inc('view_count')->update();
        $data = $knowledge->toArray();
        $data['views'] = $data['view_count'] ?? 0;
        return $this->success($data);
    }

    public function save()
    {
        $data = $this->request->post();

        if (!isset($data['view_count']) && isset($data['views'])) {
            $data['view_count'] = (int)$data['views'];
        }

        if (empty($data['title'])) {
            return $this->error('标题不能为空');
        }

        $knowledge = Knowledge::create([
            'category_id' => $data['category_id'] ?? 0,
            'title' => $data['title'],
            'cover' => $data['cover'] ?? '',
            'type' => $data['type'] ?? 'document',
            'content' => $data['content'] ?? '',
            'file_url' => $data['file_url'] ?? '',
            'file_size' => $data['file_size'] ?? 0,
            'view_count' => $data['view_count'] ?? 0,
            'duration' => $data['duration'] ?? 0,
            'is_public' => $data['is_public'] ?? 1,
            'status' => $data['status'] ?? 1,
            'sort' => $data['sort'] ?? 0,
        ]);

        return $this->success($knowledge, '添加成功');
    }

    public function update($id)
    {
        $knowledge = Knowledge::find($id);
        if (!$knowledge) {
            return $this->error('知识条目不存在');
        }

        $data = $this->request->put();
        if (!isset($data['view_count']) && isset($data['views'])) {
            $data['view_count'] = (int)$data['views'];
        }
        $knowledge->save($data);
        return $this->success(null, '更新成功');
    }

    public function delete($id)
    {
        $knowledge = Knowledge::find($id);
        if (!$knowledge) {
            return $this->error('知识条目不存在');
        }
        $knowledge->delete();
        return $this->success(null, '删除成功');
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
