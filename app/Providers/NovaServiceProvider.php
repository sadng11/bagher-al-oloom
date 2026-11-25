<?php

namespace App\Providers;

use App\Models\Book;
use App\Nova\BottomText;
use App\Nova\Dashboards\Main;
use App\Nova\Dashboards\UserInsights;
use App\Nova\ImageSlider;
use App\Nova\SiteContent;
use App\Nova\Sound;
use App\Nova\SoundCategory;
use App\Nova\User;
use App\Nova\Writer;
use App\Observers\BookObserver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Contracts\QueryBuilder;
use Laravel\Nova\Menu\Menu;
use Laravel\Nova\Menu\MenuGroup;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Laravel\Nova\Observable;

class   NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        $this->app->bind(QueryBuilder::class, function ($app, $parameters) {
            return new \App\Nova\Query\Builder(...$parameters);
        });
        Nova::enableRTL();
//        Nova::withBreadcrumbs();
        Observable::make(Book::class, BookObserver::class);
        Observable::make(\App\Models\ScholarWork::class, BookObserver::class);

        Nova::footer(function ($request) {
            return Blade::render('
            <p class="text-center">Powered by Sahel Group · v{!! $version !!} &copy; {!! $year !!}</p>
        ', [
                'version' => Nova::version(),
                'year' => date('Y'),
            ]);
        });


        Nova::mainMenu(function (Request $request, Menu $menu) {
            return [
                MenuSection::dashboard(Main::class)->icon('chart-bar'),
//                MenuSection::dashboard(UserInsights::class)->icon('chart-bar'),

                MenuSection::make(__('Content & Book'), [
                    MenuItem::resource(\App\Nova\Book::class),
                    MenuItem::resource(\App\Nova\BookContent::class),
                    MenuItem::resource(\App\Nova\ScholarWork::class),
                    MenuItem::resource(Writer::class),
                    MenuItem::resource(SoundCategory::class),
                    MenuItem::resource(Sound::class),
                ])->icon('document-text')->collapsable(),

                MenuSection::make(__('Web Site'), [
                    MenuItem::resource(SiteContent::class),
                    MenuItem::resource(ImageSlider::class),
                    MenuItem::resource(BottomText::class),
                ])->collapsable(),

                MenuSection::make(__('Users'), [
                    MenuItem::resource(User::class),
                ])->icon('user')->collapsable(),

            ];
        });

        Nova::userMenu(function (Request $request, Menu $menu) {
            $menu->prepend(
                MenuItem::make(
                    __('My Profile'),
                    "/resources/users/{$request->user()->getKey()}"
                )
            );

            return $menu;
        });


    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [
            new \App\Nova\Dashboards\Main,
//            UserInsights::make(),
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
