<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Config\Repository;
use Illuminate\Mail\Markdown;
use Illuminate\View\Factory;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class MailMarkdownTest extends TestCase
{
    /**
     * @var \Illuminate\Config\Repository
     */
    protected $configRepository;

    protected function setUp(): void
    {
        $this->configRepository = new Repository([
            'mail' => [
                'markdown' => [
                    'theme' => 'default'
                ]
            ]
        ]);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testRenderFunctionReturnsHtml()
    {
        $viewFactory = m::mock(Factory::class);
        $markdown = new Markdown($viewFactory, $this->configRepository);
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $markdown->htmlComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('exists')->with('mail.default')->andReturn(false);
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('mail::themes.default', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->twice()->andReturn('<html></html>', 'body {}');

        $result = $markdown->render('view', []);

        $this->assertStringContainsString('<html></html>', $result);
    }

    public function testRenderFunctionReturnsHtmlWithCustomTheme()
    {
        $viewFactory = m::mock(Factory::class);
        $markdown = new Markdown($viewFactory, $this->configRepository);
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $markdown->htmlComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('exists')->with('mail.yaz')->andReturn(true);
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('mail.yaz', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->twice()->andReturn('<html></html>', 'body {}');

        $result = $markdown->render('view', [], theme: 'yaz');

        $this->assertStringContainsString('<html></html>', $result);
    }

    public function testRenderFunctionReturnsHtmlWithCustomThemeWithMailPrefix()
    {
        $viewFactory = m::mock(Factory::class);
        $markdown = new Markdown($viewFactory, $this->configRepository);
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $markdown->htmlComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('exists')->with('mail.yaz')->andReturn(true);
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('mail.yaz', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->twice()->andReturn('<html></html>', 'body {}');

        $result = $markdown->render('view', [], theme: 'mail.yaz');

        $this->assertStringContainsString('<html></html>', $result);
    }

    public function testRenderTextReturnsText()
    {
        $viewFactory = m::mock(Factory::class);
        $markdown = new Markdown($viewFactory, $this->configRepository);
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $markdown->textComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->andReturn('text');

        $result = $markdown->renderText('view', [])->toHtml();

        $this->assertSame('text', $result);
    }

    public function testParseReturnsParsedMarkdown()
    {
        $viewFactory = m::mock(Factory::class);
        $markdown = new Markdown($viewFactory, $this->configRepository);

        $result = $markdown->parse('# Something')->toHtml();

        $this->assertSame("<h1>Something</h1>\n", $result);
    }
}
