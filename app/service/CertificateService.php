<?php
namespace app\service;

use app\model\Certificate;
use app\model\User;
use app\model\TrainingTask;
use app\model\TrainingEnroll;
use app\model\SystemConfig;

class CertificateService
{
    public function generateCertNo(): string
    {
        $prefix = SystemConfig::getConfigValue('cert_prefix', 'CERT');
        $date = date('Ymd');
        $rand = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        return $prefix . $date . $rand;
    }

    public function issueCertificate(int $userId, int $taskId): Certificate
    {
        $user = User::find($userId);
        $task = TrainingTask::find($taskId);

        if (!$user || !$task) {
            throw new \Exception('用户或培训任务不存在');
        }

        $exists = Certificate::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->where('status', 1)
            ->find();
        if ($exists) {
            return $exists;
        }

        $expireYears = $task->certificate_expire_years ?? null;
        $expireDate = $expireYears ? date('Y-m-d', strtotime("+{$expireYears} years")) : null;

        $certificate = Certificate::create([
            'user_id' => $userId,
            'task_id' => $taskId,
            'task_title' => $task->title,
            'user_name' => $user->realname,
            'cert_no' => $this->generateCertNo(),
            'issue_date' => date('Y-m-d'),
            'expire_date' => $expireDate,
            'status' => 1,
        ]);

        TrainingEnroll::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->update([
                'is_complete' => 1,
                'complete_time' => date('Y-m-d H:i:s'),
                'status' => 2,
                'certificate_id' => $certificate->id,
            ]);

        TrainingTask::where('id', $taskId)->inc('complete_count')->update();

        return $certificate;
    }

    public function revokeCertificate(int $certId, string $reason = ''): bool
    {
        $certificate = Certificate::find($certId);
        if (!$certificate) {
            throw new \Exception('证书不存在');
        }

        $certificate->status = 2;
        $certificate->revoke_reason = $reason;
        $certificate->revoke_time = date('Y-m-d H:i:s');
        $certificate->save();

        TrainingEnroll::where('user_id', $certificate->user_id)
            ->where('task_id', $certificate->task_id)
            ->update(['certificate_id' => null]);

        TrainingTask::where('id', $certificate->task_id)->dec('complete_count')->update();

        return true;
    }

    public function verifyCertificate(string $certNo): array
    {
        $certificate = Certificate::where('cert_no', $certNo)->find();
        if (!$certificate) {
            return ['valid' => false, 'message' => '证书编号不存在'];
        }

        if ($certificate->status == 2) {
            return ['valid' => false, 'message' => '证书已撤销'];
        }

        if ($certificate->expire_date && $certificate->expire_date < date('Y-m-d')) {
            $certificate->status = 3;
            $certificate->save();
            return ['valid' => false, 'message' => '证书已过期'];
        }

        return [
            'valid' => true,
            'data' => [
                'cert_no' => $certificate->cert_no,
                'user_name' => $certificate->user_name,
                'task_title' => $certificate->task_title,
                'issue_date' => $certificate->issue_date,
                'expire_date' => $certificate->expire_date,
                'status' => $certificate->status,
            ],
        ];
    }
}
