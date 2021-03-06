<?php

namespace Tests\Model;

use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class TraitsTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_creates_traits_from_list()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => [
                        'Foo',
                        'Bar\Quz',
                    ]
                ],
                'Post' => [
                    'name' => 'string',
                    'traits' => [
                        'Bar',
                        'Quz\Qux'
                    ]
                ]
            ],
        ]);

        $this->shouldMockTraitFile(false);

        $this->artisan('larawiz:scaffold');

        static::assertStringContainsString(
            'use Foo;',
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );
        static::assertStringContainsString(
            'use Quz;',
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );

        static::assertStringContainsString(
            'use Bar;',
            $this->filesystem->get($this->app->path('Models' . DS . 'Post.php'))
        );
        static::assertStringContainsString(
            'use Qux;',
            $this->filesystem->get($this->app->path('Models' . DS . 'Post.php'))
        );

        $this->assertFileExistsInFilesystem($this->app->path('Models' . DS . 'Foo.php'));
        static::assertStringContainsString('trait Foo',
            $this->filesystem->get($this->app->path('Models' . DS . 'Foo.php')));
        static::assertStringContainsString('initializeFoo',
            $this->filesystem->get($this->app->path('Models' . DS . 'Foo.php')));
        static::assertStringContainsString('bootFoo',
            $this->filesystem->get($this->app->path('Models' . DS . 'Foo.php')));

        $this->assertFileExistsInFilesystem($this->app->path('Models' . DS . 'Bar' . DS . 'Quz.php'));
        static::assertStringContainsString('trait Quz',
            $this->filesystem->get($this->app->path('Models' . DS . 'Bar' . DS . 'Quz.php')));
        static::assertStringContainsString('initializeQuz',
            $this->filesystem->get($this->app->path('Models' . DS . 'Bar' . DS . 'Quz.php')));
        static::assertStringContainsString('bootQuz',
            $this->filesystem->get($this->app->path('Models' . DS . 'Bar' . DS . 'Quz.php')));
    }

    public function test_traits_can_be_referenced_multiple_times()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => [
                        'Bar\Quz'
                    ]
                ],
                'Post' => [
                    'name' => 'string',
                    'traits' => [
                        'Bar\Quz'
                    ]
                ],
                'Comment' => [
                    'name' => 'string',
                    'traits' => [
                        'Bar\Quz'
                    ]
                ],
            ],
        ]);

        $this->shouldMockTraitFile(false);

        $this->artisan('larawiz:scaffold');

        static::assertStringContainsString(
            'use App\Models\Bar\Quz;',
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );
        static::assertStringContainsString(
            'use Quz;',
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );
        static::assertStringContainsString(
            'use App\Models\Bar\Quz;',
            $this->filesystem->get($this->app->path('Models' . DS . 'Post.php'))
        );
        static::assertStringContainsString(
            'use Quz;',
            $this->filesystem->get($this->app->path('Models' . DS . 'Post.php'))
        );
        static::assertStringContainsString(
            'use App\Models\Bar\Quz;',
            $this->filesystem->get($this->app->path('Models' . DS . 'Comment.php'))
        );
        static::assertStringContainsString(
            'use Quz;',
            $this->filesystem->get($this->app->path('Models' . DS . 'Comment.php'))
        );

        $this->assertFileExistsInFilesystem($this->app->path('Models' . DS . 'Bar' . DS . 'Quz.php'));
        static::assertStringContainsString('trait Quz',
            $this->filesystem->get($this->app->path('Models' . DS . 'Bar' . DS . 'Quz.php')));
        static::assertStringContainsString('initializeQuz',
            $this->filesystem->get($this->app->path('Models' . DS . 'Bar' . DS . 'Quz.php')));
        static::assertStringContainsString('bootQuz',
            $this->filesystem->get($this->app->path('Models' . DS . 'Bar' . DS . 'Quz.php')));
    }

    public function test_error_when_traits_collides_with_models_paths()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The following traits collide with the models: User.');

        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => [
                        'User',
                        'Bar\Quz'
                    ]
                ],
                'Post' => [
                    'name' => 'string',
                    'traits' => [
                        'User',
                        'Quz\Qux'
                    ]
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_trait_not_set_when_using_string()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => 'Foo'
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExistsInFilesystem($this->app->path('Models' . DS . 'Foo.php'));
    }

    public function test_external_trait_is_only_appended()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => [
                        'Illuminate\Foundation\Validation\ValidatesRequests',
                        'Foo',
                        'Bar\Quz'
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExistsInFilesystem($this->app->path(
            'Illuminate' . DS . 'Foundation' . DS . 'Validation' . DS . 'ValidatesRequests.php'
        ));
        $this->assertFileNotExistsInFilesystem($this->app->path(
            'Models' . DS . 'Illuminate' . DS . 'Foundation' . DS . 'Validation' . DS . 'ValidatesRequests.php'
        ));

        static::assertStringContainsString(
            'use Illuminate\Foundation\Validation\ValidatesRequests;',
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );

        static::assertStringContainsString(
            'use ValidatesRequests;',
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );

        $this->assertFileExistsInFilesystem($this->app->path('Models' . DS . 'Foo.php'));
        $this->assertFileExistsInFilesystem($this->app->path('Models' . DS . 'Bar' . DS . 'Quz.php'));
    }

    public function test_error_when_external_trait_is_not_trait_but_class()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [Illuminate\Foundation\Mix] exists but is not a trait, but a class or interface.');

        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => [
                        'Illuminate\Foundation\Mix',
                        'Foo',
                        'Bar\Quz'
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_external_trait_is_not_trait_but_interface()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [Illuminate\Contracts\Auth\Guard] exists but is not a trait, but a class or interface.');

        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => [
                        'Illuminate\Contracts\Auth\Guard',
                        'Foo',
                        'Bar\Quz'
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
