<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\FrontController;
use App\Core\Response;
use App\Models\Brand;
use App\Models\Post;
use App\Models\ProcessStep;
use App\Models\Service;
use App\Models\Slider;
use App\Models\Stat;
use App\Models\Testimonial;
use App\Models\Work;

final class HomeController extends FrontController
{
    public function index(): Response
    {
        return $this->view('front.home', [
            'pageTitle'  => (string) setting('home_title', (string) setting('site_name', 'نگاه مدیا')),
            'heroTitle'  => (string) setting('hero_title', 'برندت را *قابلِ دیده‌شدن* کن.'),
            'heroLead'   => (string) setting('hero_lead', 'از ایده تا اجرا؛ هویت بصری، تولید محتوا، کمپین و دیجیتال مارکتینگ را یکپارچه می‌سازیم تا برند شما فقط دیده نشود، بلکه در ذهن بماند.'),
            'heroKicker' => (string) setting('hero_kicker', 'ایده‌ای که دیده می‌شود، اثری که می‌ماند.'),
            'sliders'    => Slider::active(),
            'services'   => Service::published(),
            'stats'      => Stat::published(),
            'process'    => ProcessStep::published(),
            'brands'     => Brand::featured(12),
            'marquee'    => Brand::marquee(40),
            'works'      => Work::featured(6) ?: Work::latest(6),
            'testimonials' => Testimonial::published(6),
            'posts'      => Post::latest(3),
            'aboutText'  => (string) setting('about_short', ''),
            'quote'      => (string) setting('home_quote', 'همکاری خوب از جایی شروع می‌شود که یک تیم فقط سفارش را اجرا نکند؛ مسئله را بفهمد و برایش راه‌حل بسازد.'),
        ]);
    }
}
