<?php

namespace BpmPlatform\Model\Bpmn\Instance\Di;

use BpmPlatform\Model\Bpmn\Instance\Dc\BoundsInterface;

interface LabelInterface extends NodeInterface
{
    public function getBounds(): BoundsInterface;

    public function setBounds(BoundsInterface $bounds): void;
}