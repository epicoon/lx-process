<?php

namespace lx\process;

/**
 * Class ProcessHelper
 * @package lx\process
 */
class ProcessHelper
{
    /**
     * @return array
     */
    public static function getCurrentPids()
    {
        $currentProcesses = \lx::exec('ps -ax');
        $currentProcesses = explode(PHP_EOL, $currentProcesses);
        $result = [];
        foreach ($currentProcesses as $key => $row) {
            preg_match('/^\s*(\d+)/', $row, $match);
            if (empty($match)) {
                continue;
            }

            $result[] = (int)$match[1];
        }

        return $result;
    }
}
