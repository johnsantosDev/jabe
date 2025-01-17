<?php

namespace Jabe\Impl;

use Jabe\Impl\Cmd\CommandLogger;
use Jabe\Impl\Interceptor\{
    CommandContext,
    CommandExecutorInterface
};
use Jabe\Impl\Persistence\Entity\VariableInstanceEntity;
use Jabe\Impl\Util\{
    CompareUtil,
    EnsureUtil
};
use Jabe\Impl\variable\Serializer\AbstractTypedValueSerializer;
use Jabe\Runtime\{
    VariableInstanceInterface,
    VariableInstanceQueryInterface
};

class VariableInstanceQueryImpl extends AbstractVariableQueryImpl implements VariableInstanceQueryInterface
{
    //private final static CommandLogger LOG = ProcessEngineLogger.CMD_LOGGER;

    protected $variableId;
    protected $variableName;
    protected $variableNames = [];
    protected $variableNameLike;
    protected $executionIds = [];
    protected $processInstanceIds = [];
    //protected $caseExecutionIds = [];
    //protected $caseInstanceIds = [];
    protected $taskIds = [];
    protected $batchIds = [];
    protected $variableScopeIds = [];
    protected $activityInstanceIds = [];
    protected $tenantIds = [];

    protected bool $isByteArrayFetchingEnabled = true;
    protected bool $isCustomObjectDeserializationEnabled = true;

    public function __construct(?CommandExecutorInterface $commandExecutor = null)
    {
        parent::__construct($commandExecutor);
    }

