<?php

namespace Jabe\Impl;

use Jabe\Impl\Interceptor\{
    CommandInterface,
    CommandContext
};
use Jabe\Task\TaskReportInterface;

class TaskCountByCandidateGroupCmd implements CommandInterface
{
    private $scope;

    public function __construct(TaskReportInterface $scope)
    {
        $this->scope = $scope;
    }

    public function execute(CommandContext $commandContext, ...$args)
    {
        return $this->scope->createTaskCountByCandidateGroupReport($commandContext);
    }

    public function isRetryable(): bool
    {
        return false;
    }
}
