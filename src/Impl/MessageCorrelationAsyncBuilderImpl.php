<?php

namespace Jabe\Impl;

use Jabe\Batch\BatchInterface;
use Jabe\History\HistoricProcessInstanceQueryInterface;
use Jabe\Impl\Interceptor\CommandExecutorInterface;
use Jabe\Impl\Util\EnsureUtil;
use Jabe\Runtime\{
    MessageCorrelationAsyncBuilderInterface,
    ProcessInstanceQueryInterface
};
use Jabe\Variable\VariableMapInterface;
use Jabe\Variable\Impl\VariableMapImpl;

class MessageCorrelationAsyncBuilderImpl implements MessageCorrelationAsyncBuilderInterface
{
    protected $commandExecutor;

    protected $messageName;
    protected $payloadProcessInstanceVariables;

    protected $processInstanceIds = [];
    protected $processInstanceQuery;
    protected $historicProcessInstanceQuery;

    public function __construct($commandExecutorOrName, ?string $messageName = null)
    {
        if ($commandExecutorOrName instanceof CommandExecutorInterface) {
            $this->messageName = $messageName;
            EnsureUtil::ensureNotNull("commandExecutor", "commandExecutor", $commandExecutorOrName);
            $this->commandExecutor = $commandExecutorOrName;
        } elseif (is_string($commandExecutorOrName)) {
            $this->messageName = $commandExecutorOrName;
        }
    }

    public function processInstanceIds(array $ids): MessageCorrelationAsyncBuilderInterface
    {
        EnsureUtil::ensureNotNull("processInstanceIds", "ids", $ids);
        $this->processInstanceIds = $ids;
        return $this;
    }

    public function processInstanceQuery(ProcessInstanceQueryInterface $processInstanceQuery): MessageCorrelationAsyncBuilderInterface
    {
        EnsureUtil::ensureNotNull("processInstanceQuery", "processInstanceQuery", $processInstanceQuery);
        $this->processInstanceQuery = $processInstanceQuery;
        return $this;
    }

    public function historicProcessInstanceQuery(HistoricProcessInstanceQueryInterface $historicProcessInstanceQuery): MessageCorrelationAsyncBuilderInterface
    {
        EnsureUtil::ensureNotNull("historicProcessInstanceQuery", "historicProcessInstanceQuery", $historicProcessInstanceQuery);
        $this->historicProcessInstanceQuery = $historicProcessInstanceQuery;
        return $this;
    }

    public function setVariable(?string $variableName, $variableValue): MessageCorrelationAsyncBuilderInterface
    {
        EnsureUtil::ensureNotNull("variableName", "variableName", $variableName);
        $this->ensurePayloadProcessInstanceVariablesInitialized();
        $this->payloadProcessInstanceVariables->put($variableName, $variableValue);
        return this;
    }

    public function setVariables(array $variables): MessageCorrelationAsyncBuilderInterface
    {
        if (!empty($variables)) {
            $this->ensurePayloadProcessInstanceVariablesInitialized();
            $this->payloadProcessInstanceVariables->putAll($variables);
        }
        return $this;
    }

    protected function ensurePayloadProcessInstanceVariablesInitialized(): void
    {
        if ($this->payloadProcessInstanceVariables === null) {
            $this->payloadProcessInstanceVariables = new VariableMapImpl();
        }
    }

    public function correlateAllAsync(): BatchInterface
    {
        return $this->commandExecutor->execute(new CorrelateAllMessageBatchCmd($this));
    }

    // getters //////////////////////////////////

    public function getCommandExecutor(): CommandExecutorInterface
    {
        return $this->commandExecutor;
    }

    public function getMessageName(): ?string
    {
        return $this->messageName;
    }

    public function getProcessInstanceIds(): array
    {
        return $this->processInstanceIds;
    }

    public function getProcessInstanceQuery(): ProcessInstanceQueryInterface
    {
        return $this->processInstanceQuery;
    }

    public function getHistoricProcessInstanceQuery(): HistoricProcessInstanceQueryInterface
    {
        return $this->historicProcessInstanceQuery;
    }

    public function getPayloadProcessInstanceVariables(): ?VariableMapInterface
    {
        return $this->payloadProcessInstanceVariables;
    }
}
