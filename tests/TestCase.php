<?php

namespace Tests;

use App\Ai\Providers\FakeAiProvider;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        FakeAiProvider::reset();
    }

    protected function enableAi(): void
    {
        config([
            'ai.enabled' => true,
            'ai.provider' => 'fake',
            'ai.model' => 'fake-test',
            'ai.api_key' => null,
            'ai.timeout' => 5,
            'ai.max_tokens' => 800,
        ]);

        settings()->set('ai.enabled', true);
        settings()->set('ai.provider', 'fake');
        settings()->set('ai.model', 'fake-test');
        settings()->set('ai.timeout', 5);
        settings()->set('ai.max_tokens', 800);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'AI Test Role',
            'slug' => 'ai-test-'.uniqid(),
        ]);

        $ids = collect($permissions)->map(function (string $name) {
            return Permission::query()->firstOrCreate(
                ['name' => $name],
                ['group' => explode('.', $name)[0], 'description' => $name]
            )->id;
        });

        $role->permissions()->sync($ids);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
