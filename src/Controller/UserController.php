<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user', name: "user.")]
class UserController extends BaseController
{

    private $userRepo;

    public function __construct(UserRepository $_userRepo)
    {
        $this->userRepo = $_userRepo;
    }

    #[Route('/page', name: 'usersPage', methods: ['POST'])]
    public function page(Request $request): JsonResponse
    {
        extract($request->toArray());
        $where = [];
        if (isset($pageNum) && $pageNum) {
            $where["pageNum"] = $pageNum;
            $where['pageSize'] = $pageSize;
        }
        $retData = $this->userRepo->page($where);
        return $this->success($retData);
    }

    #[Route('', name: 'addUser', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        extract($request->toArray());
        $retData = $this->userRepo->add($username, $password);
        return $this->success($retData);
    }
    #[Route('/{id}', name: 'deleteUser', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function del(int $int, Request $request): JsonResponse
    {
        extract($request->toArray());
        if ($this->userRepo->delete([$id])) {
            return $this->success(["ids" => $id]);
        } else {
            return $this->error("删除失败");
        }
    }
    #[Route('/{id}', name: 'getUser', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        $user = $this->userRepo->get($id);
        if (!$user) {
            return $this->error("用户不存在");
        }
        return $this->success($user->getFormArray());
    }
    
}
