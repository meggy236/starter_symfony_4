<?php

declare(strict_types=1);

namespace App\Tests\Form\Type;

use App\Form\Type\PhoneNumberType;
use App\Model\PhoneNumber;
use App\Tests\Model\PhoneNumberDataProvider;
use App\Tests\TypeTestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PhoneNumberTypeTest extends TypeTestCase
{
    use PhoneNumberDataProvider;

    /**
     * @dataProvider phoneNumberValidProvider
     */
    public function testValid(string $string, array $expected): void
    {
        $formData = [
            'phoneNumber' => $string,
        ];

        $form = $this->factory->create(PhoneNumberTypeTestForm::class)
            ->submit($formData);

        $this->assertFormIsValid($form);
        $this->hasAllFormFields($form, $formData);

        // make sure the string can be converted to a phone number VO
        $this->assertInstanceOf(
            PhoneNumber::class,
            PhoneNumber::fromObject($form->getData()['phoneNumber'])
        );
    }

    /**
     * @dataProvider phoneNumberInvalidProvider
     */
    public function testInvalid(string $string): void
    {
        $formData = [
            'phoneNumber' => $string,
        ];

        $form = $this->factory->create(PhoneNumberTypeTestForm::class)
            ->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());

        $this->assertCount(1, $form->get('phoneNumber')->getErrors(true, true));
    }
}

class PhoneNumberTypeTestForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('phoneNumber', PhoneNumberType::class);
    }
}