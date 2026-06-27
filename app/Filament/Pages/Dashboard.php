<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    // 🔥 বাংলায় নাম এবং টাইটেল লক করে দেওয়া হলো
    protected static ?string $navigationLabel = 'ড্যাশবোর্ড';
    protected static ?string $title = 'ড্যাশবোর্ড';
    protected static ?int $navigationSort = 1;
}