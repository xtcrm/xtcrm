<?php

namespace cores;

/**
 * 技能模板引擎
 *
 * 纯 PHP 模板渲染（零外部依赖），支持 PDF 导出（wkhtmltopdf）。
 * 模板语法：<?= $var ?> 输出变量，<?php foreach...?> 循环，<?php if...?> 条件。
 */
class SkillTemplate
{
    /**
     * 渲染模板文件
     *
     * @param string $skillName 技能包名（用于定位模板目录）
     * @param string $template  模板文件名（相对 skills/{name}/templates/）
     * @param array  $data      模板变量
     * @return string 渲染后的 HTML
     * @throws \RuntimeException
     */
    public static function render(string $skillName, string $template, array $data): string
    {
        $file = self::resolveTemplatePath($skillName, $template);

        // 提取变量到局部作用域
        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new \RuntimeException("模板渲染失败 [{$skillName}/{$template}]: " . $e->getMessage(), 0, $e);
        }
        return ob_get_clean();
    }

    /**
     * 渲染模板并输出为 PDF
     *
     * @param string $skillName  技能包名
     * @param string $template   模板文件名
     * @param array  $data       模板变量
     * @param string $outputPath 输出 PDF 路径（可选，默认返回二进制内容）
     * @return string|null PDF 内容，失败返回 null
     */
    public static function renderPdf(string $skillName, string $template, array $data, string $outputPath = ''): ?string
    {
        $html = self::render($skillName, $template, $data);

        $bin = config('skill.wkhtmltopdf_bin');
        if (empty($bin)) {
            return null; // 未配置 wkhtmltopdf
        }

        if (empty($outputPath)) {
            $outputPath = runtime_path('cache/skills/' . $skillName . '_' . uniqid() . '.pdf');
            $isTemp = true;
        } else {
            $isTemp = false;
        }

        // 确保输出目录存在
        $outDir = dirname($outputPath);
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        // 写 HTML 到临时文件
        $tmpHtml = $outDir . '/' . uniqid('render_') . '.html';
        file_put_contents($tmpHtml, $html);

        // 执行 wkhtmltopdf
        $cmd = sprintf(
            '%s --page-size A4 --encoding UTF-8 --margin-top 10mm --margin-bottom 10mm --margin-left 15mm --margin-right 15mm %s %s 2>&1',
            escapeshellarg($bin),
            escapeshellarg($tmpHtml),
            escapeshellarg($outputPath)
        );
        exec($cmd, $output, $returnCode);

        // 清理临时 HTML
        @unlink($tmpHtml);

        if ($returnCode !== 0 || !file_exists($outputPath)) {
            return null;
        }

        $content = file_get_contents($outputPath);

        if ($isTemp) {
            @unlink($outputPath);
        }

        return $content !== false ? $content : null;
    }

    /**
     * 解析模板文件路径
     */
    private static function resolveTemplatePath(string $skillName, string $template): string
    {
        $baseDir = config('skill.base_dir') ?: root_path('skills');
        $file = $baseDir . '/' . $skillName . '/templates/' . $template;

        if (!file_exists($file)) {
            throw new \RuntimeException("模板文件不存在: {$file}");
        }

        return $file;
    }

    /**
     * 数字转中文大写金额
     * @param float $num
     * @return string
     */
    public static function cnMoney(float $num): string
    {
        $digits = ['零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖'];
        $radices = ['', '拾', '佰', '仟', '万', '拾', '佰', '仟', '亿'];

        // 处理整数部分
        $intPart = floor(abs($num));
        $decPart = round((abs($num) - $intPart) * 100);

        $result = '';

        // 整数部分
        if ($intPart == 0) {
            $result = '零';
        } else {
            $strInt = (string)$intPart;
            $len = strlen($strInt);
            $zero = false;

            for ($i = 0; $i < $len; $i++) {
                $d = (int)$strInt[$i];
                $pos = $len - $i - 1;

                if ($d == 0) {
                    $zero = true;
                    // 万位和亿位必须输出单位
                    if ($pos % 4 == 0) {
                        $result .= $radices[min($pos, 8)];
                        $zero = false;
                    }
                } else {
                    if ($zero) {
                        $result .= '零';
                        $zero = false;
                    }
                    $result .= $digits[$d] . $radices[min($pos, 8)];
                }
            }
        }

        $result .= '元';

        // 小数部分
        if ($decPart == 0) {
            $result .= '整';
        } else {
            $jiao = intval($decPart / 10);
            $fen = $decPart % 10;
            if ($jiao > 0) $result .= $digits[$jiao] . '角';
            if ($fen > 0) $result .= $digits[$fen] . '分';
        }

        return $num < 0 ? '负' . $result : $result;
    }

    /**
     * 日期转中文格式
     */
    public static function cnDate(string $date): string
    {
        $ts = strtotime($date);
        if ($ts === false) return $date;
        return date('Y年m月d日', $ts);
    }
}
