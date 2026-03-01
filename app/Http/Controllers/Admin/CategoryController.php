<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->withCount('posts')
            ->orderByDesc('show_in_navbar')
            ->orderBy('navbar_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $category = Category::create([
            'name' => $data['name'],
            'slug' => $this->resolveUniqueSlug($data['slug'] ?? $data['name']),
            'description' => $data['description'] ?? null,
            'show_in_navbar' => (bool) ($data['show_in_navbar'] ?? false),
            'navbar_order' => (int) ($data['navbar_order'] ?? 0),
        ]);

        return response()->json([
            'message' => 'Categoria criada com sucesso.',
            'data' => $category,
        ], Response::HTTP_CREATED);
    }

    public function show(int $category): JsonResponse
    {
        $categoryModel = Category::query()->withCount('posts')->findOrFail($category);

        return response()->json([
            'data' => $categoryModel,
        ]);
    }

    public function update(UpdateCategoryRequest $request, int $category): JsonResponse
    {
        $categoryModel = Category::query()->findOrFail($category);
        $data = $request->validated();

        $categoryModel->update([
            'name' => $data['name'],
            'slug' => $this->resolveUniqueSlug($data['slug'] ?? $data['name'], $categoryModel->id),
            'description' => $data['description'] ?? null,
            'show_in_navbar' => (bool) ($data['show_in_navbar'] ?? false),
            'navbar_order' => (int) ($data['navbar_order'] ?? 0),
        ]);

        return response()->json([
            'message' => 'Categoria atualizada com sucesso.',
            'data' => $categoryModel->fresh(),
        ]);
    }

    public function destroy(int $category): JsonResponse
    {
        $categoryModel = Category::query()->withCount('posts')->findOrFail($category);

        if ($categoryModel->posts_count > 0) {
            return response()->json([
                'message' => 'Não é possível remover categoria em uso por notícias.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $categoryModel->delete();

        return response()->json([
            'message' => 'Categoria removida com sucesso.',
        ]);
    }

    private function resolveUniqueSlug(string $slugSource, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slugSource);

        if ($baseSlug === '') {
            $baseSlug = 'categoria';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            Category::query()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
