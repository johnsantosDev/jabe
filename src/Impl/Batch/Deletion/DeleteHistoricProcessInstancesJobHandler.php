<?php

namespace Jabe\Impl\Batch\Deletion;

use Jabe\Batch\BatchInterface;
use Jabe\Impl\Batch\{
    AbstractBatchJobHandler,
    BatchConfiguration,
    BatchJobConfiguration,
    BatchJobContext,
    BatchJobDeclaration
};
use Jabe\Impl\Cmd\DeleteHistoricProcessInstancesCmd;
use Jabe\Impl\Interceptor\CommandContext;
use Jabe\Impl\JobExecutor\{
    JobDeclaration,
    JobHandlerConfigurationInterface
};
use Jabe\Impl\Json\JsonObjectConverter;
use Jabe\Impl\Persistence\Entity\{
    ByteArrayEntity,
    ExecutionEntity,
    MessageEntity
};

class DeleteHistoricProcessInstancesJobHandler extends AbstractBatchJobHandler
{
    public static $JOB_DECLARATION;

    public function getType(): ?string
    {
        return BatchInterface::TYPE_HISTORIC_PROCESS_INSTANCE_DELETION;
    }

    protected function getJsonConverterInstance(): JsonObjectConverter
    {
        return DeleteHistoricProcessInstanceBatchConfigurationJsonConverter::instance();
    }

    public function getJobDeclaration(): JobDeclaration
    {
        if (self::$JOB_DECLARATION === null) {
            self::$JOB_DECLARATION = new BatchJobDeclaration(BatchInterface::TYPE_HISTORIC_PROCESS_INSTANCE_DELETION);
        }
        return self::$JOB_DECLARATION;
    }

    protected function createJobConfiguration(BatchConfiguration $configuration, array $processIdsForJob): BatchConfiguration
    {
        return new BatchConfiguration($processIdsForJob, $configuration->isFailIfNotExists());
    }

    public function execute(JobHandlerConfigurationInterface $configuration, ExecutionEntity $execution, CommandContext $commandContext, ?string $tenantId): void
    {
        $configurationEntity = $commandContext
            ->getDbEntityManager()
            ->selectById(ByteArrayEntity::class, $configuration->getConfigurationByteArrayId());

        $batchConfiguration = $this->readConfiguration($configurationEntity->getBytes());

        $commandContext->executeWithOperationLogPrevented(
            new DeleteHistoricProcessInstancesCmd(
                $batchConfiguration->getIds(),
                $batchConfiguration->isFailIfNotExists()
            )
        );

        $commandContext->getByteArrayManager()->delete($configurationEntity);
    }
}
