<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(Request $request): Response
    {
        $query = Category::query()->withCount('products');

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Paginate and get categories
        $categories = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('categories/Index', [
            'categories' => $categories,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return to_route('categories.index')->with('success', 'Category created successfully.');
    }

    /**
     * Update the specified category in storage.
     */
    public function update(CategoryUpdateRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return to_route('categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category): RedirectResponse
    {
        // Check if category has products
        $productsCount = $category->products()->count();
        
        if ($productsCount > 0) {
            return to_route('categories.index')->with('error', "Cannot delete category '{$category->name}' because it has {$productsCount} associated product(s). Please reassign or delete the products first.");
        }

        $category->delete();

        return to_route('categories.index')->with('success', 'Category deleted successfully.');
    }

    /**
     * Download CSV template for importing categories.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="categories_import_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'Name',
                'Description',
            ], ';');

            // Sample data rows
            fputcsv($file, [
                'Playbox',
                'Gaming console packages with monitor',
            ], ';');

            fputcsv($file, [
                'Console',
                'Gaming consoles only',
            ], ';');

            fputcsv($file, [
                'Peripheral',
                'Gaming peripherals and accessories',
            ], ';');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import categories from CSV file.
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
        
        if (!$headers) {
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

            // Validate row
            $validator = Validator::make($data, [
                'Name' => ['required', 'string', 'max:255'],
                'Description' => ['nullable', 'string'],
            ], [
                'Name.required' => 'Category name is required.',
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
                Category::create([
                    'name' => $validated['Name'],
                    'description' => empty($validated['Description']) ? null : $validated['Description'],
                ]);
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'name' => $data['Name'] ?? 'Unknown',
                    'errors' => ['Failed to create category: ' . $e->getMessage()],
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
