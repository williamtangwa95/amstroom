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
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        // System-wide SVG upload restriction & Dynamic Max File Size enforcement
        \Illuminate\Support\Facades\Validator::extend('image', function ($attribute, $value, $parameters, $validator) {
            if (!$value instanceof \Illuminate\Http\UploadedFile) {
                return false;
            }
            $extension = strtolower($value->getClientOriginalExtension());
            $mime = strtolower($value->getMimeType() ?? '');
            if (in_array($extension, ['svg']) || $mime === 'image/svg+xml') {
                return false;
            }
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'])) {
                return false;
            }

            $requestMaxMb = request()->input('max_upload_size_mb');
            $maxMb = ($requestMaxMb && is_numeric($requestMaxMb) && (int) $requestMaxMb > 0)
                ? (int) $requestMaxMb
                : (int) \App\Models\Setting::get('max_upload_size_mb', 5);
            $maxBytes = $maxMb * 1024 * 1024;
            return $value->getSize() <= $maxBytes;
        }, 'The :attribute must be a valid image file (JPG, PNG, WEBP, GIF) and not exceed the configured maximum upload size.');




        // Listen for Login event
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function (\Illuminate\Auth\Events\Login $event) {
                \App\Models\ActivityLog::create([
                    'user_id'      => $event->user->id,
                    'action'       => 'LOGIN',
                    'description'  => 'Logged into the system.',
                    'ip_address'   => request()->ip(),
                    'user_agent'   => request()->userAgent(),
                ]);
            }
        );

        // Listen for Logout event
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            function (\Illuminate\Auth\Events\Logout $event) {
                if ($event->user) {
                    \App\Models\ActivityLog::create([
                        'user_id'      => $event->user->id,
                        'action'       => 'LOGOUT',
                        'description'  => 'Logged out of the system.',
                        'ip_address'   => request()->ip(),
                        'user_agent'   => request()->userAgent(),
                    ]);
                }
            }
        );

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $user = auth()->user();
            $shop = ($user && !$user->isOwner() && $user->shop) ? $user->shop : null;

            $systemName   = \App\Models\Setting::get('system_name', 'AMSTROOM');
            $systemSlogan = \App\Models\Setting::get('slogan', 'Technology Innovations');
            $systemLogo   = \App\Models\Setting::get('logo') ? asset('media/' . \App\Models\Setting::get('logo')) : null;

            $systemLocation = \App\Models\Setting::get('company_address', 'Main Store / HQ');
            $location       = ($shop && !empty($shop->location)) ? $shop->location : (($shop && !empty($shop->address)) ? $shop->address : $systemLocation);

            $name   = ($shop && !empty($shop->shop_name)) ? $shop->shop_name : $systemName;
            $slogan = ($shop && !empty($shop->slogan))    ? $shop->slogan    : $systemSlogan;
            $logo   = ($shop && !empty($shop->logo))      ? asset('media/' . $shop->logo) : $systemLogo;

            $view->with('appBranding', [
                'name'            => $name,
                'slogan'          => $slogan,
                'logo'            => $logo,
                'location'        => $location,
                'system_name'     => $systemName,
                'system_slogan'   => $systemSlogan,
                'system_logo'     => $systemLogo,
                'system_location' => $systemLocation,
            ]);

        });
    }
}
