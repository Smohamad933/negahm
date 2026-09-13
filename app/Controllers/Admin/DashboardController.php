<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Media;
use App\Models\Message;
use App\Models\Post;
use App\Models\Service;
use App\Models\Subscriber;
use App\Models\Visit;
use App\Models\Work;

final class DashboardController extends AdminController
{
    public function index(): Response
    {
        $visits  = Visit::dailyCounts(14);
        $maxVisit = max(1, max($visits === [] ? [1] : $visits));

        return $this->view('admin.dashboard', [
            'pageTitle' => 'داشبورد — پنل نگاه مدیا',
            'stats'     => [
                'brands'      => Brand::count('1=1'),
                'brandsPub'   => Brand::count('is_published = 1'),
                'works'       => Work::count('1=1'),
                'worksPub'    => Work::count('is_published = 1'),
                'services'    => Service::count('1=1'),
                'posts'       => Post::count('1=1'),
                'messages'    => Message::count('is_archived = 0'),
                'unread'      => Message::unreadCount(),
                'media'       => \App\Models\Media::count('1=1'),
                'mediaSize'   => Media::totalSize(),
                'subscribers' => Subscriber::count('1=1'),
                'visitsToday' => Visit::todayCount(),
                'visitsTotal' => Visit::totalCount(),
            ],
            'visits'        => $visits,
            'maxVisit'      => $maxVisit,
            'topPaths'      => Visit::topPaths(30, 6),
            'recentMessages' => Message::paginateFiltered(['status' => 'unread'], 6, 1)['data'],
            'recentWorks'   => Work::latest(5, false),
            'recentBrands'  => Brand::adminList('', 5, 1)['data'],
            'activities'    => ActivityLog::latest(12),
            'subscribers'   => Subscriber::latest(5),
        ]);
    }

    public function activity(Request $request): Response
    {
        return $this->view('admin.activity', [
            'pageTitle' => 'گزارش فعالیت‌ها — پنل نگاه مدیا',
            'pager'     => ActivityLog::paginateLogs(30, max(1, $request->integer('page', 1))),
        ]);
    }

    public function subscribers(Request $request): Response
    {
        $page  = max(1, $request->integer('page', 1));
        $total = Subscriber::count('1=1');
        $per   = 25;

        return $this->view('admin.subscribers', [
            'pageTitle' => 'اعضای خبرنامه — پنل نگاه مدیا',
            'items'     => Subscriber::all('id DESC'),
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $per,
            'lastPage'  => max(1, (int) ceil($total / $per)),
        ]);
    }
}
