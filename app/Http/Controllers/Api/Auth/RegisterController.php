<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\RoleName;
use App\Enums\WeddingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\WeddingResource;
use App\Models\Invitation;
use App\Models\Template;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /**
     * Register a new couple account and initialize their wedding workspace.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'is_active' => true,
            ]);

            $user->assignRole(RoleName::USER->value);

            // Create initial wedding
            $weddingTitle = $request->wedding_title ?? ($request->name . ' Wedding');
            $slugBase = Str::slug($weddingTitle);
            $slug = $slugBase ?: 'wedding';
            if (Wedding::withoutGlobalScopes()->where('slug', $slug)->exists()) {
                $slug .= '-' . Str::random(5);
            }

            $wedding = Wedding::create([
                'owner_id' => $user->id,
                'slug' => strtolower($slug),
                'title' => $weddingTitle,
                'wedding_date' => $request->wedding_date,
                'timezone' => 'Asia/Phnom_Penh',
                'status' => WeddingStatus::DRAFT,
            ]);

            // Create wedding details
            WeddingDetail::create([
                'wedding_id' => $wedding->id,
                'groom_name' => '',
                'bride_name' => '',
            ]);

            // Create default draft invitation
            $defaultTemplate = Template::where('status', 'published')->first();
            Invitation::create([
                'wedding_id' => $wedding->id,
                'template_id' => $defaultTemplate?->id,
                'slug' => $wedding->slug,
                'title' => $wedding->title,
                'status' => 'draft',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'wedding' => $wedding,
                'token' => $token,
            ];
        });

        return response()->json([
            'data' => [
                'token' => $result['token'],
                'user' => new UserResource($result['user']),
                'wedding' => new WeddingResource($result['wedding']),
                'role' => RoleName::USER->value,
                'permissions' => [],
            ],
        ], 201);
    }
}
