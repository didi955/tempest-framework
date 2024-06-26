<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\View;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tempest\Http\Session\Session;
use Tempest\Validation\Rules\AlphaNumeric;
use Tempest\Validation\Rules\Between;
use function Tempest\view;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 * @small
 */
class ViewComponentTest extends FrameworkIntegrationTestCase
{
    #[DataProvider('view_components')]
    public function test_view_components(string $component, string $rendered): void
    {
        $this->assertSame(
            expected: $rendered,
            actual: view($component)->render(),
        );
    }

    public function test_view_component_with_php_code(): void
    {
        $this->assertSame(
            expected: '<div foo="hello" bar="barValue"></div>',
            actual: view(
                <<<'HTML'
            <x-my :foo="$this->input" bar="barValue"></x-my>',
            HTML,
            )->data(input: 'hello')->render(),
        );
    }

    public function test_nested_components(): void
    {
        $this->assertSame(
            expected: <<<'HTML'
            <form action="#" method="post">
                
                <div>
                    <x-input name="a" label="a" type="number"/>
                </div>
                <x-input name="b" label="b" type="text"/>
            
            </form>
            HTML,
            actual: view(
                <<<'HTML'
            <x-form action="#">
                <div>
                    <x-input name="a" label="a" type="number"></x-input>
                </div>
                <x-input name="b" label="b" type="text" />
            </x-form>
            HTML,
            )->render(),
        );
    }

    public function test_view_component_with_injected_view(): void
    {
        $between = new Between(min: 1, max: 10);
        $alphaNumeric = new AlphaNumeric();

        $this->container->get(Session::class)->flash(
            Session::VALIDATION_ERRORS,
            ['name' => [$between, $alphaNumeric]],
        );

        $this->container->get(Session::class)->flash(
            Session::ORIGINAL_VALUES,
            ['name' => 'original name'],
        );

        $html = view(
            <<<'HTML'
            <x-input name="name" label="a" type="number" />
            HTML,
        )->render();

        $this->assertStringContainsString('value="original name"', $html);
        $this->assertStringContainsString($between->message(), $html);
        $this->assertStringContainsString($alphaNumeric->message(), $html);
    }

    public function test_component_with_injected_dependency(): void
    {
        $this->assertSame(
            expected: 'hi',
            actual: view('<x-with-injection />')->render(),
        );
    }

    public static function view_components(): Generator
    {
        yield [
            '<x-my></x-my>',
            '<div></div>',
        ];

        yield [
            '<x-my>body</x-my>',
            '<div>body</div>',
        ];

        yield [
            '<x-my><p>a</p><p>b</p></x-my>',
            '<div><p>a</p><p>b</p></div>',
        ];

        yield [
            '<x-my>body</x-my><x-my>body</x-my>',
            '<div>body</div><div>body</div>',
        ];

        yield [
            '<x-my foo="fooValue" bar="barValue">body</x-my>',
            '<div foo="fooValue" bar="barValue">body</div>',
        ];

        yield [
            <<<'HTML'
            <x-my>
            body
            
            multiline
            </x-my>
            HTML,
            <<<'HTML'
            <div>
            body
            
            multiline
            </div>
            HTML,
        ];
    }
}
