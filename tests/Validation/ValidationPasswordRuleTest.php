<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationPasswordRuleTest extends TestCase
{
    public function testString()
    {
        $this->fails(Password::min(3), [['foo' => 'bar'], ['foo']], [
            'validation.string',
            'validation.min.string',
        ]);

        $this->fails(Password::min(3), [1234567, 545], [
            'validation.string',
        ]);

        $this->passes(Password::min(3), ['abcd', '454qb^']);
    }

    public function testMin()
    {
        $this->fails(Password::min(3), ['a', 'ff', '12'], [
            'validation.min.string',
        ]);

        $this->passes(Password::min(3), ['1234', 'abcd']);
    }

    public function testMixedCase()
    {
        $this->fails(Password::min(2)->mixedCase(), ['nn', 'MM', '京都府'], [
            'The my password must contain at least one uppercase and one lowercase letter.',
        ]);

        $this->passes(Password::min(2)->mixedCase(), ['Nn', 'Mn', 'âA', '京都府']);
    }

    public function testLetters()
    {
        $this->fails(Password::min(2)->letters(), ['11', '22', '^^', '``', '**'], [
            'The my password must contain at least one letter.',
        ]);

        $this->passes(Password::min(2)->letters(), ['1a', 'b2', 'â1', '1 京都府']);
    }

    public function testNumbers()
    {
        $this->fails(Password::min(2)->numbers(), ['aa', 'bb', '  a', '京都府'], [
            'The my password must contain at least one number.',
        ]);

        $this->passes(Password::min(2)->numbers(), ['1a', 'b2', '00', '京都府 1']);
    }

    public function testSymbols()
    {
        $this->fails(Password::min(2)->symbols(), ['ab', '1v'], [
            'The my password must contain at least one symbol.',
        ]);

        $this->passes(Password::min(2)->symbols(), ['n^d', 'd^!', 'âè$']);
    }

    public function testUncompromised()
    {
        $this->fails(Password::min(2)->uncompromised(), [
            '123456',
            'password',
            'welcome',
            'ninja',
            'abc123',
            '123456789',
            '12345678',
            'nuno',
        ], [
            'The given my password has appeared in a data leak. Please choose a different my password.',
        ]);

        $this->passes(Password::min(2)->uncompromised(9999999), [
            'nuno',
        ]);

        $this->passes(Password::min(2)->uncompromised(), [
            '!p8VrB',
            '&xe6VeKWF#n4',
            '%HurHUnw7zM!',
            'rundeliekend',
            '7Z^k5EvqQ9g%c!Jt9$ufnNpQy#Kf',
            'NRs*Gz2@hSmB$vVBSPDfqbRtEzk4nF7ZAbM29VMW$BPD%b2U%3VmJAcrY5eZGVxP%z%apnwSX',
        ]);
    }

    public function testMessages()
    {
        $makeRule = function () {
            return Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised();
        };

        $this->fails($makeRule(), ['foo', 'azdazd', '1231231'], [
            'validation.min.string',
        ]);

        $this->fails($makeRule(), ['aaaaaaaaa', 'TJQSJQSIUQHS'], [
            'The my password must contain at least one uppercase and one lowercase letter.',
            'The my password must contain at least one symbol.',
            'The my password must contain at least one number.',
        ]);

        $this->fails($makeRule(), ['4564654564564'], [
            'The my password must contain at least one uppercase and one lowercase letter.',
            'The my password must contain at least one letter.',
            'The my password must contain at least one symbol.',
        ]);
    }

    protected function passes($rule, $values)
    {
        $this->testRule($rule, $values, true, []);
    }

    protected function fails($rule, $values, $messages)
    {
        $this->testRule($rule, $values, false, $messages);
    }

    protected function testRule($rule, $values, $result, $messages)
    {
        foreach ($values as $value) {
            $v = new Validator(
                resolve('translator'),
                ['my_password' => $value, 'my_password_confirmation' => $value],
                ['my_password' => clone $rule]
            );

            $this->assertSame($result, $v->passes());

            $this->assertSame(
                $result ? [] : ['my_password' => $messages],
                $v->messages()->toArray()
            );
        }
    }

    protected function setUp(): void
    {
        $container = Container::getInstance();

        $container->bind('translator', function () {
            return new Translator(
                new ArrayLoader, 'en'
            );
        });

        Facade::setFacadeApplication($container);

        (new ValidationServiceProvider($container))->register();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication(null);
    }
}
