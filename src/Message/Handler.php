<?php

namespace API\Message;

use API\Feature\ContainerAccess;

abstract class Handler
{
    use ContainerAccess;

    /**
     * Invoke handler with a message.
     *
     * @param API\Message\Message
     *
     * @return mixed
     */
    public function __invoke(Message $message)
    {
        $method_name = 'handle'.$message->getShortName();

        if (method_exists($this, $method_name)) {
            return $this->$method_name($message);
        }
    }
}
