<?php

namespace Jabe\Impl\Persistence\Entity;

use Jabe\Identity\GroupInterface;
use Jabe\Impl\Db\{
    HasDbRevisionInterface,
    DbEntityInterface
};
use Jabe\Impl\Util\ClassNameUtil;

class GroupEntity implements GroupInterface, \Serializable, DbEntityInterface, HasDbRevisionInterface
{
    protected $id;
    protected int $revision = 0;
    protected $name;
    protected $type;

    public function __construct(?string $id = null)
    {
        $this->id = $id;
    }

    public function getPersistentState()
    {
        $persistentState = [];
        $persistentState["name"] = $this->name;
        $persistentState["type"] = $this->type;
        return $persistentState;
    }

    public function getRevisionNext(): int
    {
        return $this->revision + 1;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getRevision(): ?int
    {
        return $this->revision;
    }

    public function setRevision(int $revision): void
    {
        $this->revision = $revision;
    }

    public function serialize()
    {
        return json_encode([
            'id' => $this->id,
            'name' => $this->name,
            'revision' => $this->revision,
            'type' => $this->type
        ]);
    }

    public function unserialize($data)
    {
        $json = json_decode($data);
        $this->id = $json->id;
        $this->name = $json->name;
        $this->revision = $json->revision;
        $this->type = $json->type;
    }

    public function __toString()
    {
        $className = ClassNameUtil::getClassNameWithoutPackage(get_class($this));
        return $className
                . "[id=" . $this->id
                . ", revision=" . $this->revision
                . ", name=" . $this->name
                . ", type=" . $this->type
                . "]";
    }
}
