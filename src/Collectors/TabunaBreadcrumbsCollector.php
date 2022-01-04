<?php

namespace RobertBoes\InertiaBreadcrumbs\Collectors;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use RobertBoes\InertiaBreadcrumbs\Breadcrumb;
use RobertBoes\InertiaBreadcrumbs\BreadcrumbCollection;
use Tabuna\Breadcrumbs\Breadcrumbs;
use Tabuna\Breadcrumbs\Crumb;

class TabunaBreadcrumbsCollector extends AbstractBreadcrumbCollector
{
    public function forRequest(Request $request): BreadcrumbCollection
    {
        $breadcrumbs = $this->getBreadcrumbs($request);

        return new BreadcrumbCollection($breadcrumbs, function (Crumb $breadcrumb): Breadcrumb {
            return new Breadcrumb(
                title: $breadcrumb->title(),
                url: $breadcrumb->url(),
            );
        });
    }

    private function getBreadcrumbs(Request $request): Collection
    {
        if (! ($route = $request->route()) instanceof Route) {
            return collect();
        }

        if (! Breadcrumbs::has($route->getName())) {
            return collect();
        }

        return Breadcrumbs::generate($route->getName(), ...$route->parameters());
    }

    public static function requiredClass(): string
    {
        return Breadcrumbs::class;
    }

    public static function notInstalledException(): string
    {
        return \Exception::class;
    }
}
