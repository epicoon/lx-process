<?php

namespace lx\process;

use lx\AbstractApplication;
use lx\ApplicationI18nMap;
use lx\AuthenticationInterface;
use lx\AuthorizationInterface;
use lx\ErrorHelper;
use lx\Language;
use lx\Router;
use lx\UserInterface;
use lx\UserManagerInterface;
use Exception;
use Throwable;

/**
 * @property-read ProcessSupervisor $processSupervisor
 */
class ProcessApplication extends AbstractApplication
{
    private string $serviceName;
    private string $name;
    private int $index;
    private bool $keepAlive;
    private int $delay;
    private bool $single;

    public function __construct(iterable $config = [])
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
        $this->logger->setFilePath('@site/log/process/' . $this->name . '_' . $this->index);
    }

    protected function init(): void
    {
        $this->checkSingleConstraint();
        if (isset($this->index)) {
            $this->processSupervisor->reborn($this);
        } else {
            $this->processSupervisor->register($this);
        }
    }

    public function getDefaultFusionComponents(): array
    {
        return array_merge(parent::getDefaultFusionComponents(), [
            'processSupervisor' => ProcessSupervisor::class,
            'router' => Router::class,
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
        //TODO
        $router = $this->router;
        if (!$router) {
            return null;
        }

        return $router->route($request);
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
            try {
                $this->iteration();
            } catch (Throwable $e) {
                $this->log(ErrorHelper::renderErrorString($e), 'error');
            }
        }

        $this->afterProcess();
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function iteration(): void
    {
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
