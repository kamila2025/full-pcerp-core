<?php

namespace App\Helpers;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as BasePDF;

class DomPDFHelper
{
    /**
     * 格式化訊息
     */
    public static function render(string $view, array $data): BasePDF
    {
        // 載入 PDF 視圖，並傳入資料
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('A4')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 96,
            ]);

        // $pdf->output();

        // $dom_pdf = $pdf->getDomPDF();
        // $canvas = $dom_pdf->getCanvas();
        // $fontMetrics = $dom_pdf->getFontMetrics();

        // $font = $fontMetrics->getFont('Helvetica', 'normal');

        // $size = 8;

        // $color = [0.6, 0.6, 0.6]; // 淺灰色 (如你圖片範例)

        // $invoiceNumber = $data['invoice_number'] ?? "INV-123456";
        // $amountDue = isset($data['total']) ? ('$' . number_format($data['total'], 0) . ' TWD') ?? "$21.00 USD" : '';
        // // $dueDate = $invoiceNumber[''] ?? "January 23, 2025";

        // // $leftText = "{$invoiceNumber} · {$amountDue} due {$dueDate}";
        // $leftText = "{$invoiceNumber} · {$amountDue}";
        // $rightText = "Page {PAGE_NUM} of {PAGE_COUNT}";

        // // 取得左右文字寬度
        // $leftWidth = $fontMetrics->getTextWidth($leftText, $font, $size);
        // $rightWidth = $fontMetrics->getTextWidth('Page 99 of 99', $font, $size);

        // // 取得 PDF 頁面尺寸
        // $canvasWidth = $canvas->get_width();
        // $canvasHeight = $canvas->get_height();

        // // 設定 Y 軸座標 (最底部以上約25px處)
        // $y = $canvasHeight - 25;

        // // 左邊文字 (左下角邊距約 40 px)
        // $canvas->page_text(20, $y, $leftText, $font, $size, $color);

        // // 右邊頁碼文字 (右下角邊距約 40 px)
        // $canvas->page_text($canvasWidth - $rightWidth - 20, $y, $rightText, $font, $size, $color);

        return $pdf;
    }
}
