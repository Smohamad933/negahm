<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Faq;
use App\Models\ProcessStep;
use App\Models\Slider;
use App\Models\Stat;
use App\Models\TeamMember;
use App\Models\Testimonial;

final class ContentController extends AdminController
{
    /* ------------------------------------------------------------- اعضای تیم */

    public function team(): Response
    {
        return $this->view('admin.content.team', [
            'pageTitle' => 'اعضای تیم — پنل نگاه مدیا',
            'items'     => TeamMember::all('sort_order ASC, id ASC'),
        ]);
    }

    public function storeTeam(Request $request): Response
    {
        $data = [
            'name'       => (string) $request->input('name', ''),
            'role'       => (string) $request->input('role', ''),
            'bio'        => (string) $request->input('bio', ''),
            'email'      => (string) $request->input('email', ''),
            'socials'    => (array) $request->input('socials', []),
            'sort_order' => $request->integer('sort_order', 0),
            'is_published' => $request->boolean('is_published'),
        ];

        $validator = Validator::make($data, [
            'name'  => 'required|minlen:2|maxlen:190',
            'email' => 'nullable|email',
        ], ['name' => 'نام عضو', 'email' => 'ایمیل']);

        if ($validator->fails()) {
            Session::flash('danger', $validator->firstError());
            return Response::redirect(url('/admin/team'));
        }

        $prepared = TeamMember::prepare($data);

        if ($request->hasFile('photo')) {
            $photo = $this->uploadFile($request->file('photo'), 'team');
            if ($photo['ok']) {
                $prepared['photo'] = $photo['path'];
            }
        }
        $prepared['created_at'] = date('Y-m-d H:i:s');

        $id = TeamMember::create($prepared);
        ActivityLog::record('team.create', 'team_members', $id, 'عضو تیم «' . $prepared['name'] . '» اضافه شد');
        Session::flash('success', 'عضو تیم اضافه شد.');

        return Response::redirect(url('/admin/team'));
    }

    public function destroyTeam(int $id): Response
    {
        if (TeamMember::find($id) === null) {
            return $this->notFound('عضو تیم پیدا نشد.');
        }
        TeamMember::deleteById($id);
        ActivityLog::record('team.delete', 'team_members', $id, 'حذف عضو تیم');
        Session::flash('success', 'عضو تیم حذف شد.');
        return Response::redirect(url('/admin/team'));
    }

    /* --------------------------------------------------------- نظرات مشتریان */

    public function testimonials(): Response
    {
        return $this->view('admin.content.testimonials', [
            'pageTitle' => 'نظرات مشتریان — پنل نگاه مدیا',
            'items'     => Testimonial::all('sort_order ASC, id DESC'),
        ]);
    }

    public function storeTestimonial(Request $request): Response
    {
        $data = [
            'name'       => (string) $request->input('name', ''),
            'role'       => (string) $request->input('role', ''),
            'company'    => (string) $request->input('company', ''),
            'quote'      => (string) $request->input('quote', ''),
            'rating'     => $request->integer('rating', 5),
            'sort_order' => $request->integer('sort_order', 0),
            'is_published' => $request->boolean('is_published'),
        ];

        $validator = Validator::make($data, [
            'name'  => 'required|minlen:2|maxlen:190',
            'quote' => 'required|minlen:5',
        ], ['name' => 'نام', 'quote' => 'متن نظر']);

        if ($validator->fails()) {
            Session::flash('danger', $validator->firstError());
            return Response::redirect(url('/admin/testimonials'));
        }

        $prepared = Testimonial::prepare($data);
        if ($request->hasFile('avatar')) {
            $avatar = $this->uploadFile($request->file('avatar'), 'team');
            if ($avatar['ok']) {
                $prepared['avatar'] = $avatar['path'];
            }
        }
        $prepared['created_at'] = date('Y-m-d H:i:s');

        $id = Testimonial::create($prepared);
        ActivityLog::record('testimonial.create', 'testimonials', $id, 'نظر «' . $prepared['name'] . '» اضافه شد');
        Session::flash('success', 'نظر مشتری اضافه شد.');

        return Response::redirect(url('/admin/testimonials'));
    }

    public function destroyTestimonial(int $id): Response
    {
        if (Testimonial::find($id) === null) {
            return $this->notFound('نظر پیدا نشد.');
        }
        Testimonial::deleteById($id);
        ActivityLog::record('testimonial.delete', 'testimonials', $id, 'حذف نظر مشتری');
        Session::flash('success', 'نظر حذف شد.');
        return Response::redirect(url('/admin/testimonials'));
    }

    /* ------------------------------------------------------------ آمار سایت */

    public function stats(): Response
    {
        return $this->view('admin.content.stats', [
            'pageTitle' => 'شمارنده‌های آماری — پنل نگاه مدیا',
            'items'     => Stat::all('sort_order ASC, id ASC'),
        ]);
    }

    public function storeStat(Request $request): Response
    {
        $data = [
            'label'       => (string) $request->input('label', ''),
            'value'       => (string) $request->input('value', ''),
            'suffix'      => (string) $request->input('suffix', ''),
            'description' => (string) $request->input('description', ''),
            'sort_order'  => $request->integer('sort_order', 0),
            'is_published' => $request->boolean('is_published'),
        ];

        $validator = Validator::make($data, [
            'label' => 'required|maxlen:190',
            'value' => 'required|maxlen:60',
        ], ['label' => 'عنوان', 'value' => 'مقدار']);

        if ($validator->fails()) {
            Session::flash('danger', $validator->firstError());
            return Response::redirect(url('/admin/stats'));
        }

        $id = Stat::create(Stat::prepare($data));
        ActivityLog::record('stat.create', 'stats', $id, 'شمارنده «' . trim($data['label']) . '» اضافه شد');
        Session::flash('success', 'شمارنده اضافه شد.');

        return Response::redirect(url('/admin/stats'));
    }

