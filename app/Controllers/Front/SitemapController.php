<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Brand;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\Work;

final class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => url('/services'), 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['loc' => url('/works'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => url('/brands'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => url('/about'), 'priority' => '0.7', 'changefreq' => 'yearly'],
            ['loc' => url('/contact'), 'priority' => '0.8', 'changefreq' => 'yearly'],
            ['loc' => url('/blog'), 'priority' => '0.7', 'changefreq' => 'daily'],
            ['loc' => url('/faq'), 'priority' => '0.5', 'changefreq' => 'yearly'],
        ];

        foreach (Service::published() as $service) {
            $urls[] = ['loc' => url('/services/' . $service['slug']), 'lastmod' => $service['updated_at'] ?? null, 'priority' => '0.8', 'changefreq' => 'monthly'];
        }
        foreach (Work::latest(200) as $work) {
            $urls[] = ['loc' => url('/works/' . $work['slug']), 'lastmod' => $work['updated_at'] ?? null, 'priority' => '0.8', 'changefreq' => 'monthly'];
        }
        foreach (Brand::publishedList(null, '', 300, 1)['data'] as $brand) {
            if ((int) ($brand['has_dedicated_page'] ?? 0) !== 1) {
                continue;
            }
            $urls[] = ['loc' => url('/brands/' . $brand['slug']), 'lastmod' => $brand['updated_at'] ?? null, 'priority' => '0.7', 'changefreq' => 'monthly'];
        }
        foreach (Post::latest(200) as $post) {
            $urls[] = ['loc' => url('/blog/' . $post['slug']), 'lastmod' => $post['published_at'] ?? null, 'priority' => '0.6', 'changefreq' => 'yearly'];
        }
        foreach (Page::published() as $page) {
            $urls[] = ['loc' => url('/p/' . $page['slug']), 'lastmod' => $page['updated_at'] ?? null, 'priority' => '0.5', 'changefreq' => 'yearly'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars((string) $url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            if (!empty($url['lastmod']) && !str_starts_with((string) $url['lastmod'], '0000')) {
                $xml .= '    <lastmod>' . date('Y-m-d', (int) strtotime((string) $url['lastmod'])) . "</lastmod>\n";
            }
            $xml .= '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            $xml .= '    <priority>' . $url['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return (new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']))->withCache(3600);
    }

    public function robots(): Response
    {
        $base = rtrim((string) setting('site_url', url('/')), '/');
        $body = "User-agent: *\n"
            . "Allow: /\n"
            . "Disallow: /admin\n"
            . "Disallow: /storage\n"
            . "\n"
            . 'Sitemap: ' . $base . url('/sitemap.xml') . "\n";

        return (new Response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']))->withCache(86400);
    }
}
