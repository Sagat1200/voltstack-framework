<?php

declare(strict_types=1);

namespace VoltStack\Test\Feature;

use PHPUnit\Framework\TestCase;
use Quantum\Controllers\Controller;
use Quantum\Http\Request;
use Quantum\HttpKernel\HttpKernel;
use Quantum\Routing\Router;
use Quantum\View\ViewFactory;
use VoltStack\Framework\Application;

final class ViewRenderingTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'voltstack-view-' . uniqid('', true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views', 0777, true);

        file_put_contents(
            $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'home.php',
            <<<'PHP'
<h1><?= e($title) ?></h1>
<p><?= e($message) ?></p>
PHP
        );
    }

    protected function tearDown(): void
    {
        $viewFile = $this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'home.php';
        $viewsDirectory = dirname($viewFile);
        $resourcesDirectory = dirname($viewsDirectory);

        if (is_file($viewFile)) {
            unlink($viewFile);
        }

        if (is_dir($viewsDirectory)) {
            rmdir($viewsDirectory);
        }

        if (is_dir($resourcesDirectory)) {
            rmdir($resourcesDirectory);
        }

        if (is_dir($this->basePath)) {
            rmdir($this->basePath);
        }

        parent::tearDown();
    }

    public function test_controller_can_return_a_view_response_through_the_kernel(): void
    {
        $app = new Application($this->basePath);
        $router = $app->make(Router::class);
        $router->get('/home', [TestPageController::class, 'show']);

        $response = $app->make(HttpKernel::class)->handle(Request::create('/home'));

        self::assertSame(200, $response->statusCode());
        self::assertSame("<h1>VoltStack</h1>\n<p>Hello from the view layer.</p>", trim($response->content()));
    }

    public function test_view_and_response_helpers_are_registered_in_the_container_flow(): void
    {
        $app = new Application($this->basePath);

        self::assertTrue($app->make(ViewFactory::class)->exists('home'));
        self::assertSame('application/json; charset=UTF-8', response()->json(['ok' => true])->headers()['Content-Type']);
    }
}

final class TestPageController extends Controller
{
    public function show(): \Quantum\View\View
    {
        return $this->view('home', [
            'title' => 'VoltStack',
            'message' => 'Hello from the view layer.',
        ]);
    }
}
