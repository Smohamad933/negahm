<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\Setting;

final class SettingController extends AdminController
{
    /** @var array<int,array{key:string,label:string,type:string,group:string,hint?:string}> */
    private array $schema = [
        ['key' => 'site_name', 'label' => 'نام سایت', 'type' => 'string', 'group' => 'general'],
        ['key' => 'site_tagline', 'label' => 'شعار کوتاه', 'type' => 'string', 'group' => 'general'],
        ['key' => 'site_logo', 'label' => 'لوگو', 'type' => 'image', 'group' => 'general'],
        ['key' => 'site_favicon', 'label' => 'فاوآیکون', 'type' => 'image', 'group' => 'general'],
        ['key' => 'site_url', 'label' => 'آدرس سایت', 'type' => 'string', 'group' => 'general', 'hint' => 'مثال: https://negahm.ir'],

        ['key' => 'hero_title', 'label' => 'عنوان بخش معرفی', 'type' => 'string', 'group' => 'home'],
        ['key' => 'hero_lead', 'label' => 'متن معرفی', 'type' => 'text', 'group' => 'home'],
        ['key' => 'hero_kicker', 'label' => 'جمله بالایی هدر', 'type' => 'string', 'group' => 'home'],
        ['key' => 'home_quote', 'label' => 'نقل‌قول صفحه اصلی', 'type' => 'text', 'group' => 'home'],
        ['key' => 'about_short', 'label' => 'متن کوتاه درباره ما', 'type' => 'text', 'group' => 'home'],

        ['key' => 'clients_title', 'label' => 'عنوان بخش برندها', 'type' => 'string', 'group' => 'home'],
        ['key' => 'clients_lead', 'label' => 'متن بخش برندها', 'type' => 'text', 'group' => 'home'],
        ['key' => 'services_kicker', 'label' => 'عنوان بالایی بخش خدمات', 'type' => 'string', 'group' => 'home'],
        ['key' => 'services_title', 'label' => 'عنوان بخش خدمات', 'type' => 'string', 'group' => 'home'],
        ['key' => 'services_lead', 'label' => 'متن بخش خدمات', 'type' => 'text', 'group' => 'home'],
        ['key' => 'about_kicker', 'label' => 'عنوان بالایی بخش نگاه ما', 'type' => 'string', 'group' => 'home'],
        ['key' => 'about_title', 'label' => 'عنوان بخش نگاه ما', 'type' => 'string', 'group' => 'home'],
        ['key' => 'work_kicker', 'label' => 'عنوان بالایی بخش نمونه‌کار', 'type' => 'string', 'group' => 'home'],
        ['key' => 'work_title', 'label' => 'عنوان بخش نمونه‌کار', 'type' => 'string', 'group' => 'home'],
        ['key' => 'process_kicker', 'label' => 'عنوان بالایی بخش فرآیند', 'type' => 'string', 'group' => 'home'],
        ['key' => 'process_title', 'label' => 'عنوان بخش فرآیند', 'type' => 'string', 'group' => 'home'],
        ['key' => 'process_lead', 'label' => 'متن بخش فرآیند', 'type' => 'text', 'group' => 'home'],
        ['key' => 'cta_title', 'label' => 'عنوان بخش تماس پایانی', 'type' => 'string', 'group' => 'home'],
        ['key' => 'cta_lead', 'label' => 'متن بخش تماس پایانی', 'type' => 'text', 'group' => 'home'],
        ['key' => 'header_cta_text', 'label' => 'متن دکمه هدر', 'type' => 'string', 'group' => 'home'],
        ['key' => 'header_cta_url', 'label' => 'آدرس دکمه هدر', 'type' => 'string', 'group' => 'home'],

        ['key' => 'about_intro', 'label' => 'متن معرفی (درباره ما)', 'type' => 'text', 'group' => 'about'],
        ['key' => 'about_story', 'label' => 'داستان ما', 'type' => 'text', 'group' => 'about'],
        ['key' => 'about_image', 'label' => 'تصویر درباره ما', 'type' => 'image', 'group' => 'about'],
        ['key' => 'about_values', 'label' => 'ارزش‌ها (هر خط یکی)', 'type' => 'lines', 'group' => 'about'],

        ['key' => 'contact_phone', 'label' => 'تلفن تماس', 'type' => 'string', 'group' => 'contact'],
        ['key' => 'contact_email', 'label' => 'ایمیل تماس', 'type' => 'string', 'group' => 'contact'],
        ['key' => 'contact_whatsapp', 'label' => 'شماره واتس‌اپ', 'type' => 'string', 'group' => 'contact'],
        ['key' => 'contact_address', 'label' => 'آدرس', 'type' => 'text', 'group' => 'contact'],
        ['key' => 'contact_hours', 'label' => 'ساعات کاری', 'type' => 'string', 'group' => 'contact'],
        ['key' => 'contact_map', 'label' => 'کد امبد نقشه', 'type' => 'text', 'group' => 'contact'],
        ['key' => 'notify_email', 'label' => 'ایمیل دریافت پیام‌ها', 'type' => 'string', 'group' => 'contact'],

        ['key' => 'social_instagram', 'label' => 'اینستاگرام', 'type' => 'string', 'group' => 'social'],
        ['key' => 'social_telegram', 'label' => 'تلگرام', 'type' => 'string', 'group' => 'social'],
        ['key' => 'social_linkedin', 'label' => 'لینکدین', 'type' => 'string', 'group' => 'social'],
        ['key' => 'social_twitter', 'label' => 'توییتر / ایکس', 'type' => 'string', 'group' => 'social'],
        ['key' => 'social_youtube', 'label' => 'یوتیوب', 'type' => 'string', 'group' => 'social'],
        ['key' => 'social_whatsapp', 'label' => 'واتس‌اپ', 'type' => 'string', 'group' => 'social'],
        ['key' => 'social_behance', 'label' => 'بیهنس', 'type' => 'string', 'group' => 'social'],
        ['key' => 'social_aparats', 'label' => 'آپارات', 'type' => 'string', 'group' => 'social'],

        ['key' => 'seo_title', 'label' => 'عنوان پیش‌فرض سئو', 'type' => 'string', 'group' => 'seo'],
        ['key' => 'seo_description', 'label' => 'توضیحات پیش‌فرض سئو', 'type' => 'text', 'group' => 'seo'],
        ['key' => 'seo_keywords', 'label' => 'کلمات کلیدی', 'type' => 'text', 'group' => 'seo'],
        ['key' => 'seo_image', 'label' => 'تصویر اشتراک‌گذاری', 'type' => 'image', 'group' => 'seo'],
        ['key' => 'analytics_code', 'label' => 'کد گوگل آنالیتیکس', 'type' => 'text', 'group' => 'seo'],

        ['key' => 'home_title', 'label' => 'عنوان صفحه اصلی', 'type' => 'string', 'group' => 'pages'],
        ['key' => 'brands_seo_title', 'label' => 'عنوان سئو — برندها', 'type' => 'string', 'group' => 'pages'],
        ['key' => 'brands_seo_description', 'label' => 'توضیحات سئو — برندها', 'type' => 'text', 'group' => 'pages'],
        ['key' => 'works_seo_title', 'label' => 'عنوان سئو — نمونه‌کارها', 'type' => 'string', 'group' => 'pages'],
        ['key' => 'works_seo_description', 'label' => 'توضیحات سئو — نمونه‌کارها', 'type' => 'text', 'group' => 'pages'],
        ['key' => 'services_seo_title', 'label' => 'عنوان سئو — خدمات', 'type' => 'string', 'group' => 'pages'],
        ['key' => 'services_seo_description', 'label' => 'توضیحات سئو — خدمات', 'type' => 'text', 'group' => 'pages'],
        ['key' => 'about_seo_title', 'label' => 'عنوان سئو — درباره ما', 'type' => 'string', 'group' => 'pages'],
        ['key' => 'about_seo_description', 'label' => 'توضیحات سئو — درباره ما', 'type' => 'text', 'group' => 'pages'],
        ['key' => 'contact_seo_title', 'label' => 'عنوان سئو — تماس', 'type' => 'string', 'group' => 'pages'],
        ['key' => 'contact_seo_description', 'label' => 'توضیحات سئو — تماس', 'type' => 'text', 'group' => 'pages'],
        ['key' => 'blog_seo_title', 'label' => 'عنوان سئو — وبلاگ', 'type' => 'string', 'group' => 'pages'],
        ['key' => 'blog_seo_description', 'label' => 'توضیحات سئو — وبلاگ', 'type' => 'text', 'group' => 'pages'],
        ['key' => 'faq_seo_title', 'label' => 'عنوان سئو — پرسش‌های متداول', 'type' => 'string', 'group' => 'pages'],
        ['key' => 'faq_seo_description', 'label' => 'توضیحات سئو — پرسش‌های متداول', 'type' => 'text', 'group' => 'pages'],

        ['key' => 'footer_about', 'label' => 'متن فوتر', 'type' => 'text', 'group' => 'general'],
        ['key' => 'footer_note', 'label' => 'یادداشت پایین فوتر', 'type' => 'string', 'group' => 'general'],
    ];

