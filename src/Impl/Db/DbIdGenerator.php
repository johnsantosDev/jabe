<?php

namespace Jabe\Impl\Db;

use Jabe\Impl\Cfg\IdGeneratorInterface;
use Jabe\Impl\Cmd\GetNextIdBlockCmd;
use Jabe\Impl\Interceptor\CommandExecutorInterface;

class DbIdGenerator implements IdGeneratorInterface
{
    protected int $idBlockSize = 0;
    protected int $nextId = 0;
    protected int $lastId = 0;

    protected $commandExecutor;

    public function __construct()
    {
        $this->reset();
    }

    public function getNextId(...$args): ?string
    {
        $lock = null;
        if (!empty($args)) {
            foreach ($args as $arg) {
                if ($arg instanceof \Swoole\Lock) {
                    $lock = $arg;
                    $lock->lock();
                    break;
                }
            }
        }
        try {
            if ($this->lastId < $this->nextId) {
                $this->getNewBlock();
            }
            $nextId = $this->nextId++;
            return strval($nextId);
        } finally {
            if ($lock !== null) {
                $lock->unlock();
            }
        }
    }

    protected function getNewBlock(): void
    {
        $idBlock = $this->commandExecutor->execute(new GetNextIdBlockCmd($this->idBlockSize));
        $this->nextId = $idBlock->getNextId();
        $this->lastId = $idBlock->getLastId();
    }

    public function getIdBlockSize(): int
    {
        return $this->idBlockSize;
    }

    public function setIdBlockSize(int $idBlockSize): void
    {
        $this->idBlockSize = $idBlockSize;
    }

    public function getCommandExecutor(): CommandExecutorInterface
    {
        return $this->commandExecutor;
    }

    public function setCommandExecutor(CommandExecutorInterface $commandExecutor): void
    {
        $this->commandExecutor = $commandExecutor;
    }

    /**
     * Reset inner state so that the generator fetches a new block of IDs from the database
     * when the next ID generation request is received.
     */
    public function reset(): void
    {
        $this->nextId = 0;
        $this->lastId = -1;
    }
}
