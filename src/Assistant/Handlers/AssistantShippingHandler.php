<?php

namespace RakutenFrance\Assistant\Handlers;

use Plenty\Modules\Wizard\Contracts\WizardSettingsHandler;
use Plenty\Plugin\Log\Loggable;

class AssistantShippingHandler implements WizardSettingsHandler
{
    use Loggable;

    public function handle(array $parameters): bool
    {
        return true;
    }
}
