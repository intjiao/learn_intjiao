<?php
namespace app\controller\api;

use app\controller\BaseApiController;
use app\model\Course;
use app\model\CourseChapter;
use app\model\CourseResource;
use app\model\StudyRecord;

class CourseController extends BaseApiController
{
    public function list()
    {
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 10);
        $categoryId = $this->request->param('category_id', '');

        $where = [['status', '=', 1], ['is_public', '=', 1]];
        if ($categoryId) {
            $where[] = ['category_id', '=', (int)$categoryId];
        }

        $query = Course::where($where);
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return $this->page($list, $total, $page, $limit);
    }

    public function detail($id)
    {
        $course = Course::with(['chapters.resources'])->find($id);
        if (!$course || $course->status != 1) {
            return $this->error('课程不存在或已下架');
        }

        $userId = $this->request->userId;
        $studyRecords = StudyRecord::where('user_id', $userId)
            ->where('course_id', $id)
            ->select()
            ->toArray();

        $completedResources = array_column($studyRecords, 'resource_id');
        $totalDuration = 0;
        $watchedDuration = 0;

        foreach ($course['chapters'] as &$chapter) {
            foreach ($chapter['resources'] as &$resource) {
                $totalDuration += $resource['duration'] ?? 0;
                if (in_array($resource['id'], $completedResources)) {
                    $resource['is_completed'] = true;
                    $watchedDuration += $resource['duration'] ?? 0;
                } else {
                    $resource['is_completed'] = false;
                }
            }
        }

        $course['progress'] = $totalDuration > 0 ? round($watchedDuration / $totalDuration * 100) : 0;
        $course['completed_resources'] = $completedResources;

        return $this->success($course);
    }

    public function chapters($id)
    {
        $course = Course::find($id);
        if (!$course) {
            return $this->error('课程不存在');
        }

        $chapters = CourseChapter::where('course_id', $id)
            ->with('resources')
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        $userId = $this->request->userId;
        $studyRecords = StudyRecord::where('user_id', $userId)
            ->where('course_id', $id)
            ->column('resource_id');

        foreach ($chapters as &$chapter) {
            foreach ($chapter['resources'] as &$resource) {
                $resource['is_completed'] = in_array($resource['id'], $studyRecords);
            }
        }

        return $this->success($chapters);
    }

    public function enroll($id)
    {
        $course = Course::find($id);
        if (!$course) {
            return $this->error('课程不存在');
        }

        return $this->success(null, '报名成功');
    }

    public function progress($id)
    {
        $userId = $this->request->userId;
        $records = StudyRecord::where('user_id', $userId)
            ->where('course_id', $id)
            ->select()
            ->toArray();

        $totalDuration = 0;
        $watchedDuration = 0;

        foreach ($records as $record) {
            $watchedDuration += $record['watch_duration'] ?? 0;
            $totalDuration += $record['total_duration'] ?? 0;
        }

        $progress = $totalDuration > 0 ? round($watchedDuration / $totalDuration * 100) : 0;

        return $this->success([
            'progress' => $progress,
            'watched_duration' => $watchedDuration,
            'total_duration' => $totalDuration,
            'completed_count' => count(array_filter($records, fn($r) => !empty($r['is_complete']))),
        ]);
    }
}
