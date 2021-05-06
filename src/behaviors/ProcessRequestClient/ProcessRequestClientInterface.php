<?php

namespace lx\process\behaviors\ProcessRequestClient;

use lx\process\Process;

interface ProcessRequestClientInterface
{
    public function getProcess(): Process;
}
