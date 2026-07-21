<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $user = auth()->user();
            $shop = ($user && !$user->isOwner() && $user->shop) ? $user->shop : null;

            $systemName   = \App\Models\Setting::get('system_name', 'AMSTROOM');
            $systemSlogan = \App\Models\Setting::get('slogan', 'Technology Innovations');
            $systemLogo   = \App\Models\Setting::get('logo') ? asset('storage/' . \App\Models\Setting::get('logo')) : null;

            $name   = ($shop && !empty($shop->shop_name)) ? $shop->shop_name : $systemName;
            $slogan = ($shop && !empty($shop->slogan))    ? $shop->slogan    : $systemSlogan;
            $logo   = ($shop && !empty($shop->logo))      ? asset('storage/' . $shop->logo) : $systemLogo;

            $view->with('appBranding', [
                'name'          => $name,
                'slogan'        => $slogan,
                'logo'          => $logo,
                'system_name'   => $systemName,
                'system_slogan' => $systemSlogan,
                'system_logo'   => $systemLogo,
            ]);
        });
    }
}
