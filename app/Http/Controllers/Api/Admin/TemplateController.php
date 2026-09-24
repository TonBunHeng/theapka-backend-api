<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTemplateRequest;
use App\Http\Requests\Admin\UpdateTemplateRequest;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Template::query();

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
        }

        $filters = $request->query('filter', []);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['is_premium'])) {
            $query->where('is_premium', filter_var($filters['is_premium'], FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $templates = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => TemplateResource::collection($templates->items()),
            'meta' => [
                'page' => $templates->currentPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
                'last_page' => $templates->lastPage(),
            ],
        ]);
    }

    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $template = Template::create($request->validated());

        return response()->json([
            'data' => new TemplateResource($template),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $template = Template::findOrFail($id);

        return response()->json([
            'data' => new TemplateResource($template),
        ]);
    }

    public function update(UpdateTemplateRequest $request, int $id): JsonResponse
    {
        $template = Template::findOrFail($id);
        $template->update($request->validated());

        return response()->json([
            'data' => new TemplateResource($template),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $template = Template::findOrFail($id);
        $template->delete();

        return response()->json([
            'data' => [
                'message' => 'Template deleted successfully.',
            ],
        ]);
    }

    public function publish(int $id): JsonResponse
    {
        $template = Template::findOrFail($id);
        $template->update(['status' => 'published', 'is_active' => true]);

        return response()->json([
            'data' => [
                'message' => 'Template published successfully.',
                'template' => new TemplateResource($template),
            ],
        ]);
    }

    public function retire(int $id): JsonResponse
    {
        $template = Template::findOrFail($id);
        $template->update(['status' => 'retired', 'is_active' => false]);

        return response()->json([
            'data' => [
                'message' => 'Template retired successfully.',
                'template' => new TemplateResource($template),
            ],
        ]);
    }
}
