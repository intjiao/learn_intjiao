<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use app\model\User;
use app\model\UserGroup;
use app\model\StudyRecord;
use app\model\ExamRecord;
use app\model\TrainingTask;
use app\model\Certificate;
use app\model\Question;
use app\model\Course;
use think\facade\Db;

class Dashboard extends BaseAdminController
{
    public function stats()
    {
        $today = date('Y-m-d');
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        $monthStart = date('Y-m-01');

        $data = [
            'user' => [
                'total' => User::count(),
                'today' => User::whereRaw("DATE(created_at) = ?", [$today])->count(),
                'active' => User::whereRaw("DATE(last_login_time) = ?", [$today])->count(),
            ],
            'question' => [
                'total' => Question::count(),
                'single' => Question::where('type', 1)->count(),
                'multi' => Question::where('type', 2)->count(),
                'fill' => Question::where('type', 3)->count(),
                'judge' => Question::where('type', 4)->count(),
                'essay' => Question::where('type', 5)->count(),
            ],
            'course' => [
                'total' => Course::count(),
                'published' => Course::where('status', 1)->count(),
            ],
            'exam' => [
                'total' => ExamRecord::where('status', 2)->count(),
                'today' => ExamRecord::whereRaw("DATE(start_time) = ?", [$today])->count(),
                'passRate' => 0,
                'avgScore' => 0,
            ],
            'certificate' => [
                'total' => Certificate::where('status', 1)->count(),
                'monthly' => Certificate::whereRaw("issue_date >= ?", [$monthStart])->count(),
            ],
            'training' => [
                'total' => TrainingTask::count(),
                'active' => TrainingTask::where('status', 1)->count(),
            ],
        ];

        $totalExams = ExamRecord::where('status', 2)->count();
        if ($totalExams > 0) {
            $data['exam']['passRate'] = round(
                ExamRecord::where('status', 2)->where('pass_status', 1)->count() / $totalExams * 100,
                1
            );
            $data['exam']['avgScore'] = round(
                ExamRecord::where('status', 2)->avg('score'),
                1
            );
        }

        return $this->success($data);
    }

    public function quickActions()
    {
        $enrollCount = Db::name('training_enroll')->count();
        $studyToday = StudyRecord::whereRaw("DATE(last_study_time) = ?", [date('Y-m-d')])->count();
        $pendingExams = ExamRecord::where('status', 1)->count();
        $pendingCerts = Certificate::where('status', 1)->count();

        return $this->success([
            ['title' => '新增学员', 'count' => User::whereRaw("DATE(created_at) = ?", [date('Y-m-d')])->count(), 'icon' => 'icon-user-plus', 'color' => '#1E9FFF'],
            ['title' => '今日考试', 'count' => $data['exam']['today'] ?? 0, 'icon' => 'icon-write', 'color' => '#FF5722'],
            ['title' => '进行中学员', 'count' => $enrollCount, 'icon' => 'icon-learning', 'color' => '#009688'],
            ['title' => '待发证书', 'count' => $pendingCerts, 'icon' => 'icon-certificate', 'color' => '#FFC107'],
        ]);
    }
}
