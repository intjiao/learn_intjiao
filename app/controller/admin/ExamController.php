<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use app\model\ExamRecord;
use app\model\ExamAnswer;

class ExamController extends BaseAdminController
{
    public function list()
    {
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 15);
        $keyword = $this->request->param('keyword', '');
        $paperId = $this->request->param('paper_id', '');
        $status = $this->request->param('status', '');
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');

        $where = [];
        if ($keyword) {
            $where[] = ['paper_title', 'like', "%{$keyword}%"];
        }
        if ($paperId !== '') {
            $where[] = ['paper_id', '=', (int)$paperId];
        }
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }
        if ($startDate) {
            $where[] = ['start_time', '>=', $startDate . ' 00:00:00'];
        }
        if ($endDate) {
            $where[] = ['start_time', '<=', $endDate . ' 23:59:59'];
        }

        $query = ExamRecord::where($where)->with('user');
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['user_name'] = $item['user']['realname'] ?? '未知';
            $item['user_phone'] = $item['user']['phone'] ?? '';
        }

        return $this->page($list, $total, $page, $limit);
    }

    public function read($id)
    {
        $record = ExamRecord::with(['user', 'paper'])->find($id);
        if (!$record) {
            return $this->error('考试记录不存在');
        }

        $data = $record->toArray();
        $data['realname'] = $data['user']['realname'] ?? '未知';
        $data['username'] = $data['user']['username'] ?? '';
        $data['phone'] = $data['user']['phone'] ?? '';
        $data['department'] = $data['user']['department'] ?? '';
        $data['paper_title'] = $data['paper']['title'] ?? ($data['paper_title'] ?? '');
        $data['pass_score'] = $data['paper']['pass_score'] ?? null;
        $data['exam_time'] = $data['start_time'] ?? null;
        $data['duration_used'] = $data['duration'] ?? 0;
        $data['is_pass'] = $data['pass_status'] ?? 0;

        return $this->success($data);
    }

    public function answers($id)
    {
        $record = ExamRecord::find($id);
        if (!$record) {
            return $this->error('考试记录不存在');
        }

        $answers = ExamAnswer::where('record_id', $id)
            ->with('question.options')
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        return $this->success($answers);
    }

    public function export()
    {
        $keyword = $this->request->param('keyword', '');
        $paperId = $this->request->param('paper_id', '');
        $status = $this->request->param('status', '');
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');

        $where = [];
        if ($keyword) {
            $where[] = ['paper_title', 'like', "%{$keyword}%"];
        }
        if ($paperId !== '') {
            $where[] = ['paper_id', '=', (int)$paperId];
        }
        if ($status !== '') {
            $where[] = ['status', '=', (int)$status];
        }
        if ($startDate) {
            $where[] = ['start_time', '>=', $startDate . ' 00:00:00'];
        }
        if ($endDate) {
            $where[] = ['start_time', '<=', $endDate . ' 23:59:59'];
        }

        $list = ExamRecord::where($where)->with('user')->order('id', 'desc')->select()->toArray();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('考试记录');

        $headers = ['学员姓名', '手机号', '试卷', '得分', '总分', '正确数', '错误数', '及格', '用时', '开始时间', '提交时间', '状态'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        foreach ($list as $row => $item) {
            $sheet->setCellValueByColumnAndRow(1, $row + 2, $item['user']['realname'] ?? '未知');
            $sheet->setCellValueByColumnAndRow(2, $row + 2, $item['user']['phone'] ?? '');
            $sheet->setCellValueByColumnAndRow(3, $row + 2, $item['paper_title'] ?? '');
            $sheet->setCellValueByColumnAndRow(4, $row + 2, $item['score'] ?? '-');
            $sheet->setCellValueByColumnAndRow(5, $row + 2, $item['total_score'] ?? '-');
            $sheet->setCellValueByColumnAndRow(6, $row + 2, $item['correct_count'] ?? 0);
            $sheet->setCellValueByColumnAndRow(7, $row + 2, $item['wrong_count'] ?? 0);
            $sheet->setCellValueByColumnAndRow(8, $row + 2, ($item['pass_status'] ?? '') == 1 ? '及格' : '不及格');
            $sheet->setCellValueByColumnAndRow(9, $row + 2, $item['duration'] ?? 0 . '秒');
            $sheet->setCellValueByColumnAndRow(10, $row + 2, $item['start_time'] ?? '');
            $sheet->setCellValueByColumnAndRow(11, $row + 2, $item['submit_time'] ?? '');
            $sheet->setCellValueByColumnAndRow(12, $row + 2, $item['status'] == 2 ? '已完成' : ($item['status'] == 1 ? '进行中' : '已取消'));
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="考试记录_' . date('Ymd') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}
