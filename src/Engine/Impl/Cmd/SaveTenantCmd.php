<?php

namespace Jabe\Engine\Impl\Cmd;

use Jabe\Engine\Identity\TenantInterface;
use Jabe\Engine\Impl\Interceptor\{
    CommandInterface,
    CommandContext
};
use Jabe\Engine\Impl\Util\EnsureUtil;

class SaveTenantCmd extends AbstractWritableIdentityServiceCmd implements CommandInterface, \Serializable
{
    protected $tenant;

    public function __construct(TenantInterface $tenant)
    {
        $this->tenant = $tenant;
    }

    public function serialize()
    {
        return json_encode([
            'tenant' => serialize($this->tenant)
        ]);
    }

    public function unserialize($data)
    {
        $json = json_decode($data);
        $this->tenant = unserialize($json->tenant);
    }

    protected function executeCmd(CommandContext $commandContext)
    {
        EnsureUtil::ensureNotNull("tenant", "tenant", $this->tenant);
        EnsureUtil::ensureWhitelistedResourceId($commandContext, "Tenant", $this->tenant->getId());

        $operationResult = $commandContext
            ->getWritableIdentityProvider()
            ->saveTenant($this->tenant);

        $commandContext->getOperationLogManager()->logTenantOperation($operationResult, $this->tenant->getId());

        return null;
    }
}
