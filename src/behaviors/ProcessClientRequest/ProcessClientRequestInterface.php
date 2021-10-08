<?php

namespace lx\process\behaviors\ProcessClientRequest;

use lx\process\Process;

interface ProcessClientRequestInterface
{
    public function getProcess(): ?Process;
}
