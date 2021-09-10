<?php

namespace lx\process;

class ProcessHelper
{
    public static function getCurrentPids(): array
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
