<?php

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\Traits\CUTime;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\HasLifecycleCallbacks]
class User extends BaseEntity
{
    use CUTime;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    //当前端需要修改属性时，返回表单数据
    public function getFormArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
        ];
    }

    //返回所有属性给前端
    /**
     * @param array $splices =["form"],合并getFormArray并返回
     * @return array
     */
    public function toArray(array $splices = ["Form"]): array
    {
        $retArray = [
            'createTime' => $this->getCreateTime(),
            'updateTime' => $this->getUpdateTime()
        ];
        return $this->mergeArray($retArray, $splices);
    }
}
