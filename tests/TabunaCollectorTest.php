<?php

namespace RobertBoes\InertiaBreadcrumbs\Tests;

use RobertBoes\InertiaBreadcrumbs\Collectors\BreadcrumbCollectorContract;
use RobertBoes\InertiaBreadcrumbs\Collectors\TabunaBreadcrumbsCollector;
use RobertBoes\InertiaBreadcrumbs\Exceptions\PackageNotInstalledException;
use RobertBoes\InertiaBreadcrumbs\Tests\Concerns\SetupCollector;
use RobertBoes\InertiaBreadcrumbs\Tests\Helpers\RequestBuilder;
use Tabuna\Breadcrumbs\Breadcrumbs as TabunaBreadcrumbs;
use Tabuna\Breadcrumbs\BreadcrumbsServiceProvider;
use Tabuna\Breadcrumbs\Trail as TabunaTrail;

class TabunaCollectorTest extends TestCase
{
    use SetupCollector;

    protected function provider(): string
    {
        return BreadcrumbsServiceProvider::class;
    }

    protected function collector(): string
    {
        return TabunaBreadcrumbsCollector::class;
    }

    /**
     * @param \Illuminate\Routing\Router $router
     */
    public function defineRoutes($router)
    {
        $router->inertia('/profile', 'Profile/Index')->name('profile');
        $router->inertia('/profile/edit', 'Profile/Edit')->name('profile.edit');
        $router->inertia('/dashboard', 'Dashboard')->name('dashboard');
        $router->get('/{name}', function (string $name) {
            return inertia('Name', [
                'name' => $name,
            ]);
        })->name('reserved-keyword-route');
    }

    /**
     * @test
     */
    public function it_has_tabuna_collector_bound()
    {
        $collector = app(BreadcrumbCollectorContract::class);

        $this->assertInstanceOf(TabunaBreadcrumbsCollector::class, $collector);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_package_is_not_installed()
    {
        $this->app->instance('inertia-breadcrumbs-package-existence', function (string $class): bool {
            return false;
        });
        $this->expectException(PackageNotInstalledException::class);
        $this->expectExceptionMessage('tabuna/breadcrumbs is not installed');

        app(BreadcrumbCollectorContract::class);
    }

    /**
     * @test
     */
    public function it_collects_tabuna_breadcrumbs()
    {
        TabunaBreadcrumbs::for('profile.edit', function (TabunaTrail $trail) {
            $trail->push('Profile', route('profile'));
            $trail->push('Edit profile', route('profile.edit'));
        });

        $request = RequestBuilder::create('profile.edit');
        $crumbs = app(BreadcrumbCollectorContract::class)->forRequest($request);

        $this->assertSame(2, $crumbs->items()->count());
        $this->assertSame([
            [
                'title' => 'Profile',
                'url' => route('profile'),
            ],
            [
                'title' => 'Edit profile',
                'url' => route('profile.edit'),
                'current' => true,
            ],
        ], $crumbs->toArray());
    }

    /**
     * @test
     */
    public function it_can_use_a_reserved_keyword()
    {
        TabunaBreadcrumbs::for('reserved-keyword-route', function (TabunaTrail $trail) {
            $trail->push('Reserved', route('reserved-keyword-route', ['name' => 'robert']));
        });

        $request = RequestBuilder::create('reserved-keyword-route', ['name' => 'robert']);
        $crumbs = app(BreadcrumbCollectorContract::class)->forRequest($request);

        $this->assertSame(1, $crumbs->items()->count());
        $this->assertSame([
            [
                'title' => 'Reserved',
                'url' => route('reserved-keyword-route', ['name' => 'robert']),
                'current' => true,
            ],
        ], $crumbs->toArray());
    }

    /**
     * @test
     */
    public function it_returns_an_empty_collection_when_route_has_no_breadcrumbs()
    {
        $request = RequestBuilder::create('dashboard');
        $crumbs = app(BreadcrumbCollectorContract::class)->forRequest($request);

        $this->assertTrue($crumbs->items()->isEmpty());
    }

    /**
     * @test
     */
    public function it_returns_empty_collection_for_404_page()
    {
        $request = RequestBuilder::notFound('foo');
        $crumbs = app(BreadcrumbCollectorContract::class)->forRequest($request);

        $this->assertTrue($crumbs->items()->isEmpty());
    }
}
