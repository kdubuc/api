<?php

namespace API\Message;

use API\Feature\{ContainerAccess, MagicMessageHandler};

abstract class Handler
{
    use ContainerAccess, MagicMessageHandler;
}
