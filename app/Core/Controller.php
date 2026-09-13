<?php

declare(strict_types=1);

namespace App\Core;

/**
 * کلاس پایه کنترلرها
 */
abstract class Controller
{
    /** @param array<string,mixed> $data */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return new Response(View::render($template, $data), $status);
    }

    protected function redirect(string $to, int $status = 302): Response
    {
        return Response::redirect($to, $status);
    }

    protected function back(string $default = '/'): Response
    {
        return Response::redirect(Request::instance()->referer($default), 302);
    }

    /**
     * تغییر مسیر همراه با پیام فلاش
     * @param array<string,mixed> $flash
     */
    protected function redirectWith(string $to, string $type, string $message, array $flash = []): Response
    {
        Session::flash($type, $message);
        foreach ($flash as $key => $value) {
            Session::flash($key, $value);
        }
        return Response::redirect($to);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function ok(string $message = 'انجام شد', array $extra = []): Response
    {
        return Response::json(['ok' => true, 'message' => $message] + $extra);
    }

    protected function fail(string $message, int $status = 422, array $errors = []): Response
    {
        return Response::json(['ok' => false, 'message' => $message, 'errors' => $errors], $status);
    }

    /** @param array<string,mixed> $data */
    protected function notFound(string $message = 'صفحه پیدا نشد'): Response
    {
        return $this->view('errors.404', ['message' => $message], 404);
    }

    protected function abort(int $status, string $message = ''): never
    {
        throw new HttpException($status, $message);
    }
}
