<?php

namespace App\Http\Controllers;

use App\Http\Requests\PartnerStoreRequest;
use App\Http\Requests\PartnerUpdateRequest;
use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartnerController extends Controller
{
    /**
     * Display a listing of the partners.
     */
    public function index(Request $request): Response
    {
        $query = Partner::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile_phone', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Paginate and get partners
        $partners = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('partners/Index', [
            'partners' => $partners,
            'filters' => [
                'search' => $search,
                'type' => $type,
            ],
        ]);
    }

    /**
     * Store a newly created partner in storage.
     */
    public function store(PartnerStoreRequest $request): RedirectResponse
    {
        Partner::create($request->validated());

        return to_route('partners.index')->with('success', 'Partner created successfully.');
    }

    /**
     * Update the specified partner in storage.
     */
    public function update(PartnerUpdateRequest $request, Partner $partner): RedirectResponse
    {
        $partner->update($request->validated());

        return to_route('partners.index')->with('success', 'Partner updated successfully.');
    }

    /**
     * Remove the specified partner from storage.
     */
    public function destroy(Partner $partner): RedirectResponse
    {
        $partner->delete();

        return to_route('partners.index')->with('success', 'Partner deleted successfully.');
    }

    /**
     * Download CSV template for importing partners.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="partners_import_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'type',
                'name',
                'email',
                'phone',
                'mobile_phone',
                'address_line_1',
                'address_line_2',
                'city',
                'province',
                'postal_code',
                'country',
                'gmap_url',
                'website',
                'notes',
            ], ';');

            // Sample data rows
            fputcsv($file, [
                'Client',
                'PT Example Indonesia',
                'contact@example.com',
                '031-1234567',
                '628123456789',
                'Jl. Contoh No. 123',
                'Blok A',
                'Surabaya',
                'Jawa Timur',
                '60123',
                'Indonesia',
                'https://maps.google.com/?q=PT+Example+Indonesia',
                'https://example.com',
                'Sample client notes',
            ], ';');

            fputcsv($file, [
                'Supplier',
                'CV Supplier Jaya',
                'supplier@example.com',
                '031-7654321',
                '628987654321',
                'Jl. Supplier Street 456',
                '',
                'Sidoarjo',
                'Jawa Timur',
                '61200',
                'Indonesia',
                '',
                '',
                'Regular supplier for office supplies',
            ], ';');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import partners from CSV file.
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
            
            // Transform phone numbers from scientific notation
            foreach (['phone', 'mobile_phone'] as $phoneField) {
                if (!empty($data[$phoneField])) {
                    $data[$phoneField] = $this->transformPhoneNumber($data[$phoneField]);
                }
            }

            // Validate row
            $validator = Validator::make($data, [
                'type' => ['required', 'string', 'in:Client,Supplier,Supplier & Client'],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255', 'unique:partners,email'],
                'phone' => ['nullable', 'string', 'max:255'],
                'mobile_phone' => ['nullable', 'string', 'max:255'],
                'address_line_1' => ['nullable', 'string', 'max:255'],
                'address_line_2' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:255'],
                'province' => ['nullable', 'string', 'max:255'],
                'postal_code' => ['nullable', 'string', 'max:255'],
                'country' => ['nullable', 'string', 'max:255'],
                'gmap_url' => ['nullable', 'url', 'max:255'],
                'website' => ['nullable', 'url', 'max:255'],
                'notes' => ['nullable', 'string'],
            ], [
                'type.required' => 'Partner type is required.',
                'type.in' => 'Invalid partner type. Must be: Client, Supplier, or Supplier & Client.',
                'name.required' => 'Partner name is required.',
                'email.email' => 'Invalid email format.',
                'email.unique' => 'Email already exists: ' . ($data['email'] ?? ''),
                'gmap_url.url' => 'Invalid Google Maps URL format.',
                'website.url' => 'Invalid website URL format.',
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'name' => $data['name'] ?? 'Unknown',
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            try {
                Partner::create(array_merge($data = $validator->validated(), [
                    'email' => empty($data['email']) ? null : $data['email'],
                    'phone' => empty($data['phone']) ? null : $data['phone'],
                    'mobile_phone' => empty($data['mobile_phone']) ? null : $data['mobile_phone'],
                ]));
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = [
                    'row' => $rowNumber,
                    'name' => $data['name'] ?? 'Unknown',
                    'errors' => ['Failed to create partner: ' . $e->getMessage()],
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

    /**
     * Transform phone number from scientific notation to regular format.
     */
    private function transformPhoneNumber(string $phone): string
    {
        // Handle scientific notation (e.g., 6,28215E+12)
        $phone = str_replace(',', '.', $phone);
        
        if (stripos($phone, 'e') !== false) {
            $phone = sprintf('%.0f', floatval($phone));
        }

        return $phone;
    }
}
