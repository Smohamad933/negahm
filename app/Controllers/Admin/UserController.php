<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\User;

final class UserController extends AdminController
{
    public function index(): Response
    {
        $this->requireAdmin();

        return $this->view('admin.users.index', [
            'pageTitle' => 'مدیریت کاربران — پنل نگاه مدیا',
            'users'     => User::all('id ASC'),
        ]);
    }

    public function store(Request $request): Response
    {
        $this->requireAdmin();

        $data = [
            'name'     => (string) $request->input('name', ''),
            'username' => (string) $request->input('username', ''),
            'email'    => (string) $request->input('email', ''),
            'password' => (string) $request->input('password', ''),
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
            'role'     => (string) $request->input('role', 'editor'),
            'is_active' => $request->boolean('is_active'),
        ];

        $validator = Validator::make($data, [
            'name'     => 'required|minlen:2|maxlen:120',
            'username' => 'required|minlen:3|maxlen:60|regex:/^[a-zA-Z0-9_.]+$/|unique:users,username',
            'email'    => 'nullable|email|unique:users,email',
            'password' => 'required|minlen:8|confirmed',
            'role'     => 'required|in:admin,editor',
        ], [
            'name'     => 'نام و نام خانوادگی',
            'username' => 'نام کاربری',
            'email'    => 'ایمیل',
            'password' => 'رمز عبور',
            'role'     => 'نقش',
        ]);

        if ($validator->fails()) {
            Session::flash('_errors', $validator->errors());
            Session::flash('_old', $data);
            Session::flash('danger', $validator->firstError());
            return Response::redirect(url('/admin/users'));
        }

        $id = User::register([
            'name'       => trim($data['name']),
            'username'   => strtolower(trim($data['username'])),
            'email'      => $data['email'] !== '' ? trim($data['email']) : null,
            'password'   => $data['password'],
            'role'       => $data['role'],
            'is_active'  => $data['is_active'] ? 1 : 0,
        ]);

        ActivityLog::record('user.create', 'users', $id, 'کاربر «' . $data['username'] . '» ایجاد شد');
        Session::flash('success', 'کاربر «' . $data['username'] . '» ایجاد شد.');

        return Response::redirect(url('/admin/users'));
    }

    public function update(Request $request, int $id): Response
    {
        $this->requireAdmin();

        $user = User::find($id);
        if ($user === null) {
            return $this->notFound('کاربر پیدا نشد.');
        }

        $data = [
            'name'     => (string) $request->input('name', ''),
            'email'    => (string) $request->input('email', ''),
            'password' => (string) $request->input('password', ''),
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
            'role'     => (string) $request->input('role', 'editor'),
            'is_active' => $request->boolean('is_active'),
        ];

        $rules = [
            'name'  => 'required|minlen:2|maxlen:120',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'role'  => 'required|in:admin,editor',
        ];
        if ($data['password'] !== '') {
            $rules['password'] = 'required|minlen:8|confirmed';
        }

        $validator = Validator::make($data, $rules, [
            'name' => 'نام و نام خانوادگی', 'email' => 'ایمیل', 'role' => 'نقش', 'password' => 'رمز عبور',
        ]);

        if ($validator->fails()) {
            Session::flash('danger', $validator->firstError());
            return Response::redirect(url('/admin/users'));
        }

        // جلوگیری از غیرفعال کردن یا تغییر نقش آخرین مدیر
        if ((int) $user['id'] === Auth::id() && (!$data['is_active'] || $data['role'] !== 'admin')) {
            Session::flash('danger', 'نمی‌توانید نقش یا وضعیت حساب خودتان را تغییر دهید.');
            return Response::redirect(url('/admin/users'));
        }

        User::updateById($id, [
            'name'       => trim($data['name']),
            'email'      => $data['email'] !== '' ? trim($data['email']) : null,
            'role'       => $data['role'],
            'is_active'  => $data['is_active'] ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($data['password'] !== '') {
            User::updatePassword($id, $data['password']);
        }

        ActivityLog::record('user.update', 'users', $id, 'ویرایش کاربر «' . $user['username'] . '»');
        Session::flash('success', 'اطلاعات کاربر بروزرسانی شد.');

        return Response::redirect(url('/admin/users'));
    }

    public function destroy(int $id): Response
    {
        $this->requireAdmin();

        if ($id === Auth::id()) {
            Session::flash('danger', 'نمی‌توانید حساب کاربری خودتان را حذف کنید.');
            return Response::redirect(url('/admin/users'));
        }

        $user = User::find($id);
        if ($user === null) {
            return $this->notFound('کاربر پیدا نشد.');
        }

        $admins = User::count("role = 'admin' AND is_active = 1");
        if ((string) $user['role'] === 'admin' && $admins <= 1) {
            Session::flash('danger', 'حذف آخرین مدیر ممکن نیست.');
            return Response::redirect(url('/admin/users'));
        }

        User::deleteById($id);
        ActivityLog::record('user.delete', 'users', $id, 'حذف کاربر «' . $user['username'] . '»');
        Session::flash('success', 'کاربر حذف شد.');

        return Response::redirect(url('/admin/users'));
    }
}
