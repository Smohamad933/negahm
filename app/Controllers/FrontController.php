<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Models\BrandCategory;
use App\Models\Font;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Service;
use App\Models\WorkCategory;

/**
 * کلاس پایه کنترلرهای سمت سایت
 */
abstract class FrontController extends Controller
{
    public function __construct()
    {
        // منو از پنل مدیریت می‌آید؛ اگر خالی باشد، منوی پیش‌فرض جایگزین می‌شود
        $menuDesktop = MenuItem::tree('header', 'desktop');
        $menuMobile  = MenuItem::tree('header', 'mobile');
        $menuFooter  = MenuItem::tree('footer');

        View::share([
            'siteName'      => (string) setting('site_name', 'نگاه مدیا'),
            'siteTagline'   => (string) setting('site_tagline', 'آژانس خلاق و تبلیغاتی'),
            'sitePhone'     => (string) setting('contact_phone', ''),
            'siteEmail'     => (string) setting('contact_email', ''),
            'siteAddress'   => (string) setting('contact_address', ''),
            'siteWhatsapp'  => (string) setting('contact_whatsapp', ''),
            'siteHours'     => (string) setting('contact_hours', ''),
            'siteMapEmbed'  => (string) setting('contact_map', ''),
            'socials'       => social_links(),
            'menuDesktop'   => $menuDesktop !== [] ? $menuDesktop : MenuItem::defaultTree(),
            'menuMobile'    => $menuMobile !== [] ? $menuMobile : MenuItem::defaultTree(),
            'menuFooter'    => $menuFooter !== [] ? $menuFooter : MenuItem::defaultTree(),
            'fontCss'       => Font::faceCss(),
            'menuPages'     => Page::forMenu(),
            'footerPages'   => Page::forFooter(),
            'navServices'   => Service::published(),
            'navBrands'     => BrandCategory::published(),
            'navWorks'      => WorkCategory::published(),
            'footerAbout'   => (string) setting('footer_about', ''),
            'analytics'     => (string) setting('analytics_code', ''),
            'seoDefault'    => [
                'title'       => (string) setting('seo_title', 'نگاه مدیا | آژانس خلاق و تبلیغاتی'),
                'description' => (string) setting('seo_description', 'از ایده تا اجرا؛ هویت بصری، تولید محتوا، کمپین و دیجیتال مارکتینگ را یکپارچه می‌سازیم.'),
                'keywords'    => (string) setting('seo_keywords', ''),
                'image'       => (string) setting('seo_image', ''),
            ],
        ]);
    }
}
