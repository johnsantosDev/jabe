<?php

namespace Jabe\Impl;

use Jabe\History\{
    HistoricActivityInstanceInterface,
    HistoricProcessInstanceInterface,
    HistoricTaskInstanceInterface,
    HistoricVariableUpdateInterface,
    UserOperationLogEntryInterface
};
use Jabe\Impl\Cmd\Optimize\{
    OptimizeCompletedHistoricActivityInstanceQueryCmd,
    OptimizeCompletedHistoricIncidentsQueryCmd,
    OptimizeCompletedHistoricProcessInstanceQueryCmd,
    OptimizeCompletedHistoricTaskInstanceQueryCmd,
    //OptimizeHistoricDecisionInstanceQueryCmd,
    OptimizeHistoricIdentityLinkLogQueryCmd,
    OptimizeHistoricUserOperationsLogQueryCmd,
    OptimizeHistoricVariableUpdateQueryCmd,
    OptimizeOpenHistoricIncidentsQueryCmd,
    OptimizeRunningHistoricActivityInstanceQueryCmd,
    OptimizeRunningHistoricProcessInstanceQueryCmd,
    OptimizeRunningHistoricTaskInstanceQueryCmd
};

class OptimizeService extends ServiceImpl
{
    public function getCompletedHistoricActivityInstances(
        ?string $finishedAfter,
        ?string $finishedAt,
        int $maxResults
    ): array {
        return $this->commandExecutor->execute(
            new OptimizeCompletedHistoricActivityInstanceQueryCmd($finishedAfter, $finishedAt, $maxResults),
            ...$this->commandExecutor->getState()
        );
    }

    public function getRunningHistoricActivityInstances(
        ?string $startedAfter,
        ?string $startedAt,
        int $maxResults
    ): array {
        return $this->commandExecutor->execute(
            new OptimizeRunningHistoricActivityInstanceQueryCmd($startedAfter, $startedAt, $maxResults),
            ...$this->commandExecutor->getState()
        );
    }

    public function getCompletedHistoricTaskInstances(
        ?string $finishedAfter,
        ?string $finishedAt,
        int $maxResults
    ): array {
        return $this->commandExecutor->execute(
            new OptimizeCompletedHistoricTaskInstanceQueryCmd($finishedAfter, $finishedAt, $maxResults),
            ...$this->commandExecutor->getState()
        );
    }

    public function getRunningHistoricTaskInstances(
        ?string $startedAfter,
        ?string $startedAt,
        int $maxResults
    ): array {
        return $this->commandExecutor->execute(
            new OptimizeRunningHistoricTaskInstanceQueryCmd($startedAfter, $startedAt, $maxResults),
            ...$this->commandExecutor->getState()
        );
    }

    public function getHistoricUserOperationLogs(
        ?string $occurredAfter,
        ?string $occurredAt,
        int $maxResults
    ): array {
        return $this->commandExecutor->execute(
            new OptimizeHistoricUserOperationsLogQueryCmd($occurredAfter, $occurredAt, $maxResults),
            ...$this->commandExecutor->getState()
        );
    }

    public function getHistoricIdentityLinkLogs(
        ?string $occurredAfter,
        ?string $occurredAt,
        int $maxResults
    ): array {
        return $this->commandExecutor->execute(
            new OptimizeHistoricIdentityLinkLogQueryCmd($occurredAfter, $occurredAt, $maxResults),
            ...$this->commandExecutor->getState()
        );
    }

    public function getCompletedHistoricProcessInstances(
        ?string $finishedAfter,
        ?string $finishedAt,
        int $maxResults
    ): array {
        return $this->commandExecutor->execute(
            new OptimizeCompletedHistoricProcessInstanceQueryCmd($finishedAfter, $finishedAt, $maxResults),
            ...$this->commandExecutor->getState()
        );
    }

    public function getRunningHistoricProcessInstances(
        ?string $startedAfter,
        ?string $startedAt,
        int $maxResults
    ): array {
        return $this->commandExecutor->execute(
            new OptimizeRunningHistoricProcessInstanceQueryCmd($startedAfter, $startedAt, $maxResults),
            ...$this->commandExecutor->getState()
        );
    }

    public function getHistoricVariableUpdates(
        ?string $occurredAfter,
        ?string $occurredAt,
        bool $excludeObjectValues,
        int $maxResults
    ): array {
        return $this->commandExecutor->execute(
            new OptimizeHistoricVariableUpdateQueryCmd($occurredAfter, $occurredAt, $excludeObjectValues, $maxResults),
            ...$this->commandExecutor->getState()
        );
    }

    public function getCompletedHistoricIncidents(
        ?string $finishedAfter,
        ?string $finishedAt,
        int $maxResults
    ): array {
        return $this->commandExecutor->execute(
            new OptimizeCompletedHistoricIncidentsQueryCmd($finishedAfter, $finishedAt, $maxResults),
            ...$this->commandExecutor->getState()
        );
    }

    public function getOpenHistoricIncidents(
        ?string $createdAfter,
        ?string $createdAt,
        int $maxResults
    ): array {
        return $this->commandExecutor->execute(
            new OptimizeOpenHistoricIncidentsQueryCmd($createdAfter, $createdAt, $maxResults),
            ...$this->commandExecutor->getState()
        );
    }

    public function getHistoricDecisionInstances(
        ?string $evaluatedAfter,
        ?string $evaluatedAt,
        int $maxResults
    ): array {
        return $this->commandExecutor->execute(
            new OptimizeHistoricDecisionInstanceQueryCmd($evaluatedAfter, $evaluatedAt, $maxResults),
            ...$this->commandExecutor->getState()
        );
    }
}
