<?php

declare(strict_types=1);

namespace App\Infrastructure\GraphQl\Mutation;

use App\Exception\FormValidationException;
use App\Form\UserVerifyType;
use App\Model\User\Command\ChangePassword;
use App\Model\User\Command\VerifyUser;
use App\Model\User\Exception\InvalidToken;
use App\Model\User\Exception\TokenHasExpired;
use App\Security\PasswordEncoder;
use App\Security\TokenValidator;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;
use Overblog\GraphQLBundle\Error\UserError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Role\Role;

class UserVerifyMutation implements MutationInterface
{
    /** @var MessageBusInterface */
    private $commandBus;

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var PasswordEncoder */
    private $passwordEncoder;

    /** @var TokenValidator */
    private $tokenValidator;

    public function __construct(
        MessageBusInterface $commandBus,
        FormFactoryInterface $formFactory,
        PasswordEncoder $passwordEncoder,
        TokenValidator $tokenValidator
    ) {
        $this->commandBus = $commandBus;
        $this->formFactory = $formFactory;
        $this->passwordEncoder = $passwordEncoder;
        $this->tokenValidator = $tokenValidator;
    }

    public function __invoke(Argument $args): array
    {
        $form = $this->formFactory
            ->create(UserVerifyType::class)
            ->submit([
                'token'    => $args['token'],
                'password' => [
                    'first'  => $args['password'],
                    'second' => $args['repeatPassword'],
                ],
            ]);

        if (!$form->isValid()) {
            throw FormValidationException::fromForm($form);
        }

        try {
            $user = $this->tokenValidator->validate($form->getData()['token']);
        } catch (InvalidToken $e) {
            // 404 -> not found
            throw new UserError('The token is invalid.', 404, $e);
        } catch (TokenHasExpired $e) {
            // 405 -> method not allowed
            throw new UserError('The link has expired.', 405);
        }

        if (!$user || !$user->active()) {
            // 404 -> not found
            throw new UserError('An account with that email cannot be found.');
        }

        if ($user->verified()) {
            throw new UserError('Your account has already been activated.', 404);
        }

        $this->commandBus->dispatch(
            VerifyUser::now($user->id())
        );

        $encodedPassword = ($this->passwordEncoder)(
            new Role($user->roles()[0]),
            $form->getData()['newPassword']
        );
        $this->commandBus->dispatch(
            ChangePassword::forUser($user->id(), $encodedPassword)
        );

        // we would log the user in right away, but as we don't have a request
        // and the projection might not be caught up, we don't try

        return [
            'success' => true,
        ];
    }
}