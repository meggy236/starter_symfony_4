<?php

declare(strict_types=1);

namespace App\Infrastructure\GraphQl\Mutation;

use App\Exception\FormValidationException;
use App\Form\EnquiryType;
use App\Model\Enquiry\Command\SubmitEnquiry;
use App\Model\Enquiry\EnquiryId;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SendEnquiryMutation implements MutationInterface
{
    /** @var MessageBusInterface */
    private $commandBus;

    /** @var FormFactoryInterface */
    private $formFactory;

    public function __construct(
        MessageBusInterface $commandBus,
        FormFactoryInterface $formFactory
    ) {
        $this->commandBus = $commandBus;
        $this->formFactory = $formFactory;
    }

    public function __invoke(Argument $args): array
    {
        $form = $this->formFactory
            ->create(EnquiryType::class)
            ->submit($args['enquiry']);

        if (!$form->isValid()) {
            throw FormValidationException::fromForm($form, 'enquiry');
        }

        $this->commandBus->dispatch(SubmitEnquiry::with(
            EnquiryId::generate(),
            $form->getData()['name'],
            $form->getData()['email'],
            $form->getData()['message']
        ));

        return [
            'success' => true,
        ];
    }
}