    public function index(Request $request): Response
    {
        $group = (string) $request->query('group', 'general');
        $groups = [];
        foreach ($this->schema as $field) {
            $groups[$field['group']] = true;
        }

        return $this->view('admin.settings.index', [
            'pageTitle' => 'تنظیمات سایت — پنل نگاه مدیا',
            'schema'    => $this->schema,
            'values'    => settings_all(),
            'group'     => $group,
            'groups'    => array_keys($groups),
        ]);
    }

    public function update(Request $request): Response
    {
        // فرم تنها فیلدهای همان گروهی را می‌فرستد که باز شده است؛
        // بدون این فیلتر، ذخیره یک گروه بقیه تنظیمات را پاک می‌کرد.
        $group = (string) $request->input('group', 'general');

        foreach ($this->schema as $field) {
            if ($field['group'] !== $group) {
                continue;
            }

            $key  = $field['key'];
            $type = $field['type'];

            if ($type === 'image') {
                if ($request->hasFile($key . '_file')) {
                    $result = $this->uploadFile($request->file($key . '_file'), 'settings');
                    if ($result['ok']) {
                        Setting::put($key, $result['path'], 'string', $field['group'], $field['label']);
                    } else {
                        Session::flash('warning', (string) ($result['error'] ?? 'بارگذاری تصویر ناموفق بود.'));
                    }
                } elseif ($request->input($key . '_remove')) {
                    Setting::put($key, '', 'string', $field['group'], $field['label']);
                }
                continue;
            }

            if ($type === 'lines') {
                $lines = preg_split('/\R+/u', (string) $request->input($key, '')) ?: [];
                $lines = array_values(array_filter(array_map('trim', $lines), static fn ($v) => $v !== ''));
                Setting::put($key, $lines, 'json', $field['group'], $field['label']);
                continue;
            }

            $value = trim((string) $request->input($key, ''));
            Setting::put($key, $value, $type === 'text' ? 'text' : 'string', $field['group'], $field['label']);
        }

        ActivityLog::record('settings.update', 'settings', null, 'تنظیمات سایت بروزرسانی شد');
        Session::flash('success', 'تنظیمات با موفقیت ذخیره شد.');

        return Response::redirect(url('/admin/settings?group=' . urlencode((string) $request->input('group', 'general'))));
    }
}
