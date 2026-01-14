<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard overview.
     */
    public function overview(): Response
    {
        $sharedData = $this->getSharedMetrics();
        
        // Revenue chart data (last 6 months)
        $revenueChart = $this->getRevenueChartData();
        
        // Recent invoices (last 10)
        $recentInvoices = Invoice::with('partner:id,name,code')
            ->latest('invoice_date')
            ->take(10)
            ->get()
            ->map(fn($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'partner_name' => $invoice->partner->name,
                'invoice_date' => $invoice->invoice_date,
                'total_amount' => $invoice->total_amount,
                'status' => $invoice->status,
            ]);
        
        // Status distribution
        $statusDistribution = Invoice::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status->value => $item->count]);
        
        return Inertia::render('dashboard/Overview', [
            'activeTab' => 'overview',
            'sharedData' => $sharedData,
            'revenueChart' => $revenueChart,
            'recentInvoices' => $recentInvoices,
            'statusDistribution' => $statusDistribution,
            'hasData' => Invoice::count() > 0,
        ]);
    }
    
    /**
     * Display the financial tab.
     */
    public function financial(): Response
    {
        $sharedData = $this->getSharedMetrics();
        
        // Monthly comparison (current vs previous month by week)
        $monthlyComparison = $this->getMonthlyComparisonData();
        
        // Top 5 customers by revenue (current month)
        $topCustomers = Invoice::select('partner_id', DB::raw('SUM(total_amount) as total_revenue'))
            ->with('partner:id,name')
            ->whereMonth('invoice_date', Carbon::now()->month)
            ->whereYear('invoice_date', Carbon::now()->year)
            ->groupBy('partner_id')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'partner_id' => $item->partner_id,
                'partner_name' => $item->partner->name,
                'total_revenue' => $item->total_revenue,
            ]);
        
        // Top 5 products by revenue (current month)
        $topProducts = InvoiceItem::select('product_id', DB::raw('SUM(total) as total_revenue'))
            ->with('product:id,name')
            ->whereHas('invoice', function($query) {
                $query->whereMonth('invoice_date', Carbon::now()->month)
                      ->whereYear('invoice_date', Carbon::now()->year);
            })
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'Unknown',
                'total_revenue' => $item->total_revenue,
            ]);
        
        // Financial metrics
        $currentMonth = Invoice::whereMonth('invoice_date', Carbon::now()->month)
            ->whereYear('invoice_date', Carbon::now()->year);
        
        $previousMonth = Invoice::whereMonth('invoice_date', Carbon::now()->subMonth()->month)
            ->whereYear('invoice_date', Carbon::now()->subMonth()->year);
        
        $metrics = [
            'average_invoice_value' => [
                'current' => $currentMonth->clone()->avg('total_amount') ?? 0,
                'previous' => $previousMonth->clone()->avg('total_amount') ?? 0,
            ],
            'payment_collection_rate' => [
                'current' => $this->calculateCollectionRate($currentMonth->clone()),
                'previous' => $this->calculateCollectionRate($previousMonth->clone()),
            ],
            'total_discounts' => [
                'current' => $currentMonth->clone()->sum('discount_amount') ?? 0,
                'previous' => $previousMonth->clone()->sum('discount_amount') ?? 0,
            ],
        ];
        
        return Inertia::render('dashboard/Financial', [
            'activeTab' => 'financial',
            'sharedData' => $sharedData,
            'monthlyComparison' => $monthlyComparison,
            'topCustomers' => $topCustomers,
            'topProducts' => $topProducts,
            'metrics' => $metrics,
            'hasData' => Invoice::count() > 0,
        ]);
    }
    
    /**
     * Display the operations tab.
     */
    public function operations(): Response
    {
        $sharedData = $this->getSharedMetrics();
        
        // Overdue invoices
        $overdueInvoices = Invoice::with('partner:id,name')
            ->where('due_date', '<', Carbon::now())
            ->whereIn('status', ['Unpaid', 'Partial'])
            ->get()
            ->map(fn($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'partner_name' => $invoice->partner->name,
                'total_amount' => $invoice->total_amount,
                'balance' => $invoice->balance,
                'due_date' => $invoice->due_date,
                'days_overdue' => Carbon::now()->diffInDays($invoice->due_date),
                'status' => $invoice->status,
            ]);
        
        // Active rentals
        $activeRentals = Invoice::with('partner:id,name', 'items.product:id,name')
            ->where('order_type', 'Rental')
            ->where('rental_end_date', '>=', Carbon::now())
            ->get()
            ->map(fn($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'partner_name' => $invoice->partner->name,
                'products' => $invoice->items->pluck('product.name')->join(', '),
                'rental_start_date' => $invoice->rental_start_date,
                'rental_end_date' => $invoice->rental_end_date,
                'rental_days' => $invoice->rental_days,
                'days_remaining' => Carbon::parse($invoice->rental_end_date)->diffInDays(Carbon::now()),
            ]);
        
        // Upcoming returns (within 7 days)
        $upcomingReturns = Invoice::with('partner:id,name', 'items.product:id,name')
            ->where('order_type', 'Rental')
            ->whereBetween('rental_end_date', [Carbon::now(), Carbon::now()->addDays(7)])
            ->get()
            ->map(fn($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'partner_name' => $invoice->partner->name,
                'products' => $invoice->items->pluck('product.name')->join(', '),
                'rental_end_date' => $invoice->rental_end_date,
                'days_remaining' => Carbon::parse($invoice->rental_end_date)->diffInDays(Carbon::now()),
            ]);
        
        return Inertia::render('dashboard/Operations', [
            'activeTab' => 'operations',
            'sharedData' => $sharedData,
            'overdueInvoices' => $overdueInvoices,
            'overdueCount' => $overdueInvoices->count(),
            'overdueTotal' => $overdueInvoices->sum('balance'),
            'activeRentals' => $activeRentals,
            'upcomingReturns' => $upcomingReturns,
            'hasData' => Invoice::count() > 0,
        ]);
    }
    
    /**
     * Get shared metrics that appear on all tabs.
     */
    private function getSharedMetrics(): array
    {
        return Cache::remember('dashboard_shared_metrics', 300, function () {
            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();
            $previousMonthStart = Carbon::now()->subMonth()->startOfMonth();
            $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();
            
            // Current month revenue
            $currentRevenue = Invoice::whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
                ->selectRaw('
                    SUM(total_amount) as total,
                    SUM(paid_amount) as paid,
                    SUM(total_amount - paid_amount) as outstanding
                ')
                ->first();
            
            // Previous month revenue
            $previousRevenue = Invoice::whereBetween('invoice_date', [$previousMonthStart, $previousMonthEnd])
                ->sum('total_amount') ?? 0;
            
            // Calculate revenue change
            $revenueChange = $previousRevenue > 0 
                ? (($currentRevenue->total - $previousRevenue) / $previousRevenue) * 100 
                : 0;
            
            // Outstanding balance
            $outstanding = Invoice::whereIn('status', ['Unpaid', 'Partial'])
                ->selectRaw('
                    SUM(total_amount - paid_amount) as total_balance,
                    SUM(CASE WHEN due_date < NOW() THEN total_amount - paid_amount ELSE 0 END) as overdue_balance
                ')
                ->first();
            
            // Active operations
            $activeRentalsCount = Invoice::where('order_type', 'Rental')
                ->where('rental_end_date', '>=', Carbon::now())
                ->count();
            
            $pendingInvoicesCount = Invoice::where('status', 'Unpaid')->count();
            
            // New customers this month
            $newCustomersCount = Partner::whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
                ->count();
            
            $previousNewCustomersCount = Partner::whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
                ->count();
            
            $customersChange = $previousNewCustomersCount > 0
                ? (($newCustomersCount - $previousNewCustomersCount) / $previousNewCustomersCount) * 100
                : 0;
            
            // Daily revenue sparkline (last 30 days)
            $dailyRevenue = Invoice::where('invoice_date', '>=', Carbon::now()->subDays(30))
                ->selectRaw('DATE(invoice_date) as date, SUM(total_amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('total')
                ->toArray();
            
            return [
                'currentMonthRevenue' => [
                    'total' => $currentRevenue->total ?? 0,
                    'paid' => $currentRevenue->paid ?? 0,
                    'outstanding' => $currentRevenue->outstanding ?? 0,
                ],
                'previousMonthRevenue' => $previousRevenue,
                'revenueChange' => round($revenueChange, 1),
                'outstandingBalance' => $outstanding->total_balance ?? 0,
                'overdueBalance' => $outstanding->overdue_balance ?? 0,
                'outstandingCount' => Invoice::whereIn('status', ['Unpaid', 'Partial'])->count(),
                'activeRentalsCount' => $activeRentalsCount,
                'pendingInvoicesCount' => $pendingInvoicesCount,
                'newCustomersCount' => $newCustomersCount,
                'customersChange' => round($customersChange, 1),
                'dailyRevenue' => $dailyRevenue,
            ];
        });
    }
    
    /**
     * Get revenue chart data for last 6 months.
     */
    private function getRevenueChartData(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = [
                'month' => $date->format('M Y'),
                'start' => $date->startOfMonth()->toDateString(),
                'end' => $date->endOfMonth()->toDateString(),
            ];
        }
        
        $data = [];
        foreach ($months as $month) {
            $sales = Invoice::where('order_type', 'Sales')
                ->whereBetween('invoice_date', [$month['start'], $month['end']])
                ->sum('total_amount') ?? 0;
            
            $rentals = Invoice::where('order_type', 'Rental')
                ->whereBetween('invoice_date', [$month['start'], $month['end']])
                ->sum('total_amount') ?? 0;
            
            $data[] = [
                'month' => $month['month'],
                'sales' => $sales,
                'rentals' => $rentals,
                'total' => $sales + $rentals,
            ];
        }
        
        return $data;
    }
    
    /**
     * Get monthly comparison data.
     */
    private function getMonthlyComparisonData(): array
    {
        $currentMonth = Carbon::now();
        $previousMonth = Carbon::now()->subMonth();
        
        $data = [];
        for ($week = 1; $week <= 4; $week++) {
            $currentWeekStart = $currentMonth->copy()->startOfMonth()->addWeeks($week - 1);
            $currentWeekEnd = $currentWeekStart->copy()->endOfWeek();
            
            $previousWeekStart = $previousMonth->copy()->startOfMonth()->addWeeks($week - 1);
            $previousWeekEnd = $previousWeekStart->copy()->endOfWeek();
            
            $currentRevenue = Invoice::whereBetween('invoice_date', [$currentWeekStart, $currentWeekEnd])
                ->sum('total_amount') ?? 0;
            
            $previousRevenue = Invoice::whereBetween('invoice_date', [$previousWeekStart, $previousWeekEnd])
                ->sum('total_amount') ?? 0;
            
            $data[] = [
                'week' => "Week $week",
                'current' => $currentRevenue,
                'previous' => $previousRevenue,
            ];
        }
        
        return $data;
    }
    
    /**
     * Calculate payment collection rate.
     */
    private function calculateCollectionRate($query): float
    {
        $result = $query->selectRaw('
            SUM(total_amount) as total,
            SUM(paid_amount) as paid
        ')->first();
        
        if (!$result || $result->total == 0) {
            return 0;
        }
        
        return round(($result->paid / $result->total) * 100, 1);
    }
}
