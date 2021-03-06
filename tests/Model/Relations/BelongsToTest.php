<?php

namespace Tests\Model\Relations;

use Illuminate\Support\Carbon;
use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class BelongsToTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_error_when_incorrect_belongs_to()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [label] relation in [Tag] points to a non-existent [Label] model.');

        $this->mockDatabaseFile(
            [
                'models' => [
                    'Post' => [
                        'name' => 'string',
                        'tags' => 'belongsToMany'
                    ],
                    'Labels' => [
                        'name' => 'string',
                        'permissions' => 'json'
                    ],
                    'Tag' => [
                        'post' => 'belongsTo',
                        'label' => 'belongsTo:Label'
                    ]
                ]
            ]
        );

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_model_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [users] relation in [Post] points to non-existent [Foo] model.');

        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'users' => 'belongsTo:Foo'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_model_cannot_be_guessed()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [authors] relation of [Post] must have a target model.');

        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'authors' => 'belongsTo'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_guesses_model_name_from_relation_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $postModel = $this->filesystem->get($this->app->path('Models' . DS . 'Post.php'));
        $postMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        static::assertStringContainsString('@property-read \App\Models\User $user', $postModel);
        static::assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\User', $postModel);
        static::assertStringContainsString('return $this->belongsTo(User::class);', $postModel);
        static::assertStringContainsString(
            "\$table->unsignedBigInteger('user_id'); // Created for [user] relation.", $postMigration
        );
    }

    public function test_different_relation_name_with_correct_model()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'author' => 'belongsTo:User'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $userModel = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $userMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php')
        );

        static::assertStringNotContainsString('protected $primaryKey', $userModel);
        static::assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Post[] $posts', $userModel
        );
        static::assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Models\Post', $userModel);
        static::assertStringContainsString('public function posts()', $userModel);
        static::assertStringContainsString("return \$this->hasMany(Post::class);", $userModel);
        static::assertStringNotContainsString('post', $userMigration);

        $postModel = $this->filesystem->get($this->app->path('Models' . DS . 'Post.php'));
        $postMigration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        static::assertStringContainsString('@property-read \App\Models\User $author', $postModel);
        static::assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\User', $postModel);
        static::assertStringContainsString('public function author()', $postModel);
        static::assertStringContainsString("return \$this->belongsTo(User::class, 'user_id');", $postModel);
        static::assertStringContainsString(
            "\$table->unsignedBigInteger('user_id'); // Created for [author] relation.", $postMigration
        );
    }

    public function test_with_column()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'string',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User,user_name'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Post.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        static::assertStringContainsString('@property-read \App\Models\User $user', $model);
        static::assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\User', $model);
        static::assertStringContainsString("return \$this->belongsTo(User::class, 'user_name');", $model);
        static::assertStringContainsString(
            "\$table->string('user_name'); // Created for [user] relation.", $migration
        );
    }

    public function test_error_when_column_doesnt_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The relation [user] references the [bar] column in the [User] but it doesn\'t exists');

        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User,foo_bar'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_can_not_guess_without_primary_key()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [user] relation in [Post] needs a column of [User].');

        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'columns' => [
                        'name' => 'string',
                        'posts' => 'hasMany:Post'
                    ]
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_creates_nullable_column()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'string',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User,user_name nullable'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Post.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        static::assertStringContainsString('@property-read null|\App\Models\User $user', $model);
        static::assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\User', $model);
        static::assertStringContainsString("return \$this->belongsTo(User::class, 'user_name');", $model);
        static::assertStringContainsString(
            "\$table->string('user_name')->nullable(); // Created for [user] relation.", $migration
        );
    }

    public function test_creates_index_column()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'string',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User,user_name index'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Post.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        static::assertStringContainsString('@property-read \App\Models\User $user', $model);
        static::assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\User', $model);
        static::assertStringContainsString("return \$this->belongsTo(User::class, 'user_name');", $model);
        static::assertStringContainsString(
            "\$table->string('user_name')->index(); // Created for [user] relation.", $migration
        );
    }

    public function test_creates_unique_column()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'string',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User,user_name unique'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Post.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        static::assertStringContainsString('@property-read \App\Models\User $user', $model);
        static::assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\User', $model);
        static::assertStringContainsString("return \$this->belongsTo(User::class, 'user_name');", $model);
        static::assertStringContainsString(
            "\$table->string('user_name')->unique(); // Created for [user] relation.", $migration
        );
    }

    public function test_accepts_with_default_and_nullable()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'string',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User,user_name nullable withDefault'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Post.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        static::assertStringContainsString('@property-read \App\Models\User $user', $model);
        static::assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\User', $model);
        static::assertStringContainsString("return \$this->belongsTo(User::class, 'user_name')->withDefault();", $model);
        static::assertStringContainsString(
            "\$table->string('user_name')->nullable(); // Created for [user] relation.", $migration
        );
    }

    public function test_accepts_nullable()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'string',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User,user_name nullable'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Post.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        static::assertStringContainsString('@property-read null|\App\Models\User $user', $model);
        static::assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\User', $model);
        static::assertStringContainsString("return \$this->belongsTo(User::class, 'user_name');", $model);
        static::assertStringContainsString(
            "\$table->string('user_name')->nullable(); // Created for [user] relation.", $migration
        );
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
