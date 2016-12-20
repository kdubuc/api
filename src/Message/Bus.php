<?php

namespace API\Message;

use Interop\Container\ContainerInterface as Container;
use League\Tactician\CommandBus as TacticianBus;
use League\Tactician\Exception\MissingHandlerException;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\Locator\InMemoryLocator;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;
use ReflectionParameter;

class Bus extends TacticianBus
{
    /**
     * Build the bus.
     *
     * @param Middleware[] $middlewares
     */
    public function __construct(array $middlewares = [])
    {
        $this->init                = false;
        $this->middlewares         = $middlewares;
        $this->locator             = new InMemoryLocator();
        $this->extractor           = new ClassNameExtractor();
        $this->inflector           = new HandleInflector();
        $this->dispatched_messages = [];
    }

    /**
     * Dispatch a message to its appropriate handler.
     *
     * @param API\Message\Message $message
     */
    public function dispatch(Message $message)
    {
        if (!$this->init) {
            $this->init = true;
            parent::__construct(array_merge($this->middlewares, [
                new CommandHandlerMiddleware($this->extractor, $this->locator, $this->inflector),
            ]));
        }

        $this->dispatched_messages[] = $message;

        try {
            return $this->handle($message);
        } catch (MissingHandlerException $e) {
            // Avoid missing handler exception. If there isn't handler for the message,
            // the show must go on !
            return;
        }
    }

    /**
     * Dispatch a batch of messages.
     *
     * @param API\Message\Message[] $messages
     *
     * @return array $results
     */
    public function dispatchBatch($messages) : array
    {
        $results = [];

        foreach ($messages as $message) {
            $results[] = $this->dispatch($message);
        }

        return $results;
    }

    /**
     * Subscribes the handler to this bus.
     *
     * @param string                               $handler
     * @param Interop\Container\ContainerInterface $container
     */
    public function subscribe(string $handler, Container $container)
    {
        foreach (get_class_methods($handler) as $method) {
            if (strpos($method, 'handle') === 0) {
                $handle_parameter_reflection = new ReflectionParameter([$handler, $method], 0);

                $expected_message = $handle_parameter_reflection->getClass()->name;

                $this->locator->addHandler(new $handler($container), $expected_message);
            }
        }
    }

    /**
     * Wait for a message.
     *
     * @param string $message_name
     * @param int    $timeout      Timeout in seconds
     *
     * @return API\Message\Message
     */
    public function waitFor($message_name, $timeout = 10) : Message
    {
        // Tick rate definitions
        $tick_count = 0;
        $tick_delay = 10000;

        do {

            // Wait fo tick
            usleep($tick_delay);

            // Increment the tick count
            ++$tick_count;

            // Get all messages dispatched with the same name
            $messages = array_filter($this->dispatched_messages, function ($message) use ($message_name) {
                return $message->getName() == $message_name;
            });

            // Get the first element in the result
            $message = array_shift($messages);

            // If the element is not null, return the event !
            if ($message !== null) {
                return $message;
            }
        } while ($tick_count * $tick_delay < $timeout * 1000000);

        throw new Exception('Timeout reached');
    }
}
