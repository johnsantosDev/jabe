<?php

namespace BpmPlatform\Engine\Impl\Persistence\Entity;

use BpmPlatform\Engine\Batch\BatchInterface;
use BpmPlatform\Engine\Impl\Page;
use BpmPlatform\Engine\Impl\Batch\{
    BatchEntity,
    BatchQueryImpl
};
use BpmPlatform\Engine\Impl\Db\ListQueryParameterObject;
use BpmPlatform\Engine\Impl\Persistence\AbstractManager;

class BatchManager extends AbstractManager
{
    public function insertBatch(BatchEntity $batch): void
    {
        $batch->setCreateUserId($this->getCommandContext()->getAuthenticatedUserId());
        $this->getDbEntityManager()->insert($batch);
    }

    public function findBatchById(string $id): BatchEntity
    {
        return $this->getDbEntityManager()->selectById(BatchEntity::class, $id);
    }

    public function findBatchCountByQueryCriteria(BatchQueryImpl $batchQuery): int
    {
        $this->configureQuery($batchQuery);
        return $this->getDbEntityManager()->selectOne("selectBatchCountByQueryCriteria", $batchQuery);
    }

    public function findBatchesByQueryCriteria(BatchQueryImpl $batchQuery, Page $page): array
    {
        $this->configureQuery($batchQuery);
        return $this->getDbEntityManager()->selectList("selectBatchesByQueryCriteria", $batchQuery, $page);
    }

    public function updateBatchSuspensionStateById(string $batchId, SuspensionState $suspensionState): void
    {
        $parameters = [];
        $parameters["batchId"] = $batchId;
        $parameters["suspensionState"] = $suspensionState->getStateCode();

        $queryParameter = new ListQueryParameterObject();
        $queryParameter->setParameter($parameters);

        $this->getDbEntityManager()->update(BatchEntity::class, "updateBatchSuspensionStateByParameters", $queryParameter);
    }

    protected function configureQuery(BatchQueryImpl $batchQuery): void
    {
        $this->getAuthorizationManager()->configureBatchQuery($batchQuery);
        $this->getTenantManager()->configureQuery($batchQuery);
    }
}
