<?php

namespace Jabe\Repository;

use Jabe\Application\ProcessApplicationRegistrationInterface;

interface ProcessApplicationDeploymentInterface
{
    public const PROCESS_APPLICATION_DEPLOYMENT_SOURCE = "process application";

    /**
     * @return ProcessApplicationRegistrationInterface the ProcessApplicationRegistration performed for this process application deployment.
     */
    public function getProcessApplicationRegistration(): ProcessApplicationRegistrationInterface;
}
