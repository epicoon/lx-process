<?php

namespace lx\process;

use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ObjectTrait;

class ProcessRequestController implements FusionComponentInterface
{
    use FusionComponentTrait;

    /**
     * @return mixed
     */
    public function run(array $request)
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

    protected function getMethodName(string $actionName): ?string
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