    public function destroyStat(int $id): Response
    {
        if (Stat::find($id) === null) {
            return $this->notFound('شمارنده پیدا نشد.');
        }
        Stat::deleteById($id);
        Session::flash('success', 'شمارنده حذف شد.');
        return Response::redirect(url('/admin/stats'));
    }

    /* -------------------------------------------------------- مراحل همکاری */

    public function process(): Response
    {
        return $this->view('admin.content.process', [
            'pageTitle' => 'مراحل همکاری — پنل نگاه مدیا',
            'items'     => ProcessStep::all('sort_order ASC, id ASC'),
        ]);
    }

    public function storeProcess(Request $request): Response
    {
        $data = [
            'title'      => (string) $request->input('title', ''),
            'body'       => (string) $request->input('body', ''),
            'number'     => (string) $request->input('number', ''),
            'sort_order' => $request->integer('sort_order', 0),
            'is_published' => $request->boolean('is_published'),
        ];

        $validator = Validator::make($data, ['title' => 'required|maxlen:190'], ['title' => 'عنوان مرحله']);
        if ($validator->fails()) {
            Session::flash('danger', $validator->firstError());
            return Response::redirect(url('/admin/process'));
        }

        $id = ProcessStep::create(ProcessStep::prepare($data));
        ActivityLog::record('process.create', 'process_steps', $id, 'مرحله «' . trim($data['title']) . '» اضافه شد');
        Session::flash('success', 'مرحله اضافه شد.');

        return Response::redirect(url('/admin/process'));
    }

    public function destroyProcess(int $id): Response
    {
        if (ProcessStep::find($id) === null) {
            return $this->notFound('مرحله پیدا نشد.');
        }
        ProcessStep::deleteById($id);
        Session::flash('success', 'مرحله حذف شد.');
        return Response::redirect(url('/admin/process'));
    }

    /* ------------------------------------------------------------- اسلایدر */

    public function sliders(): Response
    {
        return $this->view('admin.content.sliders', [
            'pageTitle' => 'اسلایدر صفحه اصلی — پنل نگاه مدیا',
            'items'     => Slider::all('sort_order ASC, id ASC'),
        ]);
    }

    public function storeSlider(Request $request): Response
    {
        $data = [
            'title'       => (string) $request->input('title', ''),
            'subtitle'    => (string) $request->input('subtitle', ''),
            'link'        => (string) $request->input('link', ''),
            'button_text' => (string) $request->input('button_text', ''),
            'sort_order'  => $request->integer('sort_order', 0),
            'is_active'   => $request->boolean('is_active'),
        ];

        $prepared = Slider::prepare($data);
        if ($request->hasFile('image')) {
            $image = $this->uploadFile($request->file('image'), 'sliders');
            if ($image['ok']) {
                $prepared['image'] = $image['path'];
            }
        }
        $prepared['created_at'] = date('Y-m-d H:i:s');

        $id = Slider::create($prepared);
        ActivityLog::record('slider.create', 'sliders', $id, 'اسلاید «' . ($prepared['title'] ?? 'بدون عنوان') . '» اضافه شد');
        Session::flash('success', 'اسلاید اضافه شد.');

        return Response::redirect(url('/admin/sliders'));
    }

    public function destroySlider(int $id): Response
    {
        if (Slider::find($id) === null) {
            return $this->notFound('اسلاید پیدا نشد.');
        }
        Slider::deleteById($id);
        Session::flash('success', 'اسلاید حذف شد.');
        return Response::redirect(url('/admin/sliders'));
    }

    /* ---------------------------------------------------- سوالات متداول */

    public function faqs(): Response
    {
        return $this->view('admin.content.faqs', [
            'pageTitle' => 'سوالات متداول — پنل نگاه مدیا',
            'items'     => Faq::all('sort_order ASC, id ASC'),
        ]);
    }

    public function storeFaq(Request $request): Response
    {
        $data = [
            'question'   => (string) $request->input('question', ''),
            'answer'     => (string) $request->input('answer', ''),
            'group'      => (string) $request->input('group', ''),
            'sort_order' => $request->integer('sort_order', 0),
            'is_published' => $request->boolean('is_published'),
        ];

        $validator = Validator::make($data, [
            'question' => 'required|maxlen:255',
            'answer'   => 'required',
        ], ['question' => 'سوال', 'answer' => 'پاسخ']);

        if ($validator->fails()) {
            Session::flash('danger', $validator->firstError());
            return Response::redirect(url('/admin/faqs'));
        }

        $prepared = Faq::prepare($data);
        $prepared['created_at'] = date('Y-m-d H:i:s');

        $id = Faq::create($prepared);
        ActivityLog::record('faq.create', 'faqs', $id, 'سوال متداول جدید اضافه شد');
        Session::flash('success', 'سوال متداول اضافه شد.');

        return Response::redirect(url('/admin/faqs'));
    }

    public function destroyFaq(int $id): Response
    {
        if (Faq::find($id) === null) {
            return $this->notFound('سوال پیدا نشد.');
        }
        Faq::deleteById($id);
        Session::flash('success', 'سوال حذف شد.');
        return Response::redirect(url('/admin/faqs'));
    }
}
