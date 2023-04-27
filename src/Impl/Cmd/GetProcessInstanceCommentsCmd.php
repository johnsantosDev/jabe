<?php

namespace Jabe\Impl\Cmd;

use Jabe\Impl\Interceptor\{
    CommandInterface,
    CommandContext
};

class GetProcessInstanceCommentsCmd implements CommandInterface, \Serializable
{
    protected $processInstanceId;

    public function __construct(?string $taskId)
    {
        $this->processInstanceId = $taskId;
    }

    public function serialize()
    {
        return json_encode([
            'processInstanceId' => $this->processInstanceId
        ]);
    }

    public function unserialize($data)
    {
        $json = json_decode($data);
        $this->processInstanceId = $json->processInstanceId;
    }

    public function execute(CommandContext $commandContext, ...$args)
    {
        return $commandContext
            ->getAttachmentManager()
            ->findCommentsByProcessInstanceId($this->processInstanceId);
    }

    public function isRetryable(): bool
    {
        return false;
    }
}