<?php

declare(strict_types=1);

namespace App\Form;

use App\DataProvider\RoleProvider;
use App\Form\DataTransformer\EmailTransformer;
use App\Form\DataTransformer\NameTransformer;
use App\Form\DataTransformer\SecurityRoleTransformer;
use App\Form\DataTransformer\UserIdTransformer;
use App\Model\User\Name;
use App\Model\User\User;
use App\Validator\Constraints\UniqueExistingUserEmail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminUserUpdateType extends AbstractType
{
    /** @var RoleProvider */
    private $roleProvider;

    /** @var SecurityRoleTransformer */
    private $securityRoleTransformer;

    public function __construct(
        RoleProvider $roleProvider,
        SecurityRoleTransformer $securityRoleTransformer
    ) {
        $this->roleProvider = $roleProvider;
        $this->securityRoleTransformer = $securityRoleTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class, [
                'label' => 'User ID',
                // no constraints because we don't want to show these errors to the user
            ])
            ->add('email', EmailType::class, [
                'label'       => 'Email',
                'attr'        => ['maxlength' => 150],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(['mode' => 'strict']),
                ],
            ])
            ->add('changePassword', CheckboxType::class, [
                'label' => 'Change Password',
            ])
            // @todo additional validation: check common passwords
            ->add('password', PasswordType::class, [
                'label'       => 'Password',
                'attr'        => ['maxlength' => BasePasswordEncoder::MAX_PASSWORD_LENGTH],
                'constraints' => [
                    new Assert\NotBlank(['groups' => ['password']]),
                    new Assert\Length([
                        'min'    => User::PASSWORD_MIN_LENGTH,
                        'max'    => BasePasswordEncoder::MAX_PASSWORD_LENGTH,
                        'groups' => ['password'],
                    ]),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label'       => 'First Name',
                'attr'        => ['maxlength' => Name::NAME_MAX_LENGTH],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'min' => Name::NAME_MIN_LENGTH,
                        'max' => Name::NAME_MAX_LENGTH,
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label'       => 'Last Name',
                'attr'        => ['maxlength' => Name::NAME_MAX_LENGTH],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'min' => Name::NAME_MIN_LENGTH,
                        'max' => Name::NAME_MAX_LENGTH,
                    ]),
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label'   => 'Role',
                'choices' => ($this->roleProvider)(),
            ])
        ;

        $builder->get('id')
            ->addModelTransformer(new UserIdTransformer());
        $builder->get('email')
            ->addModelTransformer(new EmailTransformer());
        $builder->get('role')
            ->addModelTransformer($this->securityRoleTransformer);
        $builder->get('firstName')
            ->addModelTransformer(new NameTransformer());
        $builder->get('lastName')
            ->addModelTransformer(new NameTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection'   => false,
            'validation_groups' => function (FormInterface $form) {
                $groups = ['Default'];
                $data = $form->getData();

                if ($data['changePassword']) {
                    $groups[] = 'password';
                }

                return $groups;
            },
            'constraints'       => [
                new UniqueExistingUserEmail(),
            ],
        ]);
    }
}