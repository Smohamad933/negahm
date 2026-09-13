<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

/**
 * آیتم منو — ساخت منوی سایت از پنل مدیریت
 *
 * هر آیتم می‌تواند به یک صفحه‌ی ثابت سایت، یک رکورد واقعی (صفحه، خدمت،
 * نمونه‌کار، برند، نوشته، دسته‌بندی) یا یک آدرس دلخواه وصل شود. آدرس در
 * زمان خواندن از روی رکورد مرجع ساخته می‌شود تا تغییر اسلاگ، منو را خراب نکند.
 */
final class MenuItem extends Model
{
    protected static string $table = 'menu_items';
    protected static array $fillable = [
        'title', 'type', 'url', 'reference_id', 'position', 'parent_id',
        'show_desktop', 'show_mobile', 'opens_new', 'is_active', 'sort_order',
        'created_at', 'updated_at',
    ];

    /** موقعیت‌های منو */
    public const POSITIONS = [
        'header' => 'منوی بالای سایت',
        'footer' => 'منوی پانوشت',
    ];

    /** انواع آیتم و جدول مرجع هرکدام */
    public const TYPES = [
        'route'          => ['label' => 'صفحه‌های ثابت سایت', 'table' => null],
        'page'           => ['label' => 'صفحه‌ی دلخواه', 'table' => 'pages'],
        'service'        => ['label' => 'خدمت', 'table' => 'services'],
        'work'           => ['label' => 'نمونه‌کار', 'table' => 'works'],
        'work_category'  => ['label' => 'دسته‌بندی نمونه‌کار', 'table' => 'work_categories'],
        'brand'          => ['label' => 'برند', 'table' => 'brands'],
        'brand_category' => ['label' => 'دسته‌بندی برند', 'table' => 'brand_categories'],
        'post'           => ['label' => 'نوشته‌ی وبلاگ', 'table' => 'posts'],
        'post_category'  => ['label' => 'دسته‌بندی وبلاگ', 'table' => 'post_categories'],
        'custom'         => ['label' => 'آدرس دلخواه', 'table' => null],
    ];

    /** صفحه‌های ثابت سایت که می‌توان به منو اضافه کرد */
    public const ROUTES = [
        '/'          => 'خانه',
        '/services'  => 'خدمات',
        '/works'     => 'نمونه‌کارها',
        '/brands'    => 'برندها',
        '/blog'      => 'بلاگ',
        '/about'     => 'درباره ما',
        '/contact'   => 'تماس با ما',
        '/faq'       => 'پرسش‌های متداول',
        '/search'    => 'جست‌وجو',
    ];

    /**
     * ساخت آدرس نهایی یک آیتم.
     * اگر رکورد مرجع حذف شده باشد، به آدرس ذخیره‌شده برمی‌گردیم.
     */
    public static function resolveUrl(array $item): string
    {
        $type = (string) ($item['type'] ?? 'custom');
        $id   = (int) ($item['reference_id'] ?? 0);
        $url  = trim((string) ($item['url'] ?? ''));

        if ($type === 'custom' || $id <= 0) {
            return $url !== '' ? $url : '/';
        }

        $table = self::TYPES[$type]['table'] ?? null;
        if ($table === null) {
            return $url !== '' ? $url : '/';
        }

        $row = DB::first(sprintf(
            'SELECT * FROM %s WHERE id = ?',
            DB::quoteIdent($table)
        ), [$id]);

        if ($row === null) {
            return $url !== '' ? $url : '/';
        }

        $slug = (string) ($row['slug'] ?? '');

        return match ($type) {
            'page'          => '/p/' . $slug,
            'service'       => '/services/' . $slug,
            'work'          => '/works/' . $slug,
            'work_category' => '/works/category/' . $slug,
            'brand'         => '/brands/' . $slug,
            'post'          => '/blog/' . $slug,
            'post_category' => '/blog/category/' . $slug,
            'brand_category' => '/brands?category=' . $id,
            default         => $url !== '' ? $url : '/',
        };
    }

