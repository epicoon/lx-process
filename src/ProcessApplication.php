<?php

namespace lx\process;

use lx\AbstractApplication;
use lx\I18nApplicationMap;
use lx\Language;
use lx\Router;
use lx\UserInterface;
use Exception;

/**
 * Class ProcessApplication
 * @package lx
 *
 * @property-read ProcessSupervisor $processSupervisor
 * @property-read Router $router
 * @property-read Language $language
 * @property-read I18nApplicationMap $i18nMap
 * @property-read UserInterface $user
 */
class ProcessApplication extends AbstractApplication
{
    private string $serviceName;
    private string $name;
    private int $index;
    private bool $keepAlive;
    private int $delay;
    private bool $single;

    public function __construct(array $config = [])
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

        //TODO путь можно будет брать из $config['logDirectory'] после рефакторинга супервизора, см. Service::runProcess
        $this->logger->init([
            'path' => '@site/log/process/' . $this->name . '_' . $this->index,
        ]);
    }

    protected static function getDefaultComponents(): array
    {
        return array_merge(parent::getDefaultComponents(), [
            'processSupervisor' => ProcessSupervisor::class,
            'router' => Router::class,
            'language' => Language::class,
            'i18nMap' => I18nApplicationMap::class,
            'user' => UserInterface::class,
        ]);
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * LIFE CYCLE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    protected function beforeProcess(): void
    {
        // pass
    }

    protected function process(): void
    {
        // pass
    }

    /**
     * @param mixed $message
     */
    protected function processMessage($message): void
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

    protected function afterProcess(): void
    {
        // pass
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PUBLIC
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setIndex(int $index): void
    {
        $this->index = $index;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function run(): void
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


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PROTECTED and PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    protected function init(): void
    {
        $this->checkSingleConstraint();
        if (isset($this->index)) {
            $this->processSupervisor->reborn($this);
        } else {
            $this->processSupervisor->register($this);
        }
    }

    /**
     * @throws Exception
     */
    private function checkSingleConstraint(): void
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
