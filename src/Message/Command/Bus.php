<?php

namespace API\Message\Command;

use Exception;
use ReflectionParameter;
use API\Feature\KernelAccess;
use League\Tactician\CommandBus as TacticianBus;
use League\Tactician\Handler\Locator\InMemoryLocator;
use Interop\Container\ContainerInterface as Container;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Exception\MissingHandlerException;
use League\Tactician\Handler\MethodNameInflector\InvokeInflector;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;

class Bus extends TacticianBus
{
    use KernelAccess;

    /**
     * Build the bus.
     */
    public function __construct(Container $container, array $middlewares = [])
    {
        $this->container           = $container;
        $this->init                = false;
        $this->middlewares         = $middlewares;
        $this->locator             = new InMemoryLocator();
        $this->extractor           = new ClassNameExtractor();
        $this->inflector           = new InvokeInflector();
        $this->dispatched_commands = [];
    }

    /**
     * Dispatch a command to its appropriate handler.
     */
    public function dispatch(Command $command) : void
    {
        if (!$this->init) {
            $this->init = true;
            parent::__construct(array_merge($this->middlewares, [
                new CommandHandlerMiddleware($this->extractor, $this->locator, $this->inflector),
            ]));
        }

        // We don't dispatch a message that was already dispatched before
        if (array_key_exists($command->getId(), $this->dispatched_commands)) {
            throw new Exception($command->getShortName().' (id: '.$command->getId().') already dispatched');
        }

        // Register the dispatched message
        $this->dispatched_commands[$command->getId()] = $command;

        try {
            $this->handle($command);
        } catch (MissingHandlerException $e) {
            // Avoid missing handler exception. If there isn't handler for the message,
            // the show must go on !
            return;
        }
    }

    /**
     * Dispatch a batch of commands.
     */
    public function dispatchBatch(array $commands) : void
    {
        foreach ($commands as $command) {
            $this->dispatch($command);
        }
    }

    /**
     * Map a command handler with a specific command message.
     */
    public function subscribe(string $handler_class_name) : void
    {
        if (!is_a($handler_class_name, Handler::class, true)) {
            throw new Exception('Handler MUST extends Handler class');
        }

        $handler = new $handler_class_name($this->getKernel());

        $handler_parameter_reflection = new ReflectionParameter([get_class($handler), '__invoke'], 0);

        $command_class_name = $handler_parameter_reflection->getClass()->name;

        $this->locator->addHandler($handler, $command_class_name);
    }
}
