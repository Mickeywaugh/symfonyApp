<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelService
{

  protected $styleAll = ['alignment' => [
    'horizontal' => "center",
    'vertical' => "center"
  ]];

  public function __construct() {}

  // 读取excel指定列为数组
  // $colums 数组里第1个元素列为返回数组的key
  public function readExcel(string $path, array $colums): array
  {
    $spreadsheet = IOFactory::load($path);
    $sheet = $spreadsheet->getActiveSheet();
    $data = [];
    for ($i = 1; $i <= $sheet->getHighestRow(); $i++) {
      $row = [];
      $key = "";
      foreach ($colums as $k => $colum) {
        //$colums里的首列值为key
        if ($k == 0) {
          $key = $sheet->getCell($colum . $i)->getValue();
        }
        array_push($row, $sheet->getCell($colum . $i)->getValue());
      }
      if ($key) {
        $data[$key] = $row;
      }
    }
    return $data;
  }


  /**生成发货单
   * @param $data array 
   * @param $path string
   * @return bool
   */
  public function genDeliveryNote(array $data, string $fileName): bool
  {
    if (!is_array($data)) return false;
    try {
      // 处理路径，如果不存在则创建，并返回路径和文件名
      BaseService::mkdirP($fileName);
      $spreadsheet = new Spreadsheet();
      $sheet = $spreadsheet->getActiveSheet();
      //title column width
      $sheet->getColumnDimension('A')->setWidth(12); // Carton No.
      $sheet->getColumnDimension('B')->setWidth(15); // Reel No.
      $sheet->getColumnDimension('C')->setWidth(20); // Batch No.
      $sheet->getColumnDimension('D')->setWidth(14); // Good Qty
      $sheet->getColumnDimension('E')->setWidth(6); // Good Qty
      $sheet->getColumnDimension('F')->setWidth(14); // Good Qty
      $sheet->getColumnDimension('G')->setWidth(6); // Bad Qty

      // set title

      $sheet->mergeCells('A1:G1');
      $sheet->getStyle('A1')->getAlignment()->setWrapText(true);
      $sheet->getStyle('A1')->getFont()->setSize(18);
      $sheet->getStyle('A1')->applyFromArray($this->styleAll);
      $sheet->getStyle('A1')->getFont()->setBold(true);
      $sheet->getRowDimension(1)->setRowHeight(60);

      $sheet->setCellValue('A1', "DELIVERY NOTE\n(装箱清单)");
      //set column
      $sheet->setCellValue('A2', "Carton No \n 箱号");
      $sheet->setCellValue('B2', "Reel No. \n 卷号");
      $sheet->setCellValue('C2', "Batch No.\n 批次号");
      $sheet->mergeCells('D2:E2');
      $sheet->setCellValue('D2', "Good QTY\n 良品");
      $sheet->mergeCells('F2:G2');
      $sheet->setCellValue('E2', "Bad QTY \n 不良品");

      $sheet->getStyle('A2:E2')->getAlignment()->setWrapText(true);
      $sheet->getRowDimension(2)->setRowHeight(32);

      //data row begin
      $row = 3;

      $sumData = [
        "cartonCount" => 0,
        "reelCount" => 0,
        "goodCount" => 0,
        "badCount" => 0
      ];
      extract($sumData);
      $cartonIdx = 0;
      foreach ($data as $v) {
        if ($cartonIdx != $v["cartonIdx"]) $cartonCount++;
        extract($v);

        $sheet->setCellValue('A' . $row, $cartonIdx);
        $sheet->setCellValue('B' . $row, $subReelNo);
        $sheet->setCellValue('C' . $row, $orderNo);
        $sheet->setCellValue('D' . $row, $accept);
        $sheet->setCellValue('E' . $row, "pcs");
        $sheet->setCellValue('F' . $row, $reject);
        $sheet->setCellValue('G' . $row, "pcs");
        $sheet->getRowDimension($row)->setRowHeight(20);
        $reelCount++;
        $goodCount += $accept;
        $badCount += $reject;
        $row++;
      }
      $sheet->getRowDimension($row)->setRowHeight(30);
      //total
      $sheet->setCellValue('A' . $row, "Total Qty \n 总数");
      $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);

      // $sheet->setCellValue('B' . $row, "Carton Count: $cartonCount");
      // $sheet->setCellValue('C' . $row, "Reel Count: $reelCount");
      $sheet->setCellValue('D' . $row, $goodCount);
      $sheet->setCellValue('E' . $row, "pcs");

      $sheet->setCellValue('F' . $row, $badCount);
      $sheet->setCellValue('G' . $row, "pcs");

      $sheet->getStyle('A1:G' . $row)->applyFromArray($this->styleAll);
      $sheet->getStyle('A2:G' . $row)->getBorders()->getAllBorders()->setBorderStyle("thin");

      $writer = new Xlsx($spreadsheet);
      $writer->save(BaseService::getProjectPath($fileName));
      $sheet->disconnectCells();
      Logger::log("生成发货单成功：" . $fileName);
      return true;
    } catch (\Exception $e) {
      Logger::critical($e->getMessage());
      return false;
    }
  }
}