    public function __serialize(): array
    {
        return [
            'variableId' => $this->variableId,
            'variableName' => $this->variableName,
            'variableNames' => $this->variableNames,
            'variableNameLike' => $this->variableNameLike,
            'executionIds' => $this->executionIds,
            'processInstanceIds' => $this->processInstanceIds,
            'taskIds' => $this->taskIds,
            'batchIds' => $this->batchIds,
            'variableScopeIds' => $this->variableScopeIds,
            'activityInstanceIds' => $this->activityInstanceIds,
            'tenantIds' => $this->tenantIds,
            'isByteArrayFetchingEnabled' => $this->isByteArrayFetchingEnabled,
            'isCustomObjectDeserializationEnabled' => $this->isCustomObjectDeserializationEnabled
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->variableId = $data['variableId'];
        $this->variableName = $data['variableName'];
        $this->variableNames = $data['variableNames'];
        $this->variableNameLike = $data['variableNameLike'];
        $this->executionIds = $data['executionIds'];
        $this->processInstanceIds = $data['processInstanceIds'];
        $this->taskIds = $data['taskIds'];
        $this->batchIds = $data['batchIds'];
        $this->variableScopeIds = $data['variableScopeIds'];
        $this->activityInstanceIds = $data['activityInstanceIds'];
        $this->tenantIds = $data['tenantIds'];
        $this->isByteArrayFetchingEnabled = $data['isByteArrayFetchingEnabled'];
        $this->isCustomObjectDeserializationEnabled = $data['isCustomObjectDeserializationEnabled'];
    }

    public function variableId(?string $id): VariableInstanceQueryInterface
    {
        EnsureUtil::ensureNotNull("id", "id", $id);
        $this->variableId = $id;
        return $this;
    }

    public function variableName(?string $variableName): VariableInstanceQueryInterface
    {
        $this->variableName = $variableName;
        return $this;
    }

    public function variableNameIn(array $variableNames): VariableInstanceQueryInterface
    {
        $this->variableNames = $variableNames;
        return $this;
    }

    public function variableNameLike(?string $variableNameLike): VariableInstanceQueryInterface
    {
        $this->variableNameLike = $variableNameLike;
        return $this;
    }

    public function executionIdIn(array $executionIds): VariableInstanceQueryInterface
    {
        $this->executionIds = $executionIds;
        return $this;
    }

    public function processInstanceIdIn(array $processInstanceIds): VariableInstanceQueryInterface
    {
        $this->processInstanceIds = $processInstanceIds;
        return $this;
    }

    /*public VariableInstanceQueryInterface caseExecutionIdIn(array $caseExecutionIds) {
        $this->caseExecutionIds = caseExecutionIds;
        return $this;
    }

    public VariableInstanceQueryInterface caseInstanceIdIn(array $caseInstanceIds) {
        $this->caseInstanceIds = caseInstanceIds;
        return $this;
    }*/

    public function taskIdIn(array $taskIds): VariableInstanceQueryInterface
    {
        $this->taskIds = $taskIds;
        return $this;
    }

    public function batchIdIn(array $batchIds): VariableInstanceQueryInterface
    {
        $this->batchIds = $batchIds;
        return $this;
    }

    public function variableScopeIdIn(array $variableScopeIds): VariableInstanceQueryInterface
    {
        $this->variableScopeIds = $variableScopeIds;
        return $this;
    }

    public function activityInstanceIdIn(array $activityInstanceIds): VariableInstanceQueryInterface
    {
        $this->activityInstanceIds = $activityInstanceIds;
        return $this;
    }

    public function disableBinaryFetching(): VariableInstanceQueryInterface
    {
        $this->isByteArrayFetchingEnabled = false;
        return $this;
    }

    public function disableCustomObjectDeserialization(): VariableInstanceQueryInterface
    {
        $this->isCustomObjectDeserializationEnabled = false;
        return $this;
    }

    public function tenantIdIn(array $tenantIds): VariableInstanceQueryInterface
    {
        EnsureUtil::ensureNotNull("tenantIds", "tenantIds", $tenantIds);
        $this->tenantIds = $tenantIds;
        return $this;
    }

    // ordering ////////////////////////////////////////////////////

    public function orderByVariableName(): VariableInstanceQueryInterface
    {
        $this->orderBy(VariableInstanceQueryProperty::variableName());
        return $this;
    }

    public function orderByVariableType(): VariableInstanceQueryInterface
    {
        $this->orderBy(VariableInstanceQueryProperty::variableType());
        return $this;
    }

    public function orderByActivityInstanceId(): VariableInstanceQueryInterface
    {
        $this->orderBy(VariableInstanceQueryProperty::activityInstanceId());
        return $this;
    }

    public function orderByTenantId(): VariableInstanceQueryInterface
    {
        $this->orderBy(VariableInstanceQueryProperty::tenantId());
        return $this;
    }

    protected function hasExcludingConditions(): bool
    {
        return parent::hasExcludingConditions() || CompareUtil::elementIsNotContainedInArray($this->variableName, $this->variableNames);
    }

    // results ////////////////////////////////////////////////////

    public function executeCount(CommandContext $commandContext): int
    {
        $this->checkQueryOk();
        $this->ensureVariablesInitialized();
        return $commandContext
            ->getVariableInstanceManager()
            ->findVariableInstanceCountByQueryCriteria($this);
    }

    public function executeList(CommandContext $commandContext, ?Page $page): array
    {
        $this->checkQueryOk();
        $this->ensureVariablesInitialized();
        $result = $commandContext
            ->getVariableInstanceManager()
            ->findVariableInstanceByQueryCriteria($this, $page);

        if ($result === null) {
            return $result;
        }

        // iterate over the result array to initialize the value and serialized value of the variable
        foreach ($result as $variableInstance) {
            $variableInstanceEntity = $variableInstance;
            if ($this->shouldFetchValue($variableInstanceEntity)) {
                try {
                    $variableInstanceEntity->getTypedValue($this->isCustomObjectDeserializationEnabled);
                } catch (\Exception $t) {
                    // do not fail if one of the variables fails to load
                    //LOG.exceptionWhileGettingValueForVariable(t);
                }
            }
        }
        return $result;
    }

    protected function shouldFetchValue(VariableInstanceEntity $entity): bool
    {
        // do not fetch values for byte arrays eagerly (unless requested by the user)
        return $this->isByteArrayFetchingEnabled
            || !in_array($entity->getSerializer()->getType()->getName(), AbstractTypedValueSerializer::BINARY_VALUE_TYPES);
    }

    // getters ////////////////////////////////////////////////////

    public function getVariableId(): ?string
    {
        return $this->variableId;
    }

    public function getVariableName(): ?string
    {
        return $this->variableName;
    }

    public function getVariableNames(): array
    {
        return $this->variableNames;
    }

    public function getVariableNameLike(): ?string
    {
        return $this->variableNameLike;
    }

    public function getExecutionIds(): array
    {
        return $this->executionIds;
    }

    public function getProcessInstanceIds(): array
    {
        return $this->processInstanceIds;
    }

    /*public String[] getCaseExecutionIds() {
        return caseExecutionIds;
    }

    public String[] getCaseInstanceIds() {
        return caseInstanceIds;
    }*/

    public function getTaskIds(): array
    {
        return $this->taskIds;
    }

    public function getBatchIds(): array
    {
        return $this->batchIds;
    }

    public function getVariableScopeIds(): array
    {
        return $this->variableScopeIds;
    }

    public function getActivityInstanceIds(): array
    {
        return $this->activityInstanceIds;
    }
}
