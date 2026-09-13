<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\Visit;

/**
 * آمار بازدید سایت — تعداد بازدید هر صفحه، نمودار روزانه و پاک‌سازی داده‌ها
 */
final class AnalyticsController extends AdminController
{
    /** بازه‌های مجاز (روز) */
    private const RANGES = [7 => '۷ روز گذشته', 30 => '۳۰ روز گذشته', 90 => '۹۰ روز گذشته', 365 => 'یک سال گذشته'];

    public function index(Request $request): Response
    {
        $days = $request->integer('days', 30);
        if (!isset(self::RANGES[$days])) {
            $days = 30;
        }

        $page  = max(1, $request->integer('page', 1));
        $pager = Visit::pageStats($days, 20, $page);

        return $this->view('admin.analytics.index', [
            'pageTitle' => 'آمار بازدید — پنل نگاه مدیا',
            'days'      => $days,
            'ranges'    => self::RANGES,
            'pager'     => $pager,
            'daily'     => Visit::dailyCounts(min($days, 30)),
            'summary'   => [
                'today'   => Visit::todayCount(),
                'range'   => Visit::totalInRange($days),
                'unique'  => Visit::uniqueVisitors($days),
                'all'     => Visit::totalCount(),
            ],
            'top'       => Visit::topPaths($days, 10),
        ]);
    }

    /** پاک‌سازی رکوردهای قدیمی‌تر از N روز */
    public function prune(Request $request): Response
    {
        $days = $request->integer('days', 180);
        if ($days < 7) {
            Session::flash('danger', 'حداقل بازه‌ی نگهداری ۷ روز است.');

            return Response::redirect(url('/admin/analytics'));
        }

        $removed = Visit::prune($days);
        ActivityLog::record('analytics.prune', 'visit_logs', null, $removed . ' رکورد قدیمی آمار پاک شد');
        Session::flash('success', $removed . ' رکورد قدیمی‌تر از ' . $days . ' روز پاک شد.');

        return Response::redirect(url('/admin/analytics'));
    }
}
