<?php

namespace App\Traits;

use App\Services\Logging\StructuredLogger;
use Illuminate\Support\Facades\App;

trait Loggable
{
    protected function getLogger(): StructuredLogger
    {
        return App::make(StructuredLogger::class);
    }

    protected function logInfo(string $message, array $context = []): void
    {
        $this->getLogger()->info($message, $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        $this->getLogger()->error($message, $context);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        $this->getLogger()->warning($message, $context);
    }

    protected function logDebug(string $message, array $context = []): void
    {
        $this->getLogger()->debug($message, $context);
    }
}