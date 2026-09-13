<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Str;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\PostCategory;

final class PostController extends AdminController
{
    public function index(Request $request): Response
    {
        return $this->view('admin.posts.index', [
            'pageTitle'  => 'مدیریت مقالات — پنل نگاه مدیا',
            'pager'      => Post::publishedList(['q' => trim((string) $request->query('q', ''))], 15, max(1, $request->integer('page', 1))),
            'categories' => PostCategory::options(),
            'q'          => trim((string) $request->query('q', '')),
        ]);
    }

    public function create(): Response
    {
        return $this->view('admin.posts.form', [
            'pageTitle'  => 'افزودن مقاله — پنل نگاه مدیا',
            'post'       => null,
            'categories' => PostCategory::options(),
            'action'     => url('/admin/posts'),
            'heading'    => 'افزودن مقاله جدید',
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->collect($request);

        $validator = Validator::make($data, [
            'title' => 'required|minlen:3|maxlen:255',
            'slug'  => 'nullable|slug|maxlen:190',
        ], ['title' => 'عنوان مقاله', 'slug' => 'آدرس یکتا (نامک)']);

        if ($validator->fails()) {
            return $this->withErrors($validator, url('/admin/posts/create'), $request->all());
        }

        $prepared = Post::prepare($data, null, Auth::id() ?: null);

        if ($request->hasFile('cover')) {
            $cover = $this->uploadFile($request->file('cover'), 'posts');
            if ($cover['ok']) {
                $prepared['cover'] = $cover['path'];
            }
        }

        $prepared['created_at'] = date('Y-m-d H:i:s');
        $id = Post::create($prepared);

        ActivityLog::record('post.create', 'posts', $id, 'مقاله «' . $prepared['title'] . '» منتشر شد');
        Session::flash('success', 'مقاله «' . $prepared['title'] . '» ایجاد شد.');

        return Response::redirect(url('/admin/posts/' . $id . '/edit'));
    }

    public function edit(int $id): Response
    {
        $post = Post::adminById($id);
        if ($post === null) {
            return $this->notFound('مقاله پیدا نشد.');
        }

        return $this->view('admin.posts.form', [
            'pageTitle'  => 'ویرایش مقاله — پنل نگاه مدیا',
            'post'       => $post,
            'categories' => PostCategory::options(),
            'action'     => url('/admin/posts/' . $id),
            'heading'    => 'ویرایش مقاله: ' . $post['title'],
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $post = Post::find($id);
        if ($post === null) {
            return $this->notFound('مقاله پیدا نشد.');
        }

        $data = $this->collect($request);

        $validator = Validator::make($data, [
            'title' => 'required|minlen:3|maxlen:255',
            'slug'  => 'nullable|slug|maxlen:190',
        ], ['title' => 'عنوان مقاله', 'slug' => 'آدرس یکتا (نامک)']);

        if ($validator->fails()) {
            return $this->withErrors($validator, url('/admin/posts/' . $id . '/edit'), $request->all());
        }

        $prepared = Post::prepare($data, $id, (int) ($post['author_id'] ?? Auth::id()));

        if ($request->hasFile('cover')) {
            $cover = $this->uploadFile($request->file('cover'), 'posts');
            if ($cover['ok']) {
                $prepared['cover'] = $cover['path'];
            }
        } elseif ($request->input('remove_cover')) {
            $prepared['cover'] = null;
        }

        Post::updateById($id, $prepared);
        ActivityLog::record('post.update', 'posts', $id, 'مقاله «' . $prepared['title'] . '» ویرایش شد');
        Session::flash('success', 'تغییرات مقاله ذخیره شد.');

        return Response::redirect(url('/admin/posts/' . $id . '/edit'));
    }

    public function destroy(int $id): Response
    {
        $post = Post::find($id);
        if ($post === null) {
            return $this->notFound('مقاله پیدا نشد.');
        }

        Post::deleteById($id);
        ActivityLog::record('post.delete', 'posts', $id, 'مقاله «' . $post['title'] . '» حذف شد');
        Session::flash('success', 'مقاله حذف شد.');

        return Response::redirect(url('/admin/posts'));
    }

    /* --------------------------------------------------- دسته‌بندی مقالات */

    public function categories(): Response
    {
        return $this->view('admin.categories.posts', [
            'pageTitle'  => 'دسته‌بندی مقالات — پنل نگاه مدیا',
            'categories' => PostCategory::all('sort_order ASC, id ASC'),
        ]);
    }

    public function storeCategory(Request $request): Response
    {
        $title = trim((string) $request->input('title', ''));

        $validator = Validator::make(['title' => $title], ['title' => 'required|minlen:2|maxlen:190|unique:post_categories,title'], ['title' => 'عنوان دسته‌بندی']);
        if ($validator->fails()) {
            Session::flash('danger', $validator->firstError());
            return Response::redirect(url('/admin/post-categories'));
        }

        $id = PostCategory::create([
            'title'       => $title,
            'slug'        => Str::uniqueSlug('post_categories', (string) $request->input('slug', '') ?: $title),
            'description' => trim((string) $request->input('description', '')) ?: null,
            'sort_order'  => $request->integer('sort_order', 0),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::record('postCategory.create', 'post_categories', $id, 'دسته‌بندی مقاله «' . $title . '» ایجاد شد');
        Session::flash('success', 'دسته‌بندی اضافه شد.');

        return Response::redirect(url('/admin/post-categories'));
    }

    public function updateCategory(Request $request, int $id): Response
    {
        $category = PostCategory::find($id);
        if ($category === null) {
            return $this->notFound('دسته‌بندی پیدا نشد.');
        }

        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            Session::flash('danger', 'عنوان الزامی است.');
            return Response::redirect(url('/admin/post-categories'));
        }

        PostCategory::updateById($id, [
            'title'       => $title,
            'slug'        => Str::uniqueSlug('post_categories', (string) $request->input('slug', '') ?: $title, $id),
            'description' => trim((string) $request->input('description', '')) ?: null,
            'sort_order'  => $request->integer('sort_order', 0),
        ]);

        Session::flash('success', 'دسته‌بندی بروزرسانی شد.');
        return Response::redirect(url('/admin/post-categories'));
    }

    public function destroyCategory(int $id): Response
    {
        $category = PostCategory::find($id);
        if ($category === null) {
            return $this->notFound('دسته‌بندی پیدا نشد.');
        }

        PostCategory::deleteById($id);
        Session::flash('success', 'دسته‌بندی حذف شد.');
        return Response::redirect(url('/admin/post-categories'));
    }

    /** @return array<string,mixed> */
    private function collect(Request $request): array
    {
        return [
            'title'        => (string) $request->input('title', ''),
            'slug'         => (string) $request->input('slug', ''),
            'excerpt'      => (string) $request->input('excerpt', ''),
            'body'         => (string) $request->input('body', ''),
            'category_id'  => (string) $request->input('category_id', ''),
            'tags'         => (string) $request->input('tags', ''),
            'is_featured'  => $request->boolean('is_featured'),
            'is_published' => $request->boolean('is_published'),
            'published_at' => (string) $request->input('published_at', ''),
            'seo_title'    => (string) $request->input('seo_title', ''),
            'seo_description' => (string) $request->input('seo_description', ''),
        ];
    }

    private function withErrors(Validator $validator, string $to, array $old): Response
    {
        Session::flash('_errors', $validator->errors());
        Session::flash('_old', $old);
        Session::flash('danger', $validator->firstError());
        return Response::redirect($to);
    }
}
