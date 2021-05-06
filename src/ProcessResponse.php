<?php

namespace lx\process;

class ProcessResponse
{
    /** @var mixed */
    private $data = null;

    public function isEmpty(): bool
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
