<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Message;
use App\Models\User;

final class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return $this->view('admin.login', [
            'pageTitle' => 'ورود به پنل — ' . setting('site_name', 'نگاه مدیا'),
        ]);
    }

    public function login(Request $request): Response
    {
        $identifier = trim((string) $request->input('username', ''));
        $password   = (string) $request->input('password', '');

        $validator = Validator::make(
            ['username' => $identifier, 'password' => $password],
            ['username' => 'required', 'password' => 'required'],
            ['username' => 'نام کاربری یا ایمیل', 'password' => 'رمز عبور']
        );

        if ($validator->fails()) {
            Session::flash('_old', ['username' => $identifier]);
            Session::flash('danger', $validator->firstError());
            return Response::redirect(url('/admin/login'));
        }

        $locked = Auth::isLocked($identifier);
        if ($locked > 0) {
            Session::flash('danger', 'به دلیل تلاش‌های ناموفق، ورود شما موقتاً مسدود است. حدود '
                . \App\Core\Str::toPersianDigits((int) ceil($locked / 60)) . ' دقیقه دیگر تلاش کنید.');
            return Response::redirect(url('/admin/login'));
        }

        if (!Auth::attempt($identifier, $password, $request->boolean('remember'))) {
            Session::flash('_old', ['username' => $identifier]);
            Session::flash('danger', 'نام کاربری یا رمز عبور اشتباه است.');
            return Response::redirect(url('/admin/login'));
        }

        ActivityLog::record('auth.login', 'users', Auth::id(), 'ورود به پنل مدیریت');

        $intended = (string) Session::get('intended_url', '');
        Session::forget('intended_url');
        Session::flash('success', 'خوش آمدید، ' . (string) (Auth::user()['name'] ?? ''));

        return Response::redirect($intended !== '' && str_starts_with($intended, '/admin') ? url($intended) : url('/admin'));
    }

    public function logout(): Response
    {
        ActivityLog::record('auth.logout', 'users', Auth::id(), 'خروج از پنل مدیریت');
        Auth::logout();
        return Response::redirect(url('/admin/login'));
    }

    public function profile(): Response
    {
        $user = Auth::user();
        if ($user === null) {
            throw new \App\Core\HttpException(403);
        }

        return $this->view('admin.profile', [
            'pageTitle'    => 'پروفایل کاربری — پنل نگاه مدیا',
            'user'         => $user,
            'activities'   => ActivityLog::latest(10),
            'unreadCount'  => Message::unreadCount(),
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        $user = Auth::user();
        if ($user === null) {
            throw new \App\Core\HttpException(403);
        }

        $data = [
            'name'     => (string) $request->input('name', ''),
            'email'    => (string) $request->input('email', ''),
            'bio'      => (string) $request->input('bio', ''),
            'password' => (string) $request->input('password', ''),
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
        ];

        $rules = [
            'name'  => 'required|minlen:2|maxlen:120',
            'email' => 'nullable|email|maxlen:190|unique:users,email,' . (int) $user['id'],
        ];
        $labels = ['name' => 'نام و نام خانوادگی', 'email' => 'ایمیل', 'password' => 'رمز عبور'];

        if ($data['password'] !== '') {
            $rules['password'] = 'required|minlen:8|confirmed';
        }

        $validator = Validator::make($data, $rules, $labels);
        if ($validator->fails()) {
            Session::flash('_errors', $validator->errors());
            Session::flash('_old', $data);
            Session::flash('danger', $validator->firstError());
            return Response::redirect(url('/admin/profile'));
        }

        User::updateById((int) $user['id'], [
            'name'       => trim($data['name']),
            'email'      => $data['email'] !== '' ? trim($data['email']) : null,
            'bio'        => $data['bio'] !== '' ? trim($data['bio']) : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($data['password'] !== '') {
            User::updatePassword((int) $user['id'], $data['password']);
        }

        ActivityLog::record('profile.update', 'users', (int) $user['id'], 'بروزرسانی پروفایل کاربری');
        Session::flash('success', 'پروفایل شما با موفقیت بروزرسانی شد.');

        return Response::redirect(url('/admin/profile'));
    }
}
