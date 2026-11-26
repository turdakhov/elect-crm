<?php

namespace App\Services;

use App\Models\ProjectProductSet;
use App\Models\ProjectProductSetItem;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Смета в Excel — для закупщика: те же разделы и итоги, что на странице и в
 * PDF (`ProjectProductSet::sections`), но суммы — формулами. Закупщик правит
 * количество или цену у себя, и итог пересчитывается сам, а не расходится
 * с напечатанной цифрой.
 *
 * Фото — уменьшенной копией внутри файла, а не ссылкой: файл уходит
 * поставщику, у которого доступа к CRM нет.
 */
class EstimateXlsx
{
    /** Сторона миниатюры, px. Строка с фото под неё и подгоняется. */
    private const THUMB = 64;

    private const MONEY = '#,##0 "₸";[Red]-#,##0 "₸"';

    private const QTY = '#,##0.##;[Red]-#,##0.##';

    public function build(ProjectProductSet $set): Spreadsheet
    {
        $book = new Spreadsheet;
        $book->getProperties()->setTitle($set->name)->setCreator('CRM');
        $book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $sheet = $book->getActiveSheet();
        $sheet->setTitle(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/u', ' ', $set->name) ?: 'Смета', 0, 31));

        // № · фото · товар · ед. · кол-во · цена · сумма · ссылка
        foreach (['A' => 5, 'B' => 11, 'C' => 58, 'D' => 9, 'E' => 10, 'F' => 13, 'G' => 15, 'H' => 30] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $sheet->setCellValue('A1', $set->name);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', $set->project->name.' · '.now()->format('d.m.Y').
            ($set->fromPlan() ? ' · выдана '.$set->created_at->format('d.m.Y') : ''));
        $sheet->getStyle('A2')->getFont()->getColor()->setRGB('6B7280');
        if ($set->comment) {
            $sheet->setCellValue('A3', $set->comment);
            $sheet->getStyle('A3')->getFont()->setItalic(true);
        }

        $row = 5;
        $head = ['№', 'Фото', 'Товар', 'Ед.', 'Кол-во', 'Цена', 'Сумма', 'Ссылка'];
        $sheet->fromArray($head, null, 'A'.$row);
        $sheet->getStyle("A$row:H$row")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '374151']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(20);
        $sheet->freezePane('A'.($row + 1));
        $headRow = $row;
        $row++;

        $sections = $set->sections();
        $sectionTotals = [];
        $n = 0;
        foreach ($sections as $section) {
            $titleRow = $row;
            $sheet->setCellValue("A$row", $section['title'].' · '.$section['items']->count());
            $sheet->mergeCells("A$row:F$row");
            $sheet->getStyle("A$row:H$row")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '1F2937']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            ]);
            $row++;
            $first = $row;
            foreach ($section['items'] as $item) {
                $this->itemRow($sheet, $row, ++$n, $item);
                $row++;
            }
            $last = $row - 1;
            // Итог раздела — в строке заголовка: так он виден, даже когда раздел свернут глазами.
            $sheet->setCellValue("G$titleRow", "=SUM(G$first:G$last)");
            $sheet->getStyle("G$titleRow")->getNumberFormat()->setFormatCode(self::MONEY);
            $sectionTotals[] = "G$titleRow";
        }

        $sheet->setCellValue("A$row", 'Итого');
        $sheet->mergeCells("A$row:F$row");
        $sheet->setCellValue("G$row", $sectionTotals ? '='.implode('+', $sectionTotals) : 0);
        $sheet->getStyle("A$row:H$row")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G$row")->getNumberFormat()->setFormatCode(self::MONEY);
        $unpriced = ProjectProductSet::unpriced($sections);
        if ($unpriced) {
            $sheet->setCellValue('A'.($row + 1), "Строки без цены в итог не вошли: $unpriced (подсвечены).");
            $sheet->getStyle('A'.($row + 1))->getFont()->getColor()->setRGB('B45309');
        }

        $sheet->getStyle("A$headRow:H$row")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
        $sheet->setAutoFilter("A$headRow:H".($row - 1));

        $page = $sheet->getPageSetup();
        $page->setOrientation(PageSetup::ORIENTATION_PORTRAIT)->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)->setFitToHeight(0)->setRowsToRepeatAtTopByStartAndEnd($headRow, $headRow);
        $sheet->getPageMargins()->setLeft(0.4)->setRight(0.4)->setTop(0.5)->setBottom(0.5);
        $sheet->setSelectedCell('A1');

        return $book;
    }

    /** Отдать файлом; имя — как у PDF, только .xlsx. */
    public function save(ProjectProductSet $set, string $path): void
    {
        (new Xlsx($this->build($set)))->save($path);
    }

    private function itemRow(Worksheet $sheet, int $row, int $n, ProjectProductSetItem $item): void
    {
        $product = $item->product;
        $sheet->setCellValue("A$row", $n);
        $name = $product ? trim($product->name) : 'без товара — '.($item->plan_key ?: 'строка без ключа');
        if ($item->comment) {
            $name .= "\n".$item->comment;
        }
        $sheet->setCellValue("C$row", $name);
        $sheet->setCellValue("D$row", $product?->unit ?? '');
        $sheet->setCellValue("E$row", $item->quantity);
        $price = $item->unitPrice();
        if ($price) {
            $sheet->setCellValue("F$row", $price);
        }
        $sheet->setCellValue("G$row", "=IF(F$row=\"\",0,E$row*F$row)");
        if ($product?->url) {
            $sheet->setCellValue("H$row", $this->host($product->url));
            $sheet->getCell("H$row")->getHyperlink()->setUrl($product->url);
            $sheet->getStyle("H$row")->getFont()->setUnderline(true)->getColor()->setRGB('2563EB');
        }

        $sheet->getStyle("E$row")->getNumberFormat()->setFormatCode(self::QTY);
        $sheet->getStyle("F$row:G$row")->getNumberFormat()->setFormatCode(self::MONEY);
        $sheet->getStyle("A$row:H$row")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("C$row")->getAlignment()->setWrapText(true);
        $sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A$row")->getFont()->getColor()->setRGB('9CA3AF');
        if (! $price) {
            $sheet->getStyle("A$row:H$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');
        }

        $height = $this->photo($sheet, $row, $item) ? self::THUMB * 0.75 + 6 : 18;
        // Длинное название в две строки — строке нужно место и без фото.
        $lines = substr_count($name, "\n") + 1 + intdiv(mb_strlen($name), 75);
        $sheet->getRowDimension($row)->setRowHeight(max($height, 13 * $lines + 5));
    }

    /**
     * Миниатюра фото в колонке B. Через GD, а не файлом как есть: так влезает
     * и webp, которого Excel не знает, и книга не раздувается снимками по
     * мегабайту.
     */
    private function photo(Worksheet $sheet, int $row, ProjectProductSetItem $item): bool
    {
        $file = $item->product?->file?->path;
        $path = $file ? storage_path('app/public/'.$file) : null;
        if (! $path || ! is_file($path)) {
            return false;
        }
        $src = @imagecreatefromstring((string) file_get_contents($path));
        if (! $src) {
            return false;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $k = self::THUMB / max($w, $h);
        $tw = max(1, (int) round($w * $k));
        $th = max(1, (int) round($h * $k));
        $thumb = imagecreatetruecolor(self::THUMB, self::THUMB);
        imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
        imagecopyresampled($thumb, $src, intdiv(self::THUMB - $tw, 2), intdiv(self::THUMB - $th, 2), 0, 0, $tw, $th, $w, $h);
        imagedestroy($src);

        $drawing = new MemoryDrawing;
        $drawing->setImageResource($thumb);
        $drawing->setRenderingFunction(MemoryDrawing::RENDERING_JPEG);
        $drawing->setMimeType(MemoryDrawing::MIMETYPE_JPEG);
        $drawing->setName(mb_substr((string) $item->product->name, 0, 60));
        $drawing->setWidth(self::THUMB)->setHeight(self::THUMB);
        $drawing->setCoordinates('B'.$row);
        $drawing->setOffsetX(7)->setOffsetY(3);
        $drawing->setWorksheet($sheet);

        return true;
    }

    private function host(string $url): string
    {
        return preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST)) ?: $url;
    }
}
