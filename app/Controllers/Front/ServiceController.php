<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\FrontController;
use App\Core\Response;
use App\Models\Brand;
use App\Models\Faq;
use App\Models\ProcessStep;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\Work;

final class ServiceController extends FrontController
{
    public function index(): Response
    {
        return $this->view('front.services', [
            'pageTitle'  => 'خدمات — ' . setting('site_name', 'نگاه مدیا'),
            'seoTitle'   => (string) setting('services_seo_title', 'خدمات آژانس تبلیغاتی نگاه مدیا'),
            'seoDesc'    => (string) setting('services_seo_description', 'هویت بصری، تولید محتوا، دیجیتال مارکتینگ، کمپین تبلیغاتی، طراحی وب و مشاوره استراتژی.'),
            'services'   => Service::published(),
            'process'    => ProcessStep::published(),
            'testimonials' => Testimonial::published(4),
        ]);
    }

    public function show(string $slug): Response
    {
        $service = Service::publishedBySlug($slug);
        if ($service === null) {
            return $this->notFound('خدمتی با این آدرس پیدا نشد.');
        }

        return $this->view('front.service', [
            'pageTitle'  => (string) ($service['seo_title'] ?: $service['title'] . ' — خدمات'),
            'seoTitle'   => (string) ($service['seo_title'] ?: ''),
            'seoDesc'    => (string) ($service['seo_description'] ?: excerpt((string) ($service['excerpt'] ?: $service['body']), 180)),
            'service'    => $service,
            'items'      => Service::decodeItems($service['items'] ?? null),
            'works'      => Work::publishedList(['service_id' => (int) $service['id']], 6, 1),
            'others'     => array_values(array_filter(Service::published(), static fn ($s) => (int) $s['id'] !== (int) $service['id'])),
            'faqs'       => Faq::published('services'),
            'brands'     => Brand::featured(6),
            'process'    => ProcessStep::published(),
        ]);
    }
}
