<?php
namespace app\controller\admin;

use app\controller\BaseAdminController;
use app\model\Question;
use app\model\QuestionOption;
use app\model\Category;
use app\service\QuestionImportService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Db;

class QuestionController extends BaseAdminController
{
    protected $importService;

    public function __construct()
    {
        parent::__construct(app());
        $this->importService = new QuestionImportService();
    }

    protected function normalizeQuestionType($type): int
    {
        if (is_numeric($type)) {
            return (int)$type;
        }

        $map = [
            'single' => 1,
            'multi' => 2,
            'fill' => 3,
            'judge' => 4,
            'essay' => 5,
        ];

        return $map[$type] ?? 1;
    }

    protected function normalizeQuestionOptions($options): array
    {
        if (is_string($options)) {
            $decoded = json_decode($options, true);
            $options = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($options)) {
            return [];
        }

        $normalized = [];
        foreach ($options as $idx => $opt) {
            $normalized[] = [
                'label' => $opt['label'] ?? chr(65 + $idx),
                'content' => $opt['content'] ?? ($opt['option_text'] ?? ''),
                'is_correct' => isset($opt['is_correct']) ? (int)$opt['is_correct'] : (isset($opt['is_answer']) ? (int)$opt['is_answer'] : 0),
            ];
        }

        return $normalized;
    }

    protected function parseBatchIds($ids): array
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (!is_array($ids)) {
            return [];
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, static fn($id) => $id > 0);

