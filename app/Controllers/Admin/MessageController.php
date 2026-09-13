<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\Message;

final class MessageController extends AdminController
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');

        return $this->view('admin.messages.index', [
            'pageTitle' => 'صندوق پیام‌ها — پنل نگاه مدیا',
            'pager'     => Message::paginateFiltered([
                'status' => $status,
                'q'      => trim((string) $request->query('q', '')),
            ], 15, max(1, $request->integer('page', 1))),
            'status'    => $status,
            'q'         => trim((string) $request->query('q', '')),
            'counts'    => [
                'unread'   => Message::unreadCount(),
                'inbox'    => Message::count('is_archived = 0'),
                'archived' => Message::count('is_archived = 1'),
            ],
        ]);
    }

    public function show(int $id): Response
    {
        $message = Message::withService($id);
        if ($message === null) {
            return $this->notFound('پیام پیدا نشد.');
        }

        if ((int) $message['is_read'] === 0) {
            Message::markRead($id, true);
            $message['is_read'] = 1;
        }

        return $this->view('admin.messages.show', [
            'pageTitle' => 'پیام: ' . $message['name'] . ' — پنل نگاه مدیا',
            'message'   => $message,
        ]);
    }

    public function markRead(Request $request, int $id): Response
    {
        if (Message::find($id) === null) {
            return $this->fail('پیام پیدا نشد.', 404);
        }

        $read = $request->input('read', '1');
        Message::markRead($id, in_array((string) $read, ['1', 'true', 'on'], true));

        if ($request->wantsJson()) {
            return $this->ok('وضعیت خوانده‌شدن بروزرسانی شد');
        }
        Session::flash('success', 'وضعیت پیام بروزرسانی شد.');
        return $this->back('/admin/messages');
    }

    public function archive(Request $request, int $id): Response
    {
        if (Message::find($id) === null) {
            return $this->fail('پیام پیدا نشد.', 404);
        }

        $archive = $request->input('archived', '1');
        Message::archive($id, in_array((string) $archive, ['1', 'true', 'on'], true));

        if ($request->wantsJson()) {
            return $this->ok('پیام بایگانی شد');
        }
        Session::flash('success', 'وضعیت بایگانی بروزرسانی شد.');
        return $this->back('/admin/messages');
    }

    public function destroy(int $id): Response
    {
        $message = Message::find($id);
        if ($message === null) {
            return $this->notFound('پیام پیدا نشد.');
        }

        Message::deleteById($id);
        ActivityLog::record('message.delete', 'messages', $id, 'حذف پیام «' . $message['name'] . '»');
        Session::flash('success', 'پیام حذف شد.');

        return Response::redirect(url('/admin/messages'));
    }
}
