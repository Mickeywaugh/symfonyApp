<?php

namespace App\Controller;

use App\Service\BaseService;
use App\Service\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class BaseController extends AbstractController
{

    const Enable = 1;
    const Disable = 0;
    protected $currUser;
    public function success(array $data = [], string $msg = "Succeed"): JsonResponse
    {
        return new JsonResponse([
            'code' => 0,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    public function error(string $msg = "Error", int $code = 1, array $data = []): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    public function critial(string $msg = "Critial Error", array $data = []): JsonResponse
    {
        Logger::critical($msg);
        return new JsonResponse([
            'code' => 502,
            'msg' => $msg,
            'data' => $data
        ]);
    }
    public function forbidden(string $msg, array $data = []): JsonResponse
    {
        return BaseService::errorResponse($msg, 403, $data);
    }

    public function notFound(string $msg, array $data = []): JsonResponse
    {
        return BaseService::errorResponse($msg, 404, $data);
    }
}