    /** عنوان نمایشی؛ اگر کاربر عنوانی ننوشته باشد، عنوان رکورد مرجع استفاده می‌شود */
    public static function resolveTitle(array $item): string
    {
        $title = trim((string) ($item['title'] ?? ''));
        if ($title !== '') {
            return $title;
        }

        $id    = (int) ($item['reference_id'] ?? 0);
        $table = self::TYPES[(string) ($item['type'] ?? '')]['table'] ?? null;
        if ($table !== null && $id > 0) {
            // ستون عنوان در جدول‌ها یکی نیست: pages/services/works عنوان دارند
            // و brands نام؛ پس همه ستون‌ها را می‌خوانیم و هرکدام بود برمی‌داریم.
            $row = DB::first(sprintf(
                'SELECT * FROM %s WHERE id = ?',
                DB::quoteIdent($table)
            ), [$id]);
            if ($row !== null) {
                return (string) ($row['title'] ?? $row['name'] ?? '');
            }
        }

        return self::ROUTES[(string) ($item['url'] ?? '')] ?? (string) ($item['url'] ?? '');
    }

    /**
     * آیتم‌های فعال یک موقعیت، به‌صورت درخت (فرزندان داخل children).
     *
     * @param string $position header|footer
     * @param string|null $device 'desktop' یا 'mobile'؛ null یعنی هر دو
     * @return array<int,array<string,mixed>>
     */
    public static function tree(string $position = 'header', ?string $device = null): array
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE is_active = 1 AND position = ?',
            DB::quoteIdent('menu_items')
        );
        $params = [$position];

        if ($device === 'desktop') {
            $sql .= ' AND show_desktop = 1';
        } elseif ($device === 'mobile') {
            $sql .= ' AND show_mobile = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';

        $rows = DB::select($sql, $params);
        foreach ($rows as &$row) {
            $row['href']  = self::resolveUrl($row);
            $row['label'] = self::resolveTitle($row);
            $row['children'] = [];
        }
        unset($row);

        return self::buildTree($rows);
    }

    /** چیدن ردیف‌های تخت به درخت بر اساس parent_id */
    public static function buildTree(array $rows): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $tree = [];
        foreach ($byId as $id => $row) {
            $parent = (int) ($row['parent_id'] ?? 0);
            if ($parent > 0 && isset($byId[$parent])) {
                $byId[$parent]['children'][] = &$byId[$id];
            } else {
                $tree[] = &$byId[$id];
            }
        }
        unset($row);

        return $tree;
    }

    /**
     * منوی پیش‌فرض سایت.
     *
     * وقتی هنوز هیچ آیتمی در پنل ساخته نشده باشد (مثلاً نصب تازه یا نصبی که
     * seed روی آن اجرا نشده) سایت بدون منو نمی‌ماند.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function defaultTree(): array
    {
        $items = [];
        foreach (self::ROUTES as $path => $label) {
            if ($path === '/search') {
                continue;
            }
            $items[] = [
                'id'        => 0,
                'label'     => $label,
                'href'      => $path,
                'opens_new' => 0,
                'parent_id' => null,
                'children'  => [],
            ];
        }

        return $items;
    }

    /** همه‌ی آیتم‌های یک موقعیت برای نمایش در پنل (تخت، با عنوان فرزندان) */
    public static function adminList(string $position = 'header'): array
    {
        $rows = DB::select(sprintf(
            'SELECT * FROM %s WHERE position = ? ORDER BY sort_order ASC, id ASC',
            DB::quoteIdent('menu_items')
        ), [$position]);

        foreach ($rows as &$row) {
            $row['href']  = self::resolveUrl($row);
            $row['label'] = self::resolveTitle($row);
        }
        unset($row);

        return $rows;
    }

    /** بزرگ‌ترین sort_order فعلی یک موقعیت */
    public static function nextSort(string $position = 'header'): int
    {
        return 1 + (int) DB::scalar(sprintf(
            'SELECT COALESCE(MAX(sort_order), 0) FROM %s WHERE position = ?',
            DB::quoteIdent('menu_items')
        ), [$position]);
    }

    /**
     * شماره‌گذاری پیوستهٔ ۱..n برای یک موقعیت، بر پایهٔ ترتیب فعلی.
     * sort_order مساوی بین آیتم‌ها ممکن است (مثلاً از seed) و جابه‌جایی را
     * بی‌اثر می‌کند؛ پس پیش از هر جابه‌جایی ترتیب را یکتا می‌کنیم.
     */
    public static function renumber(string $position): void
    {
        $rows = DB::select(sprintf(
            'SELECT id FROM %s WHERE position = ? ORDER BY sort_order ASC, id ASC',
            DB::quoteIdent('menu_items')
        ), [$position]);

        $order = 1;
        foreach ($rows as $row) {
            DB::update('menu_items', [
                'sort_order' => $order++,
                'updated_at' => self::now(),
            ], 'id = ?', [(int) $row['id']]);
        }
    }

    /**
     * جابه‌جایی یک آیتم با همسایهٔ بالایی یا پایینی در همان موقعیت.
     * @return bool false یعنی همسایه‌ای نبود (آیتم اول/آخر است)
     */
    public static function move(int $id, string $direction): bool
    {
        $item = self::find($id);
        if ($item === null) {
            return false;
        }

        $position = (string) $item['position'];
        $up       = $direction === 'up';
        self::renumber($position);

        // بعد از شماره‌گذاری، ترتیب آیتم ممکن است عوض شده باشد
        $item = self::find($id);
        $sort = (int) $item['sort_order'];

        $neighbor = DB::first(sprintf(
            'SELECT * FROM %s WHERE position = ? AND sort_order %s ? ORDER BY sort_order %s, id %s LIMIT 1',
            DB::quoteIdent('menu_items'),
            $up ? '<' : '>',
            $up ? 'DESC' : 'ASC',
            $up ? 'DESC' : 'ASC'
        ), [$position, $sort]);

        if ($neighbor === null) {
            return false;
        }

        $stamp = self::now();
        DB::update('menu_items', ['sort_order' => (int) $neighbor['sort_order'], 'updated_at' => $stamp], 'id = ?', [$id]);
        DB::update('menu_items', ['sort_order' => $sort, 'updated_at' => $stamp], 'id = ?', [(int) $neighbor['id']]);

        return true;
    }

    /** @return array<string,mixed> */
    public static function prepare(array $input, ?int $ignoreId = null): array
    {
        $type = (string) ($input['type'] ?? 'custom');
        if (!isset(self::TYPES[$type])) {
            $type = 'custom';
        }

        $position = (string) ($input['position'] ?? 'header');
        if (!isset(self::POSITIONS[$position])) {
            $position = 'header';
        }

        $referenceId = (int) ($input['reference_id'] ?? 0);
        $table = self::TYPES[$type]['table'];
        if ($table === null || $referenceId <= 0) {
            $referenceId = 0;
        }

        $url = trim((string) ($input['url'] ?? ''));
        if ($type === 'route') {
            $url = (string) ($input['url'] ?? '/');
            if (!isset(self::ROUTES[$url])) {
                $url = '/';
            }
        }
        if ($url === '' && $type === 'custom') {
            $url = '/';
        }

        $parentId = (int) ($input['parent_id'] ?? 0);
        if ($ignoreId !== null && $parentId === (int) $ignoreId) {
            $parentId = 0; // یک آیتم نمی‌تواند زیرمجموعه‌ی خودش باشد
        }

        return [
            'title'        => trim((string) ($input['title'] ?? '')),
            'type'         => $type,
            'url'          => $url !== '' ? mb_substr($url, 0, 255) : null,
            'reference_id' => $referenceId ?: null,
            'position'     => $position,
            'parent_id'    => $parentId ?: null,
            'show_desktop' => (int) !empty($input['show_desktop']),
            'show_mobile'  => (int) !empty($input['show_mobile']),
            'opens_new'    => (int) !empty($input['opens_new']),
            'is_active'    => (int) !empty($input['is_active']),
            'sort_order'   => (int) ($input['sort_order'] ?? 0),
            'updated_at'   => self::now(),
        ];
    }
}
