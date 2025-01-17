<?php

namespace Jabe\Management;

interface UpdateJobSuspensionStateTenantBuilderInterface
{
    /**
     * Specify that the process definition belongs to no tenant.
     *
     * @return UpdateJobSuspensionStateBuilderInterface the builder
     */
    public function processDefinitionWithoutTenantId(): UpdateJobSuspensionStateBuilderInterface;

    /**
     * Specify the id of the tenant the process definition belongs to.
     *
     * @param tenantId
     *          the id of the tenant
     * @return UpdateJobSuspensionStateBuilderInterface the builder
     */
    public function processDefinitionTenantId(?string $tenantId): UpdateJobSuspensionStateBuilderInterface;
}
