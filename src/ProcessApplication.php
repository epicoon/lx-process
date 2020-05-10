<?php

namespace lx\process;

use lx\AbstractApplication;
use lx\FusionInterface;
use lx\FusionTrait;
use lx\I18nApplicationMap;
use lx\Language;
use lx\Router;
use lx\User;

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
class ProcessApplication extends AbstractApplication implements FusionInterface
{
    use FusionTrait;

    /** @var string */
    private $serviceName;

    /** @var string */
    private $name;

    /** @var integer */
    private $index;

    /** @var bool */
    private $keepAlive;

    /**
     * ProcessApplication constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->keepAlive = true;
        $this->delay = ($config['delay'] ?? 2000) * 1000;

        $this->serviceName = $config['serviceName'] ?? '';
        $this->name = $config['processName'] ?? '';

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

    protected function beforeRun()
    {
        // pass
    }

    protected function beforeProcess()
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

    protected function afterProcess()
    {
        // pass
    }

    protected function beforeClose()
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
        $this->beforeRun();

        while ($this->keepAlive) {
            usleep($this->delay);

            $this->beforeProcess();

            $messages = $this->processSupervisor->getProcessInputMessages($this->name, $this->index, true);
            foreach ($messages as $messageData) {
                $type = $messageData['type'];
                $message = $messageData['data'];
                if ($type == ProcessConst::MESSAGE_TYPE_SPECIAL) {
                    if ($message == ProcessConst::DIRECTIVE_STOP) {
                        $this->keepAlive = false;
                        break;
                    }
                } elseif ($type == ProcessConst::MESSAGE_TYPE_COMMON) {
                    $this->processMessage($message);
                }
            }

            $this->afterProcess();
        }

        $this->beforeClose();
    }


    /*******************************************************************************************************************
     * PROTECTED
     ******************************************************************************************************************/

    protected function init()
    {
        if (isset($this->index)) {
            $this->processSupervisor->reborn($this);
        } else {
            $this->processSupervisor->register($this);
        }
    }
}
