<?php

namespace lx\process;

use lx\AbstractApplication;
use lx\I18nApplicationMap;
use lx\Language;
use lx\Router;
use lx\User;
use Exception;

/**
 * Class ProcessApplication
 * @package lx
 *
 * @property-read ProcessSupervisor $processSupervisor
 * @property-read Router $router
 * @property-read Language $language
 * @property-read I18nApplicationMap $i18nMap
 * @property-read User $user
 */
class ProcessApplication extends AbstractApplication
{
    /** @var string */
    private $serviceName;

    /** @var string */
    private $name;

    /** @var integer */
    private $index;

    /** @var bool */
    private $keepAlive;

    /** @var int */
    private $delay;

    /** @var bool */
    private $single;

    /**
     * ProcessApplication constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->serviceName = $config['serviceName'] ?? '';
        $this->name = $config['processName'] ?? '';
        $this->single = $config['single'] ?? false;

        $this->keepAlive = true;
        $this->delay = ($config['delay'] ?? 50) * 1000;

        if (array_key_exists('processIndex', $config)) {
            $this->index = $config['processIndex'];
        }

        parent::__construct($config);
    }

    /**
     * @return array
     */
    protected static function getDefaultComponents()
    {
        return array_merge(parent::getDefaultComponents(), [
            'processSupervisor' => ProcessSupervisor::class,
            'router' => Router::class,
            'language' => Language::class,
            'i18nMap' => I18nApplicationMap::class,
            'user' => User::class,
        ]);
    }


    /*******************************************************************************************************************
     * LIFE CYCLE
     ******************************************************************************************************************/

    protected function beforeProcess()
    {
        // pass
    }

    protected function process()
    {
        // pass
    }

    /**
     * @param mixed $message
     */
    protected function processMessage($message)
    {
        // pass
    }

    /**
     * @param mixed $request
     * @return mixed
     */
    protected function processRequest($request)
    {
        $controller = $this->requestController;
        if (!$controller) {
            return null;
        }

        return $controller->run($request);
    }

    protected function afterProcess()
    {
        // pass
    }


    /*******************************************************************************************************************
     * PUBLIC
     ******************************************************************************************************************/

    /**
     * @return string
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param integer $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }

    /**
     * @return integer
     */
    public function getIndex()
    {
        return $this->index;
    }

    public function run()
    {
        $this->beforeProcess();

        while ($this->keepAlive) {
            usleep($this->delay);

            $this->process();

            $messages = $this->processSupervisor->readMessagesForProcessApplication($this->name, $this->index, true);
            foreach ($messages as $messageData) {
                $type = $messageData['type'];
                $message = $messageData['data'];
                if ($type == ProcessConst::MESSAGE_TYPE_SPECIAL) {
                    if ($message == ProcessConst::DIRECTIVE_STOP) {
                        $this->keepAlive = false;
                        break;
                    }
                } elseif ($type == ProcessConst::MESSAGE_TYPE_REQUEST) {
                    $response = $this->processRequest($message);
                    $this->processSupervisor->sendResponseFromProcessApplication(
                        $this->name,
                        $this->index,
                        $messageData['meta'],
                        $response
                    );
                } elseif ($type == ProcessConst::MESSAGE_TYPE_COMMON) {
                    $this->processMessage($message);
                }
            }
        }

        $this->afterProcess();
    }


    /*******************************************************************************************************************
     * PROTECTED
     ******************************************************************************************************************/

    protected function init()
    {
        $this->checkSingleConstraint();
        if (isset($this->index)) {
            $this->processSupervisor->reborn($this);
        } else {
            $this->processSupervisor->register($this);
        }
    }

    private function checkSingleConstraint()
    {
        if (!$this->single) {
            return;
        }

        $statuses = $this->processSupervisor->getServiceProcesses(
            $this->serviceName,
            $this->name
        );

        foreach ($statuses as $status) {
            if ($status['status'] == ProcessConst::PROCESS_STATUS_ACTIVE) {
                \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                    '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                    'msg' => "There was attempt to run process '{$this->name}' from service '{$this->serviceName}' "
                        ."wich is singleton but the same process is already running",
                ]);

                throw new Exception('The same process is already running!');
            }
        }
    }
}
