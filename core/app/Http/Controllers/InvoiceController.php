<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementReason;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceItemComponentShareRequest;
use App\Http\Requests\UpdateInvoicePaymentRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\CompanySetting;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\InvoiceItemComponent;
use App\Models\Partner;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the invoices.
     */
    public function index(Request $request): InertiaResponse
    {
        $query = Invoice::with(['partner', 'user']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('partner', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($status = $request->input('status') !== 'all' && $status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by partner
        if ($partnerId = $request->input('partner_id') !== 'all' && $partnerId = $request->input('partner_id')) {
            $query->where('partner_id', $partnerId);
        }

        // Paginate and get invoices
        $invoices = $query->latest('invoice_date')->paginate(20)->withQueryString();

        // Append custom attributes
        $invoices->through(function ($invoice) {
            return [
                ...$invoice->toArray(),
                'balance' => $invoice->balance,
                'is_editable' => $invoice->is_editable,
            ];
        });

        $settings = CompanySetting::current();

        return Inertia::render('invoices/Index', [
            'invoices' => $invoices,
            'partners' => Partner::select('id', 'name', 'code', 'type')
                ->whereIn('type', ['Client', 'Supplier & Client'])
                ->orderBy('name')
                ->get(),
            'products' => Product::select('id', 'name', 'code', 'sales_price', 'rental_price')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(),
            'warehouses' => Warehouse::select('id', 'name', 'code')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'inventoryItems' => InventoryItem::with('owner:id,name')
                ->select('id', 'sku', 'name', 'owner_id', 'unit', 'cost', 'default_share_percent')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'defaultInvoiceTerms' => $settings->invoice_default_terms,
            'defaultInvoiceNotes' => $settings->invoice_default_notes,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'partner_id' => $partnerId,
            ],
        ]);
    }

    /**
     * Store a newly created invoice in storage.
     */
    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $data = $request->validated();

                // Calculate rental_end_date if rental order
                $rentalEndDate = null;
                if ($data['order_type'] === 'rental' && isset($data['rental_start_date']) && isset($data['rental_duration'])) {
                    $startDate = Carbon::parse($data['rental_start_date']);
                    $rentalEndDate = $startDate->copy()->addDays($data['rental_duration'])->format('Y-m-d');
                }

                // Create invoice
                $invoice = Invoice::create([
                    'partner_id' => $data['partner_id'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'],
                    'order_type' => $data['order_type'],
                    'rental_start_date' => $data['rental_start_date'] ?? null,
                    'rental_end_date' => $rentalEndDate,
                    'delivery_time' => $data['delivery_time'] ?? null,
                    'return_time' => $data['delivery_time'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'terms' => $data['terms'] ?? null,
                    'discount_amount' => $data['discount_amount'] ?? 0,
                    'tax_amount' => $data['tax_amount'] ?? 0,
                    'shipping_fee' => $data['shipping_fee'] ?? 0,
                    'user_id' => Auth::id(),
                    'subtotal' => 0,
                    'total_amount' => 0,
                ]);

                // Calculate rental multiplier for pricing
                $rentalMultiplier = ($data['order_type'] === 'rental' && isset($data['rental_duration']))
                    ? $data['rental_duration']
                    : 1;

                // Create invoice items
                foreach ($data['line_items'] as $index => $item) {
                    $invoiceItem = $invoice->invoiceItems()->create([
                        'product_id' => $item['product_id'] ?? null,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $item['quantity'] * $item['unit_price'] * $rentalMultiplier,
                        'sort_order' => $index,
                    ]);

                    // Create invoice item components and deduct stock
                    if (! empty($item['components'])) {
                        foreach ($item['components'] as $component) {
                            // Fetch inventory item to get owner and default share percent
                            $inventoryItem = InventoryItem::findOrFail($component['inventory_item_id']);

                            // Use provided share_percent or default from inventory item
                            $sharePercent = $component['share_percent'] ?? $inventoryItem->default_share_percent;

                            // Calculate share amount based on invoice item total
                            $shareAmount = ($invoiceItem->total * $sharePercent) / 100;

                            // Create invoice item component record
                            $invoiceItem->invoiceItemComponents()->create([
                                'inventory_item_id' => $inventoryItem->id,
                                'warehouse_id' => $component['warehouse_id'],
                                'owner_id' => $inventoryItem->owner_id,
                                'qty' => $component['qty'],
                                'share_percent' => $sharePercent,
                                'share_amount' => $shareAmount,
                            ]);

                            // Create stock movement (negative quantity for sale)
                            StockMovement::create([
                                'inventory_item_id' => $inventoryItem->id,
                                'warehouse_id' => $component['warehouse_id'],
                                'quantity' => -abs($component['qty']),
                                'reason' => StockMovementReason::Sale,
                                'ref_type' => get_class($invoiceItem),
                                'ref_id' => $invoiceItem->id,
                                'notes' => "Auto-deducted from Invoice #{$invoice->invoice_number}, Item: {$invoiceItem->description}",
                                'created_by' => Auth::id(),
                            ]);
                        }
                    }
                }

                // Calculate totals and update status
                $invoice->calculateTotals();
                $invoice->updateStatus();
                $invoice->save();
            });

            return to_route('invoices.index')->with('success', 'Invoice created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create invoice: '.$e->getMessage()]);
        }
    }

    /**
     * Display the specified invoice details.
     */
    public function show(Invoice $invoice): InertiaResponse
    {
        $invoice->load([
            'partner',
            'user',
            'invoiceItems.product.productComponents.inventoryItem',
            'invoiceItems.invoiceItemComponents.inventoryItem.owner',
            'invoiceItems.invoiceItemComponents.warehouse',
        ]);

        return Inertia::render('invoices/Show', [
            'invoice' => [
                ...$invoice->toArray(),
                'balance' => $invoice->balance,
                'is_editable' => $invoice->is_editable,
                'rental_days' => $invoice->rental_days,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(Invoice $invoice): InertiaResponse
    {
        $invoice->load(['partner', 'invoiceItems.product', 'user']);
        $settings = CompanySetting::current();

        return Inertia::render('invoices/Edit', [
            'invoice' => [
                ...$invoice->toArray(),
                'balance' => $invoice->balance,
                'is_editable' => $invoice->is_editable,
                'rental_days' => $invoice->rental_days,
            ],
            'partners' => Partner::select('id', 'name', 'code', 'type')
                ->whereIn('type', ['Client', 'Supplier & Client'])
                ->orderBy('name')
                ->get(),
            'products' => Product::select('id', 'name', 'code', 'sales_price', 'rental_price')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(),
            'warehouses' => Warehouse::select('id', 'name', 'code')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'inventoryItems' => InventoryItem::with('owner:id,name')
                ->select('id', 'sku', 'name', 'owner_id', 'unit', 'cost', 'default_share_percent')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'defaultInvoiceTerms' => $settings->invoice_default_terms,
            'defaultInvoiceNotes' => $settings->invoice_default_notes,
        ]);
    }

    /**
     * Update the specified invoice in storage.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        if (! $invoice->is_editable) {
            return back()->withErrors(['error' => 'This invoice cannot be edited.']);
        }

        try {
            DB::transaction(function () use ($request, $invoice) {
                $data = $request->validated();

                // Calculate rental_end_date if rental order
                $rentalEndDate = null;
                if ($data['order_type'] === 'rental' && isset($data['rental_start_date']) && isset($data['rental_duration'])) {
                    $startDate = Carbon::parse($data['rental_start_date']);
                    $rentalEndDate = $startDate->copy()->addDays($data['rental_duration'])->format('Y-m-d');
                }

                // Update invoice
                $invoice->update([
                    'partner_id' => $data['partner_id'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'],
                    'order_type' => $data['order_type'],
                    'rental_start_date' => $data['rental_start_date'] ?? null,
                    'rental_end_date' => $rentalEndDate,
                    'delivery_time' => $data['delivery_time'] ?? null,
                    'return_time' => $data['delivery_time'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'terms' => $data['terms'] ?? null,
                    'discount_amount' => $data['discount_amount'] ?? 0,
                    'tax_amount' => $data['tax_amount'] ?? 0,
                    'shipping_fee' => $data['shipping_fee'] ?? 0,
                ]);

                // Calculate rental multiplier for pricing
                $rentalMultiplier = ($data['order_type'] === 'rental' && isset($data['rental_duration']))
                    ? $data['rental_duration']
                    : 1;

                // Reverse stock movements for existing components before deleting items
                foreach ($invoice->invoiceItems as $existingItem) {
                    foreach ($existingItem->invoiceItemComponents as $existingComponent) {
                        // Create reverse stock movement (positive quantity) to restore stock
                        StockMovement::create([
                            'inventory_item_id' => $existingComponent->inventory_item_id,
                            'warehouse_id' => $existingComponent->warehouse_id,
                            'quantity' => abs($existingComponent->qty),
                            'reason' => StockMovementReason::Adjustment,
                            'ref_type' => get_class($existingItem),
                            'ref_id' => $existingItem->id,
                            'notes' => "Stock restored due to invoice #{$invoice->invoice_number} update/deletion",
                            'created_by' => Auth::id(),
                        ]);
                    }
                }

                // Delete existing items (components will be cascade deleted)
                $invoice->invoiceItems()->delete();

                // Create new invoice items
                foreach ($data['line_items'] as $index => $item) {
                    $invoiceItem = $invoice->invoiceItems()->create([
                        'product_id' => $item['product_id'] ?? null,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $item['quantity'] * $item['unit_price'] * $rentalMultiplier,
                        'sort_order' => $index,
                    ]);

                    // Create invoice item components and deduct stock
                    if (! empty($item['components'])) {
                        foreach ($item['components'] as $component) {
                            // Fetch inventory item to get owner and default share percent
                            $inventoryItem = InventoryItem::findOrFail($component['inventory_item_id']);

                            // Use provided share_percent or default from inventory item
                            $sharePercent = $component['share_percent'] ?? $inventoryItem->default_share_percent;

                            // Calculate share amount based on invoice item total
                            $shareAmount = ($invoiceItem->total * $sharePercent) / 100;

                            // Create invoice item component record
                            $invoiceItem->invoiceItemComponents()->create([
                                'inventory_item_id' => $inventoryItem->id,
                                'warehouse_id' => $component['warehouse_id'],
                                'owner_id' => $inventoryItem->owner_id,
                                'qty' => $component['qty'],
                                'share_percent' => $sharePercent,
                                'share_amount' => $shareAmount,
                            ]);

                            // Create stock movement (negative quantity for sale)
                            StockMovement::create([
                                'inventory_item_id' => $inventoryItem->id,
                                'warehouse_id' => $component['warehouse_id'],
                                'quantity' => -abs($component['qty']),
                                'reason' => StockMovementReason::Sale,
                                'ref_type' => get_class($invoiceItem),
                                'ref_id' => $invoiceItem->id,
                                'notes' => "Auto-deducted from Invoice #{$invoice->invoice_number}, Item: {$invoiceItem->description}",
                                'created_by' => Auth::id(),
                            ]);
                        }
                    }
                }

                // Recalculate totals and update status
                $invoice->calculateTotals();
                $invoice->updateStatus();
                $invoice->save();
            });

            // Redirect based on continue_editing parameter
            if ($request->input('continue_editing')) {
                return to_route('invoices.edit', $invoice)->with('success', 'Invoice updated successfully.');
            }

            return to_route('invoices.index')->with('success', 'Invoice updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update invoice: '.$e->getMessage()]);
        }
    }

    /**
     * Remove the specified invoice from storage.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        if (! $invoice->is_editable) {
            return back()->withErrors(['error' => 'This invoice cannot be deleted.']);
        }

        $invoice->delete();

        return to_route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Update the payment status of the invoice.
     */
    public function updatePayment(UpdateInvoicePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validated();

        $invoice->paid_amount = $data['paid_amount'];

        // Auto-calculate status unless manually overridden
        if (isset($data['status'])) {
            $invoice->status = $data['status'];
        } else {
            $invoice->updateStatus();
        }

        $invoice->save();

        return to_route('invoices.index')->with('success', 'Payment updated successfully.');
    }

    /**
     * Update the share percentage of an invoice item component.
     */
    public function updateComponentShare(
        UpdateInvoiceItemComponentShareRequest $request,
        Invoice $invoice,
        InvoiceItemComponent $component
    ): RedirectResponse {
        // Verify the component belongs to this invoice
        $invoiceItem = $invoice->invoiceItems()->find($component->invoice_item_id);

        if (! $invoiceItem) {
            return back()->withErrors(['error' => 'Component does not belong to this invoice.']);
        }

        $data = $request->validated();

        // Recalculate share amount based on new percentage
        $component->share_percent = $data['share_percent'];
        $component->share_amount = ($invoiceItem->total * $data['share_percent']) / 100;
        $component->save();

        return back()->with('success', 'Share percentage updated successfully.');
    }

    /**
     * Preview the invoice as PDF.
     */
    public function preview(Invoice $invoice): Response
    {
        $invoice->load(['partner', 'invoiceItems.product', 'user']);
        $settings = CompanySetting::current();

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'settings'));

        return $pdf->stream('Invoice-'.$invoice->invoice_number.'.pdf');
    }

    /**
     * Preview the invoice as HTML for debugging.
     */
    public function previewHtml(Invoice $invoice): \Illuminate\View\View
    {
        $invoice->load(['partner', 'invoiceItems.product', 'user']);
        $settings = CompanySetting::current();

        return view('invoices.pdf', compact('invoice', 'settings'));
    }
}
