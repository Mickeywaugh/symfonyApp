<?php

namespace App\Service;

use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use App\Service\BaseService as Util;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class PdfService
{
    public $outputFile;
    // pdf document information
    public $docInfo = [
        "creator" => "BoingTech",
        "author" => "BoingTech",
        "title" => "BoingTech Shipment Sticker",
        "subject" => "BoingTech Shipment Sticker",
        "keywords" => "BoingTech,RFID Tags"
    ];

    // default page settings
    private $pageConfig = [
        "tempDir" => "../var/cache/dev",
        'autoPageBreak' => true,
        'autoScriptToLang' => true,
        'autoLangToFont'   => true,
        "default_font" => "Arial",
        "default_font_size" => 12,
        "dpi" => 120,
        'format' => 'A4',
        'mode' => 'utf-8',
        "mirrorMargins" => true,
        "margin_left" => 5,
        "margin_right" => 5,
        "margin_top" => 5,
        "margin_bottom" => 5,
        "margin_header" => 5,
        "margin_footer" => 5,
        "margin_header_inner" => 2,
        "orientation" => "L"
    ];

    // mPdf instance object
    public $pdf;
    // array data for page items, and this will be used to generate pdf page content
    public $pageItems = [];

    private $pageTemplateHtml = "";
    private $pageSize;

    private $loader = null;
    private $twig = null;
    private $twigTemplate = "";

    public function __construct()
    {
        // error_reporting(0);
        // create new PDF document
        $this->pdf = new Mpdf($this->pageConfig);
        $this->loader = new FilesystemLoader("../templates");
        $this->twig = new Environment($this->loader, [
            'cache' => '../var/cache/twig',
            'debug' => true,
        ]);
        //set page properties
        $this->setDocInfo($this->docInfo);
    }

    public function setDocInfo(array $_docInfo): self
    {
        $this->docInfo = array_merge($this->docInfo, $_docInfo);
        // set document information
        $this->pdf->SetCreator($this->docInfo["creator"]);
        $this->pdf->SetAuthor($this->docInfo["author"]);
        $this->pdf->SetSubject($this->docInfo["subject"]);
        $this->pdf->SetKeywords($this->docInfo["keywords"]);
        $this->pdf->SetTitle($this->docInfo["title"]);
        return $this;
    }


    public function setPageConfig(array $_pageConfig): self
    {
        $this->pageConfig = array_merge($this->pageConfig, $_pageConfig);
        $this->pdf = new Mpdf($this->pageConfig);
        return $this;
    }
    /**
     * @param array $pageConfig 
     * @return $this
     */
    public function trimPage(): self
    {
        $this->pdf->SetHeader(null);
        $this->pdf->SetFooter(null);
        return $this;
    }

    /**
     * @param string $_pageSize
     * @param string $_oriatation
     * @return $this
     */
    public function setPageSize(array $_pageSize, string $orientation = "P"): self
    {
        $this->pageSize = $_pageSize;
        $this->pdf->_setPageSize($this->pageSize, $orientation);
        return $this;
    }

    public function setPageTemplateHtml(string $html): self
    {
        $this->pageTemplateHtml = $html;
        return $this;
    }

    public function setTwigTemplate(string $template): self
    {
        $this->twigTemplate = $template;
        return $this;
    }

    public function loadFonts()
    {
        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $pageConfig['fontDir'] = array_merge($fontDirs, ['fonts']);
        $pageConfig["fontdata"] = $fontData + [ // lowercase letters only in font key
            'msyh' => [
                'R' => 'msyh.ttf',
                'B' => 'msyhbd.ttf'
            ]
        ];
        $this->pageConfig = array_merge($this->pageConfig, $pageConfig);
        $this->pdf = new Mpdf($this->pageConfig);
        return $this;
    }

    private function setHeaderCss(string $css = ""): self
    {
        $headerCss = <<<EOF
        body { 
            font-family: Arial, msyh, sans-serif, dejavusans; 
            font-size: 10pt;
        }

        table {
            border-collapse: collapse; 
        }

        table th, td {
            border: 1px solid black; 
            padding: 1mm;
            vertical-align: middle;
        }

        
        table th {
            text-align: center;
        }
       
        .textCenter {
            text-align: center;
            vertical-align: middle;
        }
        


        EOF;
        $headerCss .= $css;
        $this->pdf->WriteHTML($headerCss, HTMLParserMode::HEADER_CSS);
        return $this;
    }

    // check output file periodically
    /**
     * @param string $outputFile
     * @return bool
     */
    public function checkOutputFile(string $outputFile)
    {
        $count = 0;
        $result = false;
        while ($count < 10) {
            usleep(10000); //等待 10ms
            if (file_exists($outputFile)) {
                $result = true;
                break;
            } else {
                $count++;
            }
        }
        return $result;
    }


    /****************************************Generate Reel Sticker ****************************************/
    /**
     * @param array $tagItems  sticker页面数据数组
     * @return Bolb
     */
    public function genStickers(array $tagItems, $fileName, $common = null, $dstn = "F")
    {

        $fileName = $fileName ? Util::getProjectPath($fileName) : "";
        //设置页面大小
        $this->setHeaderCss();
        try {
            $pageCount = count($tagItems);
            foreach ($tagItems as $k => $tagItem) {
                $bodyHtml = $this->twig->render($this->twigTemplate, ["item" => $tagItem, "common" => $common]);
                $pageBreak = $k  < ($pageCount - 1) ? "<pagebreak>" : "";
                $this->pdf->WriteHTML($bodyHtml . $pageBreak, HTMLParserMode::HTML_BODY);
            }
            $this->pdf->Output($fileName, $dstn);
            return true;
        } catch (\Exception $e) {
            Logger::critical($e->getMessage());
            return false;
        }
    }

    public function generate(array $rendData, $fileName = "", $dstn = "I")
    {
        $fileName = $dstn == 'I' ? Util::getProjectPath($fileName) : "tempPdf.pdf";
        //设置页面大小
        $this->setHeaderCss();
        try {
            $bodyHtml = $this->twig->render($this->twigTemplate, ["data" => $rendData]);
            $this->pdf->WriteHTML($bodyHtml, HTMLParserMode::HTML_BODY);
            $this->pdf->Output($fileName, $dstn);
            return true;
        } catch (\Exception $e) {
            Logger::critical($e->getMessage());
            return false;
        }
    }

    /**
     * 保存文件
     * @param string $fileName 相对项目根目录下data的路径
     * @param array $html
     */
    public function saveFile($fileName, array $html = [])
    {
        $this->setHeaderCss();
        try {
            foreach ($html as $value) {
                $this->pdf->WriteHTML($value);
            }
            $dest = Util::getProjectPath($fileName);
            $this->pdf->Output($dest, 'F');
            if (file_exists($dest)) {
                return $fileName;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Logger::critical($e->getMessage());
            return false;
        }
    }
}
