<?php










namespace Symfony\Component\EventDispatcher;






class ImmutableEventDispatcher implements EventDispatcherInterface
{
private $dispatcher;

public function __construct(EventDispatcherInterface $dispatcher)
{
$this->dispatcher = $dispatcher;
}




public function dispatch(object $event, ?string $eventName = null): object
{
return $this->dispatcher->dispatch($event, $eventName);
}




public function addListener(string $eventName, $listener, int $priority = 0)
{
throw new \BadMethodCallException('Unmodifiable event dispatchers must not be modified.');
}




public function addSubscriber(EventSubscriberInterface $subscriber)
{
throw new \BadMethodCallException('Unmodifiable event dispatchers must not be modified.');
}




public function removeListener(string $eventName, $listener)
{
throw new \BadMethodCallException('Unmodifiable event dispatchers must not be modified.');
}




public function removeSubscriber(EventSubscriberInterface $subscriber)
{
throw new \BadMethodCallException('Unmodifiable event dispatchers must not be modified.');
}




public function getListeners(?string $eventName = null)
{
return $this->dispatcher->getListeners($eventName);
}




public function getListenerPriority(string $eventName, $listener)
{
return $this->dispatcher->getListenerPriority($eventName, $listener);
}




public function hasListeners(?string $eventName = null)
{
return $this->dispatcher->hasListeners($eventName);
}
}