        return array_values(array_unique($ids));
    }

    public function list()
    {
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 15);
        $keyword = $this->request->param('keyword', '');
        $type = $this->request->param('type', '');
        $categoryId = $this->request->param('category_id', '');
        $difficulty = $this->request->param('difficulty', '');

        $where = [];
        if ($keyword) {
            $where[] = ['stem', 'like', "%{$keyword}%"];
        }
        if ($type !== '') {
            $where[] = ['type', '=', $this->normalizeQuestionType($type)];
        }
        if ($categoryId !== '') {
            $where[] = ['category_id', '=', (int)$categoryId];
        }
        if ($difficulty !== '') {
            $where[] = ['difficulty', '=', (int)$difficulty];
        }

        $query = Question::where($where);
        $total = $query->count();
        $list = $query->with('options')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $categories = Category::where('type', 'question')->column('name', 'id');

        foreach ($list as &$item) {
            $item['category_name'] = $categories[$item['category_id']] ?? '未分类';
            $item['options'] = $item['options'] ?? [];
            usort($item['options'], fn($a, $b) => $a['sort'] - $b['sort']);
        }

        return $this->page($list, $total, $page, $limit);
    }

    public function read($id)
    {
        $question = Question::with('options')->find($id);
        if (!$question) {
            return $this->error('试题不存在');
        }

        $data = $question->toArray();
        $options = $data['options'] ?? [];
        usort($options, fn($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));
        $data['options'] = $options;

        return $this->success($data);
    }

    public function save()
    {
        $data = $this->request->post();
        $type = $this->normalizeQuestionType($data['type'] ?? 1);
        if (empty($data['stem'])) {
            return $this->error('题干不能为空');
        }

        $question = Question::create([
            'category_id' => $data['category_id'] ?? 0,
            'type' => $type,
            'stem' => $data['stem'],
            'answer' => $data['answer'] ?? '',
            'analysis' => $data['analysis'] ?? '',
            'score' => $data['score'] ?? 1.0,
            'difficulty' => $data['difficulty'] ?? 1,
            'status' => isset($data['status']) ? (int)$data['status'] : 1,
        ]);

        if (in_array($type, [1, 2], true) && !empty($data['options'])) {
            $options = $this->normalizeQuestionOptions($data['options']);
            $optionRecords = [];
            foreach ($options as $idx => $opt) {
                $optionRecords[] = [
                    'question_id' => $question->id,
                    'label' => $opt['label'] ?? chr(65 + $idx),
                    'content' => $opt['content'] ?? '',
                    'is_correct' => $opt['is_correct'] ?? 0,
                    'sort' => $idx,
                ];
            }
            if (!empty($optionRecords)) {
                (new QuestionOption())->insertAll($optionRecords);
            }
        }

        return $this->success($question, '添加成功');
    }

    public function update($id)
    {
        $question = Question::find($id);
        if (!$question) {
            return $this->error('试题不存在');
        }

        $data = $this->request->put();
        $type = $this->normalizeQuestionType($data['type'] ?? $question->type);
        $data['type'] = $type;

        $saveData = $data;
        unset($saveData['options']);
        $question->save($saveData);

        QuestionOption::where('question_id', $id)->delete();
        if (in_array($type, [1, 2], true) && !empty($data['options'])) {
            $options = $this->normalizeQuestionOptions($data['options']);
            $optionRecords = [];
            foreach ($options as $idx => $opt) {
                $optionRecords[] = [
                    'question_id' => $id,
                    'label' => $opt['label'] ?? chr(65 + $idx),
                    'content' => $opt['content'] ?? '',
                    'is_correct' => $opt['is_correct'] ?? 0,
                    'sort' => $idx,
                ];
            }
            if (!empty($optionRecords)) {
                (new QuestionOption())->insertAll($optionRecords);
            }
        }

        return $this->success(null, '更新成功');
    }

    public function delete($id)
    {
        $question = Question::find($id);
        if (!$question) {
            return $this->error('试题不存在');
        }

        QuestionOption::where('question_id', $id)->delete();
        $question->delete();
        return $this->success(null, '删除成功');
    }

    public function batchDelete()
    {
        $ids = $this->parseBatchIds($this->request->post('ids', []));
        if (empty($ids)) {
            return $this->error('请选择要删除的试题');
        }

        Db::transaction(function () use ($ids) {
            QuestionOption::whereIn('question_id', $ids)->delete();
            Question::whereIn('id', $ids)->delete();
        });

        return $this->success(['count' => count($ids)], '删除成功');
    }

    public function batchStatus()
    {
        $ids = $this->parseBatchIds($this->request->post('ids', []));
        $status = $this->request->post('status', null);

        if (empty($ids)) {
            return $this->error('请选择要操作的试题');
        }
        if (!in_array((string) $status, ['0', '1'], true)) {
            return $this->error('状态参数错误');
        }

        $updated = Question::whereIn('id', $ids)->update(['status' => (int) $status]);
        $actionText = (int) $status === 1 ? '启用' : '禁用';

        return $this->success(['count' => (int) $updated], $actionText . '成功');
    }

    public function import()
    {
        $file = $this->request->file('file');
        $categoryId = (int)$this->request->param('category_id', 0);

        if (!$file) {
            return $this->error('请上传文件');
        }

        $extension = strtolower($file->getOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            return $this->error('仅支持上传 xls 或 xlsx 文件');
        }

        $uploadPath = root_path() . 'runtime/uploads/import/' . date('Ymd');
        $saveName = 'question_' . date('His') . '_' . uniqid() . '.' . $extension;

        try {
            $savedFile = $file->move($uploadPath, $saveName);
            $result = $this->importService->importFromExcel($savedFile->getPathname(), $categoryId);

            return $this->success($result, '导入完成');
        } catch (\Throwable $e) {
            return $this->error('导入失败：' . $e->getMessage());
        }
    }

    public function importTemplate()
    {
        $spreadsheet = $this->importService->buildTemplateSpreadsheet();
        $filename = '试题导入模板_' . date('Ymd') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public function export()
    {
        $keyword = $this->request->param('keyword', '');
        $type = $this->request->param('type', '');
        $categoryId = $this->request->param('category_id', '');

        $where = [];
        if ($keyword) {
            $where[] = ['stem', 'like', "%{$keyword}%"];
        }
        if ($type !== '') {
            $where[] = ['type', '=', $this->normalizeQuestionType($type)];
        }
        if ($categoryId !== '') {
            $where[] = ['category_id', '=', (int)$categoryId];
        }

        $list = Question::where($where)->with('options')->order('id', 'desc')->select()->toArray();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('试题列表');

        $headers = ['题型', '题干', '选项A', '选项B', '选项C', '选项D', '正确答案', '分值', '难度', '解析'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        foreach ($list as $row => $item) {
            $options = $item['options'] ?? [];
            usort($options, fn($a, $b) => $a['sort'] - $b['sort']);

            $sheet->setCellValueByColumnAndRow(1, $row + 2, $item['type']);
            $sheet->setCellValueByColumnAndRow(2, $row + 2, $item['stem']);
            $sheet->setCellValueByColumnAndRow(3, $row + 2, $options[0]['content'] ?? '');
            $sheet->setCellValueByColumnAndRow(4, $row + 2, $options[1]['content'] ?? '');
            $sheet->setCellValueByColumnAndRow(5, $row + 2, $options[2]['content'] ?? '');
            $sheet->setCellValueByColumnAndRow(6, $row + 2, $options[3]['content'] ?? '');
            $sheet->setCellValueByColumnAndRow(7, $row + 2, $item['answer'] ?? '');
            $sheet->setCellValueByColumnAndRow(8, $row + 2, $item['score']);
            $sheet->setCellValueByColumnAndRow(9, $row + 2, $item['difficulty']);
            $sheet->setCellValueByColumnAndRow(10, $row + 2, $item['analysis'] ?? '');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="试题列表_' . date('Ymd') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}
