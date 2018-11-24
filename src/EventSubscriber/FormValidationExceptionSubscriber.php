<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\FormValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class FormValidationExceptionSubscriber implements EventSubscriberInterface
{
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -100],
        ];
    }

    /**
     * If the exception if of customer doesn't exist,
     * change the exception to an HTTP 404.
     *
     * @see \FOS\RestBundle\Serializer\Normalizer\FormErrorNormalizer
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        /** @var FormValidationException $exception */
        $exception = $event->getException();

        if ($exception instanceof FormValidationException) {
            $json = $this->serializer
                ->serialize($exception->getForm(), 'json', array_merge([
                    'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
                ]));

            $event->setResponse(JsonResponse::fromJsonString($json, 400));
        }
    }
}
