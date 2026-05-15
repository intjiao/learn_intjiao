<?php
namespace app\controller\api;

use app\controller\BaseApiController;
use app\model\Knowledge;

class KnowledgeController extends BaseApiController
{
    public function list()
    {
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 10);
        $categoryId = $this->request->param('category_id', '');
        $type = $this->request->param('type', '');

        $where = [['status', '=', 1], ['is_public', '=', 1]];
        if ($categoryId) {
            $where[] = ['category_id', '=', (int)$categoryId];
        }
        if ($type) {
            $where[] = ['type', '=', $type];
        }

        $query = Knowledge::where($where);
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['file_size_format'] = $this->formatFileSize($item['file_size'] ?? 0);
        }

        return $this->page($list, $total, $page, $limit);
    }

    public function detail($id)
    {
        $knowledge = Knowledge::find($id);
        if (!$knowledge || $knowledge->status != 1) {
            return $this->error('知识条目不存在');
        }

        Knowledge::where('id', $id)->inc('view_count')->update();
        return $this->success($knowledge);
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
