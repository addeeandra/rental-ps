<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanySettingsRequest;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CompanySettingsController extends Controller
{
    /**
     * Show the company settings form.
     */
    public function edit(): InertiaResponse
    {
        return Inertia::render('settings/CompanySettings', [
            'settings' => CompanySetting::current(),
        ]);
    }

    /**
     * Update the company settings.
     */
    public function update(CompanySettingsRequest $request): RedirectResponse
    {
        $settings = CompanySetting::current();
        $data = $request->validated();

        // Handle logo removal
        if ($request->boolean('remove_logo') && $settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
            $settings->logo_path = null;
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            
            // Store new logo
            $path = $request->file('logo')->store('logos', 'public');
            $settings->logo_path = $path;
        }

        // Update other fields
        $settings->fill($request->except(['logo', 'remove_logo']));
        $settings->save();

        return back()->with('success', 'Company settings updated successfully.');
    }
}
