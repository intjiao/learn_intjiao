<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use app\model\Course;
use app\model\CourseChapter;
use app\model\CourseResource;
use app\model\Category;

class CourseController extends BaseAdminController
{
    public function list()
    {
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 15);
        $keyword = $this->request->param('keyword', '');
        $categoryId = $this->request->param('category_id', '');
        $status = $this->request->param('status', '');

        $where = [];
        if ($keyword) {
            $where[] = ['title', 'like', "%{$keyword}%"];
        }
        if ($categoryId !== '') {
            $where[] = ['category_id', '=', (int)$categoryId];
        }
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }

        $query = Course::where($where);
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $categories = Category::where('type', 'course')->column('name', 'id');

        foreach ($list as &$item) {
            $item['category_name'] = $categories[$item['category_id']] ?? '未分类';
            $item['cover_image'] = $item['cover'] ?? '';
        }

        return $this->page($list, $total, $page, $limit);
    }

    public function read($id)
    {
        $course = Course::with(['chapters.resources'])->find($id);
        if (!$course) {
            return $this->error('课程不存在');
        }

        $data = $course->toArray();
        $data['cover_image'] = $data['cover'] ?? '';
        return $this->success($data);
    }

    public function save()
    {
        $data = $this->request->post();

        if (!isset($data['cover']) && isset($data['cover_image'])) {
            $data['cover'] = $data['cover_image'];
        }

        if (empty($data['title'])) {
            return $this->error('课程标题不能为空');
        }

        $course = Course::create([
            'category_id' => $data['category_id'] ?? 0,
            'title' => $data['title'],
            'cover' => $data['cover'] ?? '',
            'teacher' => $data['teacher'] ?? '',
            'description' => $data['description'] ?? '',
            'content' => $data['content'] ?? '',
            'is_free' => $data['is_free'] ?? 0,
            'price' => $data['price'] ?? 0,
            'is_public' => $data['is_public'] ?? 1,
            'status' => $data['status'] ?? 1,
            'sort' => $data['sort'] ?? 0,
        ]);

        return $this->success($course, '添加成功');
    }

    public function update($id)
    {
        $course = Course::find($id);
        if (!$course) {
            return $this->error('课程不存在');
        }

        $data = $this->request->put();
        if (!isset($data['cover']) && isset($data['cover_image'])) {
            $data['cover'] = $data['cover_image'];
        }
        $course->save($data);
        return $this->success(null, '更新成功');
    }

    public function delete($id)
    {
        $course = Course::find($id);
        if (!$course) {
            return $this->error('课程不存在');
        }

        CourseChapter::where('course_id', $id)->delete();
        $course->delete();
        return $this->success(null, '删除成功');
    }

    public function export()
    {
        $keyword = $this->request->param('keyword', '');
        $categoryId = $this->request->param('category_id', '');
        $status = $this->request->param('status', '');

        $where = [];
        if ($keyword) {
            $where[] = ['title', 'like', "%{$keyword}%"];
        }
        if ($categoryId !== '') {
            $where[] = ['category_id', '=', (int)$categoryId];
        }
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }

        $list = Course::where($where)->order('id', 'desc')->select()->toArray();
        $categories = Category::where('type', 'course')->column('name', 'id');

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="courses_' . date('Ymd_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['ID', '课程标题', '分类', '讲师', '章节数', '总时长(秒)', '学员数', '价格', '状态', '创建时间']);

        foreach ($list as $item) {
            fputcsv($fp, [
                $item['id'],
                $item['title'],
                $categories[$item['category_id']] ?? '未分类',
                $item['teacher'] ?? '',
                $item['chapter_count'] ?? 0,
                $item['total_duration'] ?? 0,
                $item['student_count'] ?? 0,
                $item['price'] ?? 0,
                (int)($item['status'] ?? 0) === 1 ? '上架' : '下架',
                $item['created_at'] ?? '',
            ]);
        }

        fclose($fp);
        exit;
    }

    public function chapters($id)
    {
        $chapters = CourseChapter::where('course_id', $id)
            ->with('resources')
            ->order('sort', 'asc')
            ->select()
            ->toArray();
        return $this->success($chapters);
    }

    public function saveChapter($id)
    {
        $course = Course::find($id);
        if (!$course) {
            return $this->error('课程不存在');
        }

        $data = $this->request->post();
        $payload = [
            'title' => trim((string)($data['title'] ?? '')),
            'sort' => (int)($data['sort'] ?? 0),
        ];

        if ($payload['title'] === '') {
            return $this->error('章节名称不能为空');
        }

        if (!empty($data['id'])) {
            $chapter = CourseChapter::where('course_id', $id)->find($data['id']);
            if (!$chapter) {
                return $this->error('章节不存在');
            }
            $chapter->save($payload);
        } else {
            $chapter = CourseChapter::create(array_merge($payload, [
                'course_id' => $id,
            ]));
            Course::where('id', $id)->inc('chapter_count')->update();
        }

        return $this->success($chapter, '保存成功');
    }

    public function deleteChapter($id)
    {
        $chapter = CourseChapter::find($id);
        if (!$chapter) {
            return $this->error('章节不存在');
        }

        CourseResource::where('chapter_id', $id)->delete();
        $chapter->delete();

        Course::where('id', $chapter->course_id)->dec('chapter_count')->update();

        return $this->success(null, '删除成功');
    }
}
