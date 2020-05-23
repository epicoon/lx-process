<?php

namespace lx\process;

/**
 * Class ProcessResponse
 * @package lx\process
 */
class ProcessResponse
{
    /** @var mixed */
    private $data = null;

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return !isset($this->data);
    }

    /**
     * @param mixed $data
     */
    public function init($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
