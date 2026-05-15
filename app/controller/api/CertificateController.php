<?php
namespace app\controller\api;

use app\controller\BaseApiController;
use app\model\Certificate;
use app\service\CertificateService;

class CertificateController extends BaseApiController
{
    protected $certService;

    public function __construct()
    {
        parent::__construct(app());
        $this->certService = new CertificateService();
    }

    public function list()
    {
        $userId = $this->request->userId;
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 10);

        $total = Certificate::where('user_id', $userId)->count();
        $list = Certificate::where('user_id', $userId)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            if ($item['expire_date'] && $item['expire_date'] < date('Y-m-d')) {
                $item['status_text'] = '已过期';
                $item['status'] = 3;
            }
        }

        return $this->page($list, $total, $page, $limit);
    }

    public function detail($id)
    {
        $userId = $this->request->userId;
        $certificate = Certificate::where('id', $id)
            ->where('user_id', $userId)
            ->find();

        if (!$certificate) {
            return $this->error('证书不存在');
        }

        return $this->success($certificate);
    }

    public function verify($certNo)
    {
        $result = $this->certService->verifyCertificate($certNo);
        return $this->success($result);
    }
}
