<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request): Response
    {
        $query = Product::query()->with(['category', 'productComponents.inventoryItem']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Paginate and get products
        $products = $query->latest()->paginate(20)->withQueryString();

        // Get categories for filter dropdown
        $categories = Category::select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        // Get inventory items for component selection
        $inventoryItems = InventoryItem::select('id', 'sku', 'name', 'owner_id', 'unit')
            ->with('owner:id,code,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('products/Index', [
            'products' => $products,
            'categories' => $categories,
            'inventoryItems' => $inventoryItems,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
            ],
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(ProductStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $components = $validated['components'] ?? [];
        unset($validated['components']);

        $product = Product::create($validated);

        // Sync product components
        if (! empty($components)) {
            foreach ($components as $component) {
                $product->productComponents()->create([
                    'inventory_item_id' => $component['inventory_item_id'],
                    'slot' => $component['slot'],
                    'qty_per_product' => $component['qty_per_product'],
                ]);
            }
        }

        return to_route('products.index')->with('success', 'Product created successfully.');
    }

    /**
     * Update the specified product in storage.
     */
    public function update(ProductUpdateRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();
        $components = $validated['components'] ?? [];
        unset($validated['components']);

        $product->update($validated);

        // Sync product components (delete existing, create new)
        $product->productComponents()->delete();

        if (! empty($components)) {
            foreach ($components as $component) {
                $product->productComponents()->create([
                    'inventory_item_id' => $component['inventory_item_id'],
                    'slot' => $component['slot'],
                    'qty_per_product' => $component['qty_per_product'],
                ]);
            }
        }

        return to_route('products.index')->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return to_route('products.index')->with('success', 'Product deleted successfully.');
    }

    /**
     * Download CSV template for importing products.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="products_import_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'Code',
                'Name',
                'Description',
                'Category',
                'Sales Price',
                'UOM',
                'Rental Price',
                'Rental Duration',
            ], ';');

            // Sample data rows
            fputcsv($file, [
                'PRD-0001',
                'PlayStation 5 - 1 Day',
                'PS5 console rental package',
                'Console',
                '0',
                'pcs',
                '200000',
                'day',
            ], ';');

            fputcsv($file, [
                '',
                'Extra Controller',
                'Additional gaming controller',
                'Peripheral',
                '0',
                'pcs',
                '35000',
                'day',
            ], ';');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import products from CSV file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // 10MB max
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            fseek($handle, 0);
        }

        // Read headers
        $headers = fgetcsv($handle, 0, ';');

        if (! $headers) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid CSV file format.',
            ], 422);
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1; // Start from 1 (header is row 0)

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Combine headers with row data
            $data = array_combine($headers, $row);

            // Transform rental duration to lowercase for case-insensitive handling
            if (! empty($data['Rental Duration'])) {
                $data['Rental Duration'] = strtolower(trim($data['Rental Duration']));
            }

            // Find or create category
            $category = null;
            if (! empty($data['Category'])) {
                $category = Category::firstOrCreate(['name' => trim($data['Category'])]);
            }

            // Validate row
            $validator = Validator::make([
                'code' => $data['Code'] ?? null,
                'name' => $data['Name'] ?? null,
                'description' => $data['Description'] ?? null,
                'category_id' => $category?->id,
                'sales_price' => $data['Sales Price'] ?? null,
                'rental_price' => $data['Rental Price'] ?? null,
                'uom' => $data['UOM'] ?? null,
                'rental_duration' => $data['Rental Duration'] ?? null,
            ], [
                'code' => ['nullable', 'string', 'max:255', 'unique:products,code'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'category_id' => ['required', 'exists:categories,id'],
                'sales_price' => ['required', 'numeric', 'min:0'],
                'rental_price' => ['required', 'numeric', 'min:0'],
                'uom' => ['required', 'string', 'max:255'],
                'rental_duration' => ['required', 'string', 'in:hour,day,week,month'],
            ], [
                'code.unique' => 'Product code already exists: '.($data['Code'] ?? ''),
                'name.required' => 'Product name is required.',
                'category_id.required' => 'Category is required.',
                'category_id.exists' => 'Category not found: '.($data['Category'] ?? ''),
                'sales_price.required' => 'Sales price is required.',
                'sales_price.min' => 'Sales price must be at least 0.',
                'rental_price.required' => 'Rental price is required.',
                'rental_price.min' => 'Rental price must be at least 0.',
                'uom.required' => 'Unit of measure is required.',
                'rental_duration.required' => 'Rental duration is required.',
                'rental_duration.in' => 'Invalid rental duration. Must be: hour, day, week, or month.',
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'name' => $data['Name'] ?? 'Unknown',
                    'errors' => $validator->errors()->all(),
                ];

                continue;
            }

            try {
                $validated = $validator->validated();
                Product::create([
                    'code' => empty($validated['code']) ? null : $validated['code'],
                    'name' => $validated['name'],
                    'description' => empty($validated['description']) ? null : $validated['description'],
                    'category_id' => $validated['category_id'],
                    'sales_price' => $validated['sales_price'],
                    'rental_price' => $validated['rental_price'],
                    'uom' => $validated['uom'],
                    'rental_duration' => $validated['rental_duration'],
                ]);
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'name' => $data['Name'] ?? 'Unknown',
                    'errors' => ['Failed to create product: '.$e->getMessage()],
                ];
            }
        }

        fclose($handle);

        return response()->json([
            'success' => true,
            'summary' => [
                'total' => $imported + $skipped,
                'imported' => $imported,
                'skipped' => $skipped,
            ],
            'errors' => $errors,
        ]);
    }
}
