<?php

namespace lx\process;

use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ObjectTrait;

/**
 * Class ProcessRequestController
 * @package lx\process
 */
class ProcessRequestController implements FusionComponentInterface
{
    use ObjectTrait;
    use FusionComponentTrait;

    /**
     * @param array $request
     * @return mixed
     */
    public function run($request)
    {
        $action = $request['action'] ?? null;
        if (!$action) {
            return null;
        }

        $method = $this->getMethodName($action);
        if (!$method) {
            return null;
        }

        return call_user_func_array([$this, $method], $request['params'] ?? []);
    }

    /**
     * @param string $actionName
     * @return string|null
     */
    protected function getMethodName($actionName)
    {
        if ($actionName == 'run' || $actionName == 'getMethodName') {
            return null;
        }

        if (!method_exists($this, $actionName)) {
            return null;
        }

        return $actionName;
    }
}
