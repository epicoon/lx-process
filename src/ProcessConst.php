<?php

namespace lx\process;

/**
 * Class ProcessConst
 * @package lx\process
 */
class ProcessConst
{
    const PROCESS_STATUS_ACTIVE = 1;
    const PROCESS_STATUS_CLOSED = 2;
    const PROCESS_STATUS_CRASHED = 3;

    const MESSAGE_TYPE_SPECIAL = 11;
    const MESSAGE_TYPE_COMMON = 12;

    const DIRECTIVE_STOP = 21;
}
