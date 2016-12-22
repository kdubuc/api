<?php

namespace API\Feature;

use API\Message\Message;

trait MagicMessageHandler
{
    /**
     * Handle the message provided with the correct handler in the object
     * *magically*.
     *
     * @param API\Message\Message $message
     * @param string $method_name_prefix
     */
    public function handleMagically(Message $message, $method_name_prefix = 'handle')
    {
        $method_name = $method_name_prefix.$message->getShortName();

        if (method_exists($this, $method_name)) {
            return $this->$method_name($message);
        }
    }
}
