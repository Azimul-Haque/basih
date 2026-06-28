<?php

namespace App\Providers\Filament;

use Filament\Support\Facades\FilamentView; // 👈 এই নেমস্পেসটি ফাইলের একদম ওপরে নিশ্চিত করুন
use Illuminate\Support\HtmlString;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use App\Filament\Widgets\LedgerOverview;
// 🔥 ডিফল্ট ফিলামেন্ট ড্যাশবোর্ডের পরিবর্তে আপনার কাস্টম ড্যাশবোর্ড ক্লাসটি ইম্পোর্ট করা হলো
use App\Filament\Pages\Dashboard; 

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // 🔥 এখানে আপনার কাস্টম ড্যাশবোর্ড ক্লাসটি পুশ করা হলো
                Dashboard::class, 
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                LedgerOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                \Illuminate\Session\Middleware\AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        // 🔥 গ্লোবাল রেন্ডার হুক ব্যবহার করে হেড ট্যাগে কাস্টম সিএসএস ইনজেক্ট করা হলো
        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): string => new HtmlString('
                <style>
                    /* সাইডবার বা মেনুর টেক্সট বড় করা */
                    .fi-sidebar-item-label {
                        font-size: 1.1rem !important; /* ডিফল্ট থেকে বড় */
                        font-weight: 600 !important;
                    }
                    
                    /* সাইডবার আইকনগুলোর সাইজ সামান্য বড় করা */
                    .fi-sidebar-item-icon {
                        width: 1.5rem !important;
                        height: 1.5rem !important;
                    }

                    /* ড্যাশবোর্ডের মেইন হেডিং বড় করা */
                    .fi-header-heading {
                        font-size: 1.65rem !important;
                        font-weight: 800 !important;
                    }

                    /* টেবিল বা কার্ডের ভেতরের সাধারণ টেক্সট রিডাবিলিটি বাড়ানো */
                    .fi-ta-text, .fi-wi-stats-overview-stat {
                        font-size: 0.95rem !important;
                    }
                </style>
            '),
        );
    }
}