<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use app\model\Certificate;
use app\model\ExamRecord;
use app\service\CertificateService;

class CertificateController extends BaseAdminController
{
    protected $certService;

    public function __construct()
    {
        parent::__construct(app());
        $this->certService = new CertificateService();
    }

    public function list()
    {
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 15);
        $keyword = $this->request->param('keyword', '');
        $status = $this->request->param('status', '');
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');

        $where = [];
        if ($keyword) {
            $where[] = ['cert_no|user_name|task_title', 'like', "%{$keyword}%"];
        }
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }
        if ($startDate) {
            $where[] = ['issue_date', '>=', $startDate];
        }
        if ($endDate) {
            $where[] = ['issue_date', '<=', $endDate];
        }

        $query = Certificate::where($where)->with('user');
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['user_phone'] = $item['user']['phone'] ?? '';
        }

        return $this->page($list, $total, $page, $limit);
    }

    public function read($id)
    {
        $certificate = Certificate::with('user')->find($id);
        if (!$certificate) {
            return $this->error('证书不存在');
        }

        $data = $certificate->toArray();
        $data['realname'] = $data['user']['realname'] ?? ($data['user_name'] ?? '');
        $data['username'] = $data['user']['username'] ?? '';
        $data['phone'] = $data['user']['phone'] ?? '';
        $data['department'] = $data['user']['department'] ?? '';
        $data['issued_at'] = $data['issue_date'] ?? null;
        $data['revoked_at'] = $data['revoke_time'] ?? null;

        $record = ExamRecord::where('user_id', $data['user_id'])
            ->where('task_id', $data['task_id'])
            ->where('status', 2)
            ->order('id', 'desc')
            ->find();

        if ($record) {
            $data['paper_title'] = $record->paper_title ?? ($data['task_title'] ?? '');
            $data['score'] = $record->score;
            $data['total_score'] = $record->total_score;
            $data['exam_time'] = $record->start_time;
            $data['submit_time'] = $record->submit_time;
        } else {
            $data['paper_title'] = $data['task_title'] ?? '';
            $data['score'] = null;
            $data['total_score'] = null;
            $data['exam_time'] = $data['issue_date'] ?? null;
            $data['submit_time'] = null;
        }

        return $this->success($data);
    }

    public function revoke($id)
    {
        $reason = $this->request->param('reason', '');

        try {
            $this->certService->revokeCertificate($id, $reason);
            return $this->success(null, '撤销成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function verify($certNo)
    {
        $result = $this->certService->verifyCertificate($certNo);
        return $this->success($result);
    }
}
