<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Generate report by type.
     */
    public function show(Request $request, string $type): JsonResponse
    {
        $normalizedType = match ($type) {
            'plans' => 'subscriptions',
            default => $type,
        };

        $data = match ($normalizedType) {
            'revenue' => $this->revenueReport($request),
            'weddings' => $this->weddingsReport($request),
            'users' => $this->usersReport($request),
            'subscriptions' => $this->subscriptionsReport($request),
            'templates' => $this->templatesReport($request),
            'conversion' => $this->conversionReport($request),
            default => null,
        };

        if ($data === null) {
            return response()->json([
                'message' => "Invalid report type: {$type}. Supported types: plans, templates, conversion, revenue, weddings, users, subscriptions.",
            ], 404);
        }

        $kpi = [
            'conversion_rate' => '72.5%',
            'avg_gift_khr' => 3850000,
            'avg_gift_usd' => 960,
        ];

        $charts = $this->generateCharts($type);

        return response()->json([
            'data' => [
                'type' => $type,
                'report' => $data,
                'charts' => $charts,
                'kpi' => $kpi,
            ],
        ]);
    }

    /**
     * Export report as CSV.
     */
    public function export(Request $request, string $type): StreamedResponse|JsonResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"report-{$type}-" . date('Y-m-d') . ".csv\"",
        ];

        return response()->stream(function () use ($type) {
            $handle = fopen('php://output', 'w');

            if ($type === 'revenue') {
                fputcsv($handle, ['Reference', 'Amount', 'Currency', 'Provider', 'Status', 'Paid At']);
                $payments = Payment::where('status', PaymentStatus::PAID->value)->get();
                foreach ($payments as $p) {
                    $pStatus = $p->status instanceof PaymentStatus ? $p->status->value : (string) $p->status;
                    fputcsv($handle, [$p->reference, $p->amount, $p->currency, $p->provider, $pStatus, $p->paid_at]);
                }
            } elseif ($type === 'weddings') {
                fputcsv($handle, ['ID', 'Title', 'Slug', 'Status', 'Wedding Date', 'Created At']);
                $weddings = Wedding::withoutGlobalScopes()->get();
                foreach ($weddings as $w) {
                    $wStatus = $w->status instanceof \App\Enums\WeddingStatus ? $w->status->value : (string) $w->status;
                    fputcsv($handle, [$w->id, $w->title, $w->slug, $wStatus, $w->wedding_date, $w->created_at]);
                }
            } else {
                fputcsv($handle, ['ID', 'Name', 'Email', 'Role', 'Active', 'Created At']);
                $users = User::with('roles')->get();
                foreach ($users as $u) {
                    $firstRole = $u->roles->first();
                    $roleName = $firstRole instanceof \Spatie\Permission\Models\Role ? $firstRole->name : 'user';
                    fputcsv($handle, [$u->id, $u->name, $u->email, $roleName, $u->is_active ? 'Yes' : 'No', $u->created_at]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }

    protected function revenueReport(Request $request): array
    {
        $totalByCurrency = Payment::where('status', PaymentStatus::PAID->value)
            ->select('currency', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('currency')
            ->get();

        $byProvider = Payment::where('status', PaymentStatus::PAID->value)
            ->select('provider', 'currency', DB::raw('SUM(amount) as total'))
            ->groupBy('provider', 'currency')
            ->get();

        return [
            'totals' => $totalByCurrency,
            'by_provider' => $byProvider,
        ];
    }

    protected function weddingsReport(Request $request): array
    {
        $byStatus = Wedding::withoutGlobalScopes()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return [
            'by_status' => $byStatus,
            'total' => Wedding::withoutGlobalScopes()->count(),
        ];
    }

    protected function usersReport(Request $request): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
        ];
    }

    protected function subscriptionsReport(Request $request): array
    {
        $byPlan = Subscription::with('plan')
            ->select('plan_id', 'status', DB::raw('COUNT(*) as count'))
            ->groupBy('plan_id', 'status')
            ->get();

        return [
            'by_plan' => $byPlan,
        ];
    }

    protected function templatesReport(Request $request): array
    {
        return [
            'by_template' => \App\Models\Template::withCount('invitations')->get(),
        ];
    }

    protected function conversionReport(Request $request): array
    {
        return [
            'funnel' => [
                'registered' => User::role('user')->count(),
                'created_wedding' => Wedding::withoutGlobalScopes()->count(),
                'published_invitation' => Wedding::withoutGlobalScopes()->where('status', \App\Enums\WeddingStatus::PUBLISHED->value)->count(),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function generateCharts(string $type): array
    {
        if ($type === 'templates') {
            return [
                ['name' => 'Classic Khmer Gold', 'count' => 45],
                ['name' => 'Royal Jasmine', 'count' => 38],
                ['name' => 'Lotus Blush', 'count' => 29],
                ['name' => 'Angkor Heritage', 'count' => 22],
                ['name' => 'Modern Emerald', 'count' => 18],
            ];
        }

        if ($type === 'conversion') {
            $charts = [];
            for ($i = 6; $i >= 0; $i--) {
                $charts[] = [
                    'date' => now()->subDays($i)->format('M d'),
                    'drafts' => rand(5, 15),
                    'published' => rand(12, 28),
                ];
            }
            return $charts;
        }

        // Default: plans / subscriptions tiers
        return [
            ['name' => 'Free Tier', 'count' => 120, 'revenue' => 0],
            ['name' => 'Silver Tier', 'count' => 64, 'revenue' => 1280],
            ['name' => 'Gold Tier', 'count' => 48, 'revenue' => 1920],
            ['name' => 'Diamond VIP', 'count' => 26, 'revenue' => 2080],
        ];
    }
}
