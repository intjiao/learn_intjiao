<?php
namespace app\controller\api;

use app\controller\BaseApiController;
use app\model\StudyRecord;
use app\model\CourseResource;

class StudyController extends BaseApiController
{
    public function record()
    {
        $userId = $this->request->userId;
        $data = $this->request->post();

        if (empty($data['resource_id'])) {
            return $this->error('资源ID不能为空');
        }

        $resource = CourseResource::find($data['resource_id']);
        if (!$resource) {
            return $this->error('资源不存在');
        }

        $record = StudyRecord::where('user_id', $userId)
            ->where('resource_id', $data['resource_id'])
            ->find();

        $watchedDuration = $data['watch_duration'] ?? 0;
        $totalDuration = $resource->duration;
        $isComplete = ($watchedDuration >= $totalDuration * 0.9);

        if ($record) {
            if ($watchedDuration > $record->watch_duration) {
                $record->watch_duration = $watchedDuration;
            }
            if ($isComplete) {
                $record->is_complete = 1;
            }
            $record->progress = $totalDuration > 0 ? round($watchedDuration / $totalDuration * 100) : 0;
            $record->last_study_time = date('Y-m-d H:i:s');
            $record->save();
        } else {
            StudyRecord::create([
                'user_id' => $userId,
                'course_id' => $data['course_id'] ?? $resource->course_id,
                'chapter_id' => $data['chapter_id'] ?? $resource->chapter_id,
                'resource_id' => $data['resource_id'],
                'watch_duration' => $watchedDuration,
                'total_duration' => $totalDuration,
                'progress' => $totalDuration > 0 ? round($watchedDuration / $totalDuration * 100) : 0,
                'is_complete' => $isComplete ? 1 : 0,
                'last_study_time' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->success([
            'progress' => $isComplete ? 100 : ($totalDuration > 0 ? round($watchedDuration / $totalDuration * 100) : 0),
            'is_complete' => $isComplete,
        ]);
    }
}
