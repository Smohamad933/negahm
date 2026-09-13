<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\FrontController;
use App\Core\Response;
use App\Models\Faq;

final class FaqController extends FrontController
{
    public function index(): Response
    {
        return $this->view('front.faq', [
            'pageTitle' => 'سوالات متداول — ' . setting('site_name', 'نگاه مدیا'),
            'seoTitle'  => (string) setting('faq_seo_title', 'سوالات متداول همکاری با نگاه مدیا'),
            'seoDesc'   => (string) setting('faq_seo_description', 'پاسخ پرسش‌های رایج درباره فرآیند همکاری، هزینه و زمان تحویل پروژه‌ها.'),
            'faqs'      => Faq::published(),
        ]);
    }
}
