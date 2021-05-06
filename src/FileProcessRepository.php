<?php

namespace lx\process;

use lx\File;
use lx\DataFile;
use lx\process\interfaces\ProcessRepositoryInterface;

class FileProcessRepository implements ProcessRepositoryInterface
{
    private ProcessMap $map;

    public function getMap(): ProcessMap
    {
        if (!isset($this->map)) {
            $this->loadMap();
        }

        return $this->map;
    }

    public function renew(): void
    {
        $file = $this->getMapFile();
        if (!$file->exists()) {
            $file->getParentDir()->make();
        }

        $file->put($this->map->toArray());
    }

    public function sendMessageToProcess(string $processName, int $processIndex, string $message): void
    {
        $file = $this->getProcessInputFile($processName, $processIndex);
        $file->put('<lx-process-message-begin>' . $message . '<lx-process-message-end>', FILE_APPEND);
    }

    public function getProcessInputMessages(string $processName, int $processIndex, bool $clear = false): array
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

    /**
     * @param mixed $message
     */
    public function sendResponseFromProcess(
        string $processName,
        int $processIndex,
        string $responseCode,
        $message
    ): void
    {
        $messageString = serialize($message);
        $file = $this->getProcessResponseFile($processName, $processIndex, $responseCode);
        $file->put($messageString);
    }

    public function getProcessResponse(string $processName, int $processIndex, string $responseCode): ProcessResponse
    {
        $response = new ProcessResponse();
        $file = $this->getProcessResponseFile($processName, $processIndex, $responseCode);
        if (!$file->exists()) {
            return $response;
        }

        $messageString = $file->get();
        $file->remove();
        $message = unserialize($messageString);
        $response->init($message);

        return $response;
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function loadMap(): void
    {
        $file = $this->getMapFile();
        $map = $file->exists() ? $file->get() : [];
        $this->map = new ProcessMap($this, $map);
    }

    private function getMapFile(): DataFile
    {
        $path = \lx::$conductor->getSystemPath('process');
        return new DataFile($path . '/map.json');
    }

    private function getProcessInputFile(string $processName, int $processIndex): File
    {
        $path = \lx::$conductor->getSystemPath('process');
        return new File($path . '/input_' . $processName . '_' . $processIndex);
    }

    private function getProcessResponseFile(string $processName, int $processIndex, string $responseCode): File
    {
        $path = \lx::$conductor->getSystemPath('process');
        return new File($path . '/response_' . $processName . '_' . $processIndex . '_' . $responseCode);
    }
}
