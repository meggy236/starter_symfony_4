<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\LoginLoggerSubscriber;
use App\Model\Auth\Command\UserLoggedInSuccessfully;
use App\Model\Auth\Command\UserLoginFailed;
use App\Model\User\Credentials;
use App\Tests\BaseTestCase;
use Mockery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Xm\SymfonyBundle\Model\Email;

class LoginLoggerSubscriberTest extends BaseTestCase
{
    public function testSubscribedEvents(): void
    {
        $subscribed = LoginLoggerSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(
            SecurityEvents::INTERACTIVE_LOGIN,
            $subscribed
        );
        $this->assertArrayHasKey(
            AuthenticationEvents::AUTHENTICATION_FAILURE,
            $subscribed
        );
    }

    public function testSuccessfulLogin(): void
    {
        $faker = $this->faker();

        $method = LoginLoggerSubscriber::getSubscribedEvents()[SecurityEvents::INTERACTIVE_LOGIN];

        $commandBus = Mockery::mock(MessageBusInterface::class);
        $commandBus->shouldReceive('dispatch')
            ->with(Mockery::type(UserLoggedInSuccessfully::class))
            ->andReturn(new Envelope(new \stdClass()));

        $request = Request::create(
            '',
            'GET',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => $faker->ipv4,
            ]
        );
        $requestStack = Mockery::mock(RequestStack::class);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('userId')
            ->andReturn($faker->userId);
        $user->shouldReceive('email')
            ->andReturn(Email::fromString('test@example.com'));

        $token = Mockery::mock(TokenInterface::class);
        $token->shouldReceive('getUser')
            ->andReturn($user);

        $event = new InteractiveLoginEvent($request, $token);

        $listener = new LoginLoggerSubscriber($commandBus, $requestStack);

        $listener->{$method}($event);
    }

    public function testFailedLogin(): void
    {
        $faker = $this->faker();

        $method = LoginLoggerSubscriber::getSubscribedEvents()[AuthenticationEvents::AUTHENTICATION_FAILURE];

        $commandBus = Mockery::mock(MessageBusInterface::class);
        $commandBus->shouldReceive('dispatch')
            ->with(Mockery::type(UserLoginFailed::class))
            ->andReturn(new Envelope(new \stdClass()));

        $request = Request::create(
            '',
            'GET',
            [],
            [],
            [],
            [
                'REMOTE_ADDR'     => $faker->ipv4,
                'HTTP_USER_AGENT' => $faker->userAgent,
            ]
        );
        $requestStack = Mockery::mock(RequestStack::class);
        $requestStack->shouldReceive('getCurrentRequest')
            ->andReturn($request);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('userId')
            ->andReturn($faker->userId);
        $user->shouldReceive('email')
            ->andReturn(Email::fromString('test@example.com'));

        $token = Mockery::mock(TokenInterface::class);
        $token->shouldReceive('getCredentials')
            ->andReturn(Credentials::build($faker->email, $faker->password));

        $event = new AuthenticationFailureEvent(
            $token,
            new AuthenticationException()
        );

        $listener = new LoginLoggerSubscriber($commandBus, $requestStack);

        $listener->{$method}($event);
    }
}