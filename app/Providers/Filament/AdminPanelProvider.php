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
            ->notifications([
                'alignment' => \Filament\Support\Enums\Alignment::Center, // সেন্টারে থাকবে
                'position' => \Filament\Support\Enums\Alignment::Bottom,  // স্ক্রিনের একদম নিচে দেখাবে
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        // 🔥 গুগল ফন্টস প্রি-কানেক্ট এবং সিএসএস ইনজেকশন (পারফরম্যান্স ও সুন্দর বাংলা ফন্টের জন্য)
        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): string => new HtmlString('
                <link rel="preconnect" href="https://fonts.googleapis.com">
                <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

                <style>
                    /* পুরো ফিলামেন্ট প্যানেলের ওপর বাংলা ফন্ট অ্যাপ্লাই করা হলো */
                    body, html, select, input, textarea, button, span, div {
                        font-family: "Hind Siliguri", sans-serif !important;
                    }

                    /* সাইডবার বা মেনুর টেক্সট বড় ও স্পষ্ট করা */
                    .fi-sidebar-item-label {
                        font-size: 1.15rem !important;
                        font-weight: 600 !important;
                        letter-spacing: 0.3px;
                    }
                    
                    /* সাইডবার আইকনগুলোর সাইজ অপ্টিমাইজেশন */
                    .fi-sidebar-item-icon {
                        width: 1.4rem !important;
                        height: 1.4rem !important;
                    }

                    /* ড্যাশবোর্ডের মেইন হেডিং */
                    .fi-header-heading {
                        font-size: 1.75rem !important;
                        font-weight: 700 !important;
                    }

                    /* টেবিল এবং কার্ডের ভেতরের সাধারণ টেক্সট রিডাবিলিটি */
                    .fi-ta-text, .fi-wi-stats-overview-stat {
                        font-size: 1rem !important;
                    }
                    
                    /* টেবিল হেডার টেক্সট */
                    .fi-ta-header-cell-label {
                        font-size: 0.95rem !important;
                        font-weight: 600 !important;
                    }
                </style>
            '),
        );

        // 🔥 ১. ফন্ট ও সিএসএস রেজিস্টার হুক (আপনার আগের কোডটি এখানে থাকবে)
        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): string => new HtmlString('
                '),
        );

        // 🔥 ২. নতুন হুক: টপবারের বাম পাশে ডাইনামিক ব্যাক বাটন ইনজেকশন
        FilamentView::registerRenderHook(
            'panels::topbar.start',
            function (): string {
                // যদি ব্যবহারকারী একদম মেইন ড্যাশবোর্ডে থাকেন, তবে ব্যাক বাটন দেখানোর প্রয়োজন নেই
                $isDashboard = request()->routeIs('filament.admin.pages.dashboard');
                if ($isDashboard) {
                    return '';
                }

                return new HtmlString('
                    <button 
                        onclick="window.history.length > 1 ? window.history.back() : window.location.href=\'/admin\'" 
                        class="flex items-center justify-center p-2 mr-2 text-gray-500 transition duration-200 rounded-lg hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 focus:outline-none"
                        title="পেছনে যান"
                        style="-webkit-tap-highlight-color: transparent;"
                    >
                        <svg class="w-6 h-6 stroke-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                    </button>
                ');
            },
        );
    }
}