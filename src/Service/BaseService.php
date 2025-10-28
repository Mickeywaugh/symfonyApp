<?php

namespace App\Service;

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class BaseService
{
    public static $INITIALPASSWORD = "123456";
    public static $APISUFFIX = "/api/v1/";

    public static function getInstance(): static
    {
        return new static();
    }

    public static function errorResponse(string $msg, int $code = 1, $data = null): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    public static function successResponse(string $msg, $data = null): JsonResponse
    {
        return new JsonResponse([
            'code' => 0,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    public static function criticalResponse(string $msg, $data = null): JsonResponse
    {
        Logger::critical($msg, $data);
        return new JsonResponse([
            'code' => 502,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    public static function uniqidReal($lenght = 16)
    {
        // uniqid gives 13 chars, but you could adjust it to your needs.
        if (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
        } else {
            $bytes = random_bytes(ceil($lenght / 2));
        }
        return strtoupper(substr(bin2hex($bytes), 0, $lenght));
    }

    public static function getProjectDir()
    {
        return (new Kernel('dev', true))->getProjectDir();
    }

    public static function getProjectPath($fileName)
    {
        return self::getProjectDir() . "/" . $fileName;
    }

    public static function setProjectPath($fileName)
    {
        $projectPath = self::getProjectPath($fileName);
        try {
            self::mkdirP($fileName);
            return $projectPath;
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            return false;
        }
    }

    public static function getPublicStaticPath($param)
    {
        return match ($param) {
            'userQrcode' => 'download/images/QrCode/user/',
            'orderQrcode' => 'download/images/QrCode/order/',
            'rawmatQrcode' => 'download/images/QrCode/rawmat/',
            'machineQrcode' => 'download/images/QrCode/machine/',
            default => null,
        };
    }

    // 创建文件，路径相对于public目录,(index.php所在的目录)
    public static function touchFile(string $fileName)
    {
        $fileName = realpath($fileName);
        try {
            Logger::log($fileName);
            touch($fileName);
            return $fileName;
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            return false;
        }
    }

    /**
     * 创建文件夹
     * @param string $dirPath, 相对于项目目录
     * @param int $mode
     * @return string|bool
     */
    public static function mkdirP(string $fileName, $mode = 0775)
    {
        $fileName = self::getProjectPath($fileName);
        $pathName = dirname($fileName);
        try {
            if (!file_exists($pathName)) {
                mkdir($pathName, $mode, true);
            }
            return $fileName;
        } catch (\Exception $e) {
            Logger::error("create $fileName failed, msg=>" . $e->getMessage());
        }
    }
    public static function convertToSnakeCase(string $input): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $input));
    }


    public static function getRequest()
    {
        return new Request();
    }

    // 获取协议和域名
    public static function getSchemeHost()
    {
        $request = self::getInstance()->getRequest();
        $hostName = $request->getSchemeAndHttpHost();
        return $hostName;
    }
    public static function getIps()
    {
        $request = self::getInstance()->getRequest();
        $ip = $request->getClientIps();
        return $ip;
    }

    public static function getUserAgent()
    {
        $request = self::getInstance()->getRequest();
        $userAgent = $request->headers->get('User-Agent');
        return $userAgent;
    }

    // 驼峰转换下划线,首字小写不转换 configName => config_name
    public static function toSnakeCase(string $input): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $input));
    }

    // snakeCase 转换为小驼峰格式, config_name => configName
    public static function toCamelCase(string $input): string
    {
        // // 如果输入已经是小驼峰或单个单词，直接返回
        if (ctype_lower($input[0]) && !preg_match('/[A-Z]/', $input)) {
            return $input;
        }

        // 分割字符串（支持下划线、短横线分隔的单词）
        $words = preg_split('/[-_]+/', $input);

        // 将每个单词首字母转换为大写（除了第一个单词）
        array_walk($words, function (&$word, $index) {
            if ($index !== 0) {
                $word = ucfirst(strtolower($word));
            }
        });

        // 合并单词，形成小驼峰格式
        return implode('', $words);
    }

    // 转换为大驼峰格式, configName => ConfigName
    public static function toPascalCase(string $input): string
    {
        $camelCase = self::toCamelCase($input);
        // 将小驼峰的第一个字母转为大写，得到大驼峰形式
        return ucfirst($camelCase);
    }


    /**
     * 保存base64图片到路径，并加入异常处理
     *
     * @param string $base64 图片的base64编码
     * @param string $filePath 保存图片的路径
     * @return string 返回图片保存的路径，或在异常时返回错误信息
     */
    public static function saveBase64Image(string $base64, string $filePath): string
    {
        try {
            if ($base64) {
                // mkdir(dirname($filePath), 0755, true);
                $base64 = str_replace('data:image/png;base64,', '', $base64);
                file_put_contents($filePath,  base64_decode($base64));
                return $filePath; // 成功保存后返回文件路径
            } else {
                Logger::log("Base64字符串为空");
                return false;
            }
        } catch (\Exception $e) {
            // 记录日志或者处理异常
            Logger::error("保存图片时发生错误：" . $e->getMessage());
            return false;
        }
    }

    /**
     * 输出文件
     * @param string $filePath 相对于项目目录的文件路径
     * @param string|null $fileName 返回至浏览器的文件名
     * @return void
     */
    public static function responseFile(string $filePath, bool $inline = true): ?Response
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $headerContentType = SELF::getContentType($extension);
        if (!$filePath) {
            SELF::errorResponse("Filepath empty", 404);
        }
        $projectFilePath = self::getProjectPath($filePath);
        try {
            Logger::log("文件读取：" . $projectFilePath);
            ob_start();
            $fileName = basename($projectFilePath);
            $fileContent = file_get_contents($projectFilePath);
            $response = new Response($fileContent);
            // 输出文件头信息
            $response->headers->set("Content-Type", $headerContentType);
            $response->headers->set("Content-Disposition", ($inline ? "inline" : "attachment") . "; filename=" . $fileName);
            ob_end_flush();
            return $response;
        } catch (\Exception $e) {
            Logger::error("文件读取异常：" . $e->getMessage());
            SELF::errorResponse("File Read Error", 500);
            return new Response();
        }
    }

    public static function getContentType(string $extension)
    {
        $extension = strtolower($extension);
        return match ($extension) {
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
    }
}
