<?php

namespace lx\process;

use lx\File;
use lx\DataFile;
use lx\process\interfaces\ProcessRepositoryInterface;

/**
 * Class FileProcessRepository
 * @package lx
 */
class FileProcessRepository implements ProcessRepositoryInterface
{
    /** @var ProcessMap */
    private ProcessMap $map;

    /**
     * @return ProcessMap
     */
    public function getMap() : ProcessMap
    {
        if (!isset($this->map)) {
            $this->loadMap();
        }

        return $this->map;
    }

    public function renew()
    {
        $file = $this->getMapFile();
        if (!$file->exists()) {
            $file->getParentDir()->make();
        }

        $file->put($this->map->toArray());
    }

    /**
     * @param string $processName
     * @param integer $processIndex
     * @param string $message
     */
    public function sendMessageToProcess($processName, $processIndex, $message)
    {
        $file = $this->getProcessInputFile($processName, $processIndex);
        $file->put('<lx-process-message-begin>' . $message . '<lx-process-message-end>', FILE_APPEND);
    }

    /**
     * @param string $processName
     * @param integer $processIndex
     * @param bool $clear
     * @return array
     */
    public function getProcessInputMessages($processName, $processIndex, $clear = false)
    {
        $file = $this->getProcessInputFile($processName, $processIndex);
        if (!$file->exists()) {
            return [];
        }

        $content = $file->get();
        preg_match_all(
            '/<lx-process-message-begin>(.+?)<lx-process-message-end>/',
            $content,
            $matches
        );

        if ($clear) {
            $file->remove();
        }

        if (empty($matches[0])) {
            return [];
        }

        return $matches[1];
    }


    /*******************************************************************************************************************
     * PRIVATE
     ******************************************************************************************************************/

    private function loadMap()
    {
        $file = $this->getMapFile();
        $map = $file->exists() ? $file->get() : [];
        $this->map = new ProcessMap($map);
    }

    /**
     * @return DataFile
     */
    private function getMapFile()
    {
        $path = \lx::$conductor->getSystemPath('process');
        return new DataFile($path . '/map.json');
    }

    /**
     * @param string $processName
     * @param integer $processIndex
     * @return File
     */
    private function getProcessInputFile($processName, $processIndex)
    {
        $path = \lx::$conductor->getSystemPath('process');
        return new File($path . '/input_' . $processName . '_' . $processIndex);
    }
}
