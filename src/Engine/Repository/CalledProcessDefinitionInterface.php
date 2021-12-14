<?php

namespace BpmPlatform\Engine\Repository;

interface CalledProcessDefinitionInterface extends ProcessDefinitionInterface
{
    public function getCallingProcessDefinitionId(): string;

    public function getCalledFromActivityIds(): array;
}
