<?php
declare(strict_types=1);

namespace Nexus\Events;

/**
 * Decoupled Pub/Sub Event Dispatcher
 */
class EventDispatcher
{
    private array $listeners = [];

    public function listen(string $eventName, callable $listener): static
    {
        $this->listeners[$eventName][] = $listener;
        return $this;
    }

    public function dispatch(string|object $event, mixed $payload = null): void
    {
        $eventName = is_object($event) ? get_class($event) : $event;
        $eventData = is_object($event) ? $event : $payload;

        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                $listener($eventData);
            }
        }
    }
}

