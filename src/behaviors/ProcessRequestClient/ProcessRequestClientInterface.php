<?php

namespace lx\process\behaviors\ProcessRequestClient;

use lx\process\Process;

/**
 * Interface ProcessRequestClientInterface
 * @package lx\process\behaviors\ProcessRequestClient
 */
interface ProcessRequestClientInterface
{
    /**
     * @return Process
     */
    public function getProcess();
}
