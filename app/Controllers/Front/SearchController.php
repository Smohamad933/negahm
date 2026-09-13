<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\FrontController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Brand;
use App\Models\Post;
use App\Models\Service;
use App\Models\Work;

final class SearchController extends FrontController
{
    public function index(Request $request): Response
    {
        $q     = trim((string) $request->query('q', ''));
        $page  = max(1, $request->integer('page', 1));
        $type  = (string) $request->query('type', 'all');

        $results = [
            'works'    => [],
            'brands'   => [],
            'services' => [],
            'posts'    => [],
        ];
        $total = 0;

        if ($q !== '') {
            $like = '%' . $q . '%';

            if ($type === 'all' || $type === 'works') {
                $results['works'] = Work::publishedList(['q' => $q], 12, $page)['data'];
            }
            if ($type === 'all' || $type === 'brands') {
                $results['brands'] = Brand::publishedList(null, $q, 12, $page)['data'];
            }
            if ($type === 'all' || $type === 'posts') {
                $results['posts'] = Post::publishedList(['q' => $q], 12, $page)['data'];
            }
            if ($type === 'all' || $type === 'services') {
                $results['services'] = array_values(array_filter(
                    Service::published(),
                    static fn (array $s): bool => mb_stripos((string) $s['title'], $q) !== false
                        || mb_stripos((string) $s['excerpt'], $q) !== false
                ));
            }

            unset($like);
            $total = count($results['works']) + count($results['brands'])
                + count($results['services']) + count($results['posts']);
        }

        return $this->view('front.search', [
            'pageTitle' => 'جست‌وجو — ' . setting('site_name', 'نگاه مدیا'),
            'q'         => $q,
            'type'      => $type,
            'results'   => $results,
            'total'     => $total,
        ]);
    }
}
