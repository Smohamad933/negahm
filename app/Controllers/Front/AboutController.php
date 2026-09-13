<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\FrontController;
use App\Core\Response;
use App\Models\Brand;
use App\Models\ProcessStep;
use App\Models\Stat;
use App\Models\TeamMember;
use App\Models\Testimonial;

final class AboutController extends FrontController
{
    public function index(): Response
    {
        return $this->view('front.about', [
            'pageTitle'    => 'درباره ما — ' . setting('site_name', 'نگاه مدیا'),
            'seoTitle'     => (string) setting('about_seo_title', 'درباره نگاه مدیا'),
            'seoDesc'      => (string) setting('about_seo_description', 'ما فقط تبلیغ نمی‌کنیم؛ روایت می‌سازیم.'),
            'aboutIntro'   => (string) setting('about_intro', ''),
            'aboutStory'   => (string) setting('about_story', ''),
            'values'       => (array) (setting('about_values', []) ?: []),
            'stats'        => Stat::published(),
            'team'         => TeamMember::published(),
            'process'      => ProcessStep::published(),
            'testimonials' => Testimonial::published(6),
            'brands'       => Brand::featured(12),
            'marquee'      => Brand::marquee(40),
        ]);
    }
}
