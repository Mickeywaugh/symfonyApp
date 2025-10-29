<?php

namespace App\Entity\Traits;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait CUTime
{

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected ?\DateTimeInterface $createTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeInterface $updateTime = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (property_exists($this, 'createTime')) {
            $this->setCreateTime();
        }
        if (property_exists($this, 'updateTime')) {
            $this->setUpdateTime();
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        if (property_exists($this, 'updateTime')) {
            $this->setUpdateTime();
        }
    }

    public function setCreateTime(?\DateTimeInterface $createTime = new DateTimeImmutable()): static
    {
        $this->createTime = $createTime;
        return $this;
    }

    /**
     * @param bool $object 返回对象还是字符串
     * @return null|string|DateTimeInterface
     */
    public function getCreateTime(bool $object = false): null|string|\DateTimeInterface
    {
        if (property_exists($this, 'createTime')) {
            return $this->createTime ? ($object ? $this->createTime : $this->createTime->format('Y-m-d H:i:s')) : null;
        } else {
            return null;
        }
    }


    public function setUpdateTime(?\DateTimeInterface $updateTime = new DateTimeImmutable()): static
    {
        $this->updateTime = $updateTime;
        return $this;
    }

    /**
     * @param bool $object 返回对象还是字符串
     * @return null|string|DateTimeInterface
     */
    public function getUpdateTime(bool $object = false): null| string|\DateTimeInterface
    {
        if (property_exists($this, 'updateTime')) {
            return $this->updateTime ? ($object ? $this->updateTime : $this->updateTime->format('Y-m-d H:i:s'))  : null;
        } else {
            return null;
        }
    }
}
