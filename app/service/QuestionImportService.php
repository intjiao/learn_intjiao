<?php
namespace app\service;

use app\model\Question;
use app\model\QuestionOption;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class QuestionImportService
{
    public function importFromExcel(string $filePath, int $categoryId): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $success = 0;
            $errors = [];
            $total = max(count($rows) - 1, 0);

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $lineNum = $i + 1;
                $stem = trim((string)($row[1] ?? ''));

                if ($stem === '') {
                    $errors[] = "第{$lineNum}行：题干为空，已跳过";
                    continue;
                }

                try {
                    $type = $this->normalizeImportType($row[0] ?? '');
                    if (!in_array($type, [1, 2, 3, 4, 5], true)) {
                        $errors[] = "第{$lineNum}行：题型标识无效，只支持 1-5 或对应题型名称，已跳过";
                        continue;
                    }

                    $answerValue = '';
                    $optionRecords = [];

                    if (in_array($type, [1, 2], true)) {
                        $answerLabels = $this->parseAnswerLabels($row[6] ?? '');
                        $optionLabels = ['A', 'B', 'C', 'D'];
                        $optionContents = array_slice($row, 2, 4);
                        $availableLabels = [];

                        foreach ($optionLabels as $idx => $label) {
                            $content = trim((string)($optionContents[$idx] ?? ''));
                            if ($content === '') {
                                continue;
                            }

                            $availableLabels[] = $label;
                            $optionRecords[] = [
                                'label' => $label,
                                'content' => $content,
                                'is_correct' => 0,
                                'sort' => $idx,
                            ];
                        }

                        if (count($optionRecords) < 2) {
                            $errors[] = "第{$lineNum}行：单选题/多选题至少需要填写两个选项，已跳过";
                            continue;
                        }

                        if ($type === 1 && count($answerLabels) !== 1) {
                            $errors[] = "第{$lineNum}行：单选题只能填写一个正确答案，如 A，已跳过";
                            continue;
                        }

                        if (empty($answerLabels)) {
                            $errors[] = "第{$lineNum}行：未填写正确答案，已跳过";
                            continue;
                        }

                        $invalidLabels = array_diff($answerLabels, $availableLabels);
                        if (!empty($invalidLabels)) {
                            $errors[] = "第{$lineNum}行：正确答案包含不存在的选项【" . implode(',', $invalidLabels) . "】，已跳过";
                            continue;
                        }

                        foreach ($optionRecords as &$optionRecord) {
                            $optionRecord['is_correct'] = in_array($optionRecord['label'], $answerLabels, true) ? 1 : 0;
                        }
                        unset($optionRecord);

                        $answerValue = $type === 1 ? $answerLabels[0] : implode(',', $answerLabels);
                    } elseif ($type === 4) {
                        $answerValue = $this->normalizeJudgeAnswer($row[6] ?? '');
                        if ($answerValue === '') {
                            $errors[] = "第{$lineNum}行：判断题答案仅支持 true/false、正确/错误 或 A/B，已跳过";
                            continue;
                        }

                        $optionRecords = [
                            [
                                'label' => 'A',
                                'content' => trim((string)($row[2] ?? '')) ?: '正确',
                                'is_correct' => $answerValue === 'true' ? 1 : 0,
                                'sort' => 0,
                            ],
                            [
                                'label' => 'B',
                                'content' => trim((string)($row[3] ?? '')) ?: '错误',
                                'is_correct' => $answerValue === 'false' ? 1 : 0,
                                'sort' => 1,
                            ],
                        ];
                    } else {
                        $answerValue = trim((string)($row[6] ?? ''));
                    }

                    $question = Question::create([
                        'category_id' => $categoryId,
                        'type' => $type,
                        'stem' => $stem,
                        'answer' => $answerValue,
                        'score' => (float)($row[7] ?? 1.0),
                        'difficulty' => (int)($row[8] ?? 1),
                        'analysis' => trim((string)($row[9] ?? '')),
                        'status' => 1,
                    ]);

                    if (!empty($optionRecords)) {
                        foreach ($optionRecords as &$optionRecord) {
                            $optionRecord['question_id'] = $question->id;
                        }
                        unset($optionRecord);
                        (new QuestionOption())->insertAll($optionRecords);
                    }

                    $success++;
                } catch (\Exception $e) {
                    $errors[] = "第{$lineNum}行：处理失败 - " . $e->getMessage();
                }
            }

            return [
                'success' => $success,
                'total' => $total,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            return [
                'success' => 0,
                'total' => 0,
                'errors' => ['文件读取失败：' . $e->getMessage()],
            ];
        } finally {
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }
    }

    public function getTemplateFields(): array
    {
        return [
            'A' => '题型标识',
            'B' => '题干',
            'C' => '选项A',
            'D' => '选项B',
            'E' => '选项C',
            'F' => '选项D',
            'G' => '正确答案',
            'H' => '分值',
            'I' => '难度（1-5）',
            'J' => '解析',
        ];
    }

    public function buildTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $templateSheet = $spreadsheet->getActiveSheet();
        $templateSheet->setTitle('导入模板');

        foreach ($this->getTemplateFields() as $column => $header) {
            $templateSheet->setCellValue($column . '1', $header);
        }

        $templateSheet->freezePane('A2');
        $templateSheet->getStyle('A1:J1')->getFont()->setBold(true);
        $templateSheet->getStyle('A1:J1')->getAlignment()->setWrapText(true);

        foreach ([
            'A' => 16,
            'B' => 40,
            'C' => 18,
            'D' => 18,
            'E' => 18,
            'F' => 18,
            'G' => 28,
            'H' => 10,
            'I' => 12,
            'J' => 36,
        ] as $column => $width) {
            $templateSheet->getColumnDimension($column)->setWidth($width);
        }

        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('导入说明');
        $guideSheet->fromArray([
            ['项目', '说明'],
            ['题型标识', 'A列必填：1=单选题，2=多选题，3=填空题，4=判断题，5=问答题。也支持填写 single / multi / fill / judge / essay 或中文题型名。'],
            ['单选题', 'C-F 列填写选项A-D，G列填写一个正确答案，例如 A。'],
            ['多选题', 'C-F 列填写选项A-D，G列填写多个正确答案，例如 A,B；也支持直接写 AB。'],
            ['判断题', 'C列可填“正确”，D列可填“错误”；G列支持 true / false、正确 / 错误 或 A / B。'],
            ['填空题', 'C-F 列可以留空，G列填写标准答案；多个可接受答案可用 | 分隔。'],
            ['问答题', 'C-F 列可以留空，G列填写参考答案。'],
            ['分值 / 难度 / 解析', 'H列填写分值，I列填写难度 1-5，J列填写解析，可选。'],
            ['分类归属', '导入时会自动归入当前页面筛选框里选中的分类；未选择分类则导入到未分类。'],
        ], null, 'A1', true);
        $guideSheet->getStyle('A1:B9')->getAlignment()->setWrapText(true);
        $guideSheet->getStyle('A1:B1')->getFont()->setBold(true);
        $guideSheet->getColumnDimension('A')->setWidth(18);
        $guideSheet->getColumnDimension('B')->setWidth(96);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    protected function normalizeImportType($value): int
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return 0;
        }

        $normalized = mb_strtolower(str_replace([' ', '　'], '', $raw), 'UTF-8');
        $map = [
            '1' => 1,
            'single' => 1,
            '单选题' => 1,
            '单选' => 1,
            '2' => 2,
            'multi' => 2,
            '多选题' => 2,
            '多选' => 2,
            '3' => 3,
            'fill' => 3,
            '填空题' => 3,
            '填空' => 3,
            '4' => 4,
            'judge' => 4,
            '判断题' => 4,
            '判断' => 4,
            'truefalse' => 4,
            '5' => 5,
            'essay' => 5,
            '问答题' => 5,
            '问答' => 5,
        ];

        return $map[$normalized] ?? 0;
    }

    protected function parseAnswerLabels($value): array
    {
        $raw = strtoupper(trim((string)$value));
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\s,，、;；|\/]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if (count($parts) <= 1) {
            $parts = preg_split('//u', preg_replace('/[^A-Z]/', '', $raw), -1, PREG_SPLIT_NO_EMPTY);
        }

        $labels = [];
        foreach ($parts as $part) {
            $label = strtoupper(trim((string)$part));
            if (preg_match('/^[A-H]$/', $label)) {
                $labels[] = $label;
            }
        }

        return array_values(array_unique($labels));
    }

    protected function normalizeJudgeAnswer($value): string
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return '';
        }

        $normalized = mb_strtolower(str_replace([' ', '　'], '', $raw), 'UTF-8');
        $trueValues = ['a', 'true', 't', '1', '正确', '对', '是', 'yes', 'y'];
        $falseValues = ['b', 'false', 'f', '0', '错误', '错', '否', 'no', 'n'];

        if (in_array($normalized, $trueValues, true)) {
            return 'true';
        }

        if (in_array($normalized, $falseValues, true)) {
            return 'false';
        }

        return '';
    }
}
