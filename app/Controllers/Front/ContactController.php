<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\FrontController;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Faq;
use App\Models\Message;
use App\Models\Service;
use App\Models\Subscriber;

final class ContactController extends FrontController
{
    public function index(): Response
    {
        return $this->view('front.contact', [
            'pageTitle' => 'تماس با ما — ' . setting('site_name', 'نگاه مدیا'),
            'seoTitle'  => (string) setting('contact_seo_title', 'تماس با نگاه مدیا'),
            'seoDesc'   => (string) setting('contact_seo_description', 'برای شروع یک پروژه یا دریافت مشاوره رایگان با ما در تماس باشید.'),
            'services'  => Service::published(),
            'faqs'      => Faq::published('contact'),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = [
            'name'       => (string) $request->input('name', ''),
            'email'      => (string) $request->input('email', ''),
            'phone'      => (string) $request->input('phone', ''),
            'company'    => (string) $request->input('company', ''),
            'subject'    => (string) $request->input('subject', ''),
            'budget'     => (string) $request->input('budget', ''),
            'service_id' => (string) $request->input('service_id', ''),
            'body'       => (string) $request->input('message', ''),
            // تله هرزنامه — باید خالی بماند
            'website'    => (string) $request->input('website', ''),
        ];

        if ($data['website'] !== '') {
            return $this->respond($request, false, 'ارسال پیام ناموفق بود.');
        }

        $validator = Validator::make($data, [
            'name'  => 'required|minlen:2|maxlen:120',
            'phone' => 'required|phone',
            'email' => 'nullable|email|maxlen:190',
            'body'  => 'required|minlen:10|maxlen:4000',
        ], [
            'name'  => 'نام و نام خانوادگی',
            'phone' => 'شماره تماس',
            'email' => 'ایمیل',
            'body'  => 'متن پیام',
        ]);

        if ($validator->fails()) {
            Session::flash('_errors', $validator->errors());
            Session::flash('_old', $request->all());
            Session::flash('danger', $validator->firstError());

            if ($request->wantsJson()) {
                return $this->fail($validator->firstError(), 422, $validator->errors());
            }
            return Response::redirect(url('/contact'));
        }

        $id = Message::create([
            'name'       => trim($data['name']),
            'email'      => $data['email'] !== '' ? trim($data['email']) : null,
            'phone'      => trim($data['phone']),
            'company'    => $data['company'] !== '' ? trim($data['company']) : null,
            'subject'    => $data['subject'] !== '' ? trim($data['subject']) : null,
            'service_id' => $data['service_id'] !== '' ? (int) $data['service_id'] : null,
            'budget'     => $data['budget'] !== '' ? trim($data['budget']) : null,
            'body'       => trim($data['body']),
            'source'     => 'contact',
            'ip'         => $request->ip(),
            'user_agent' => mb_substr($request->userAgent(), 0, 255),
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('message.received', 'messages', $id, 'پیام جدید از فرم تماس: ' . trim($data['name']));
        $this->notify($data);

        Session::flash('success', 'پیام شما ثبت شد. به‌زودی با شما تماس می‌گیریم.');

        if ($request->wantsJson()) {
            return $this->ok('پیام شما با موفقیت ثبت شد.');
        }
        return Response::redirect(url('/contact'));
    }

    public function subscribe(Request $request): Response
    {
        $result = Subscriber::subscribe((string) $request->input('email', ''));

        if ($request->wantsJson()) {
            return $result['ok'] ? $this->ok($result['message']) : $this->fail($result['message']);
        }

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        return $this->back('/');
    }

    /** @param array<string,mixed> $data */
    private function notify(array $data): void
    {
        $to = (string) setting('notify_email', '');
        if ($to === '') {
            $to = (string) Config::get('mail.notify', '');
        }
        if ($to === '') {
            $to = (string) setting('contact_email', '');
        }
        if ($to === '' || !function_exists('mail')) {
            return;
        }
        $subject = 'پیام جدید از سایت ' . setting('site_name', 'نگاه مدیا');
        $body    = "نام: {$data['name']}\nتلفن: {$data['phone']}\nایمیل: {$data['email']}\nموضوع: {$data['subject']}\n\n{$data['body']}";
        $headers = 'From: ' . (string) setting('site_name', 'Negah Media') . ' <no-reply@negahm.ir>' . "\r\n"
            . 'Content-Type: text/plain; charset=UTF-8' . "\r\n";

        @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    }

    private function respond(Request $request, bool $ok, string $message): Response
    {
        if ($request->wantsJson()) {
            return $ok ? $this->ok($message) : $this->fail($message);
        }
        Session::flash($ok ? 'success' : 'danger', $message);
        return Response::redirect(url('/contact'));
    }
}
