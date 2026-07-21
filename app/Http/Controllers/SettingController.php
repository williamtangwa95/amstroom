<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $systemName   = Setting::get('system_name', 'AMSTROOM');
        $slogan       = Setting::get('slogan', 'Technology Innovations');
        $logo         = Setting::get('logo');

        return view('settings.index', compact('systemName', 'slogan', 'logo'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'system_name' => 'required|string|max:150',
            'slogan'      => 'nullable|string|max:255',
            'logo'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        Setting::set('system_name', $request->system_name);
        Setting::set('slogan', $request->slogan);

        if ($request->hasFile('logo')) {
            $oldLogo = Setting::get('logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $path = $request->file('logo')->store('logos', 'public');
            Setting::set('logo', $path);
        }

        return back()->with('success', 'System branding settings updated successfully.');
    }

    public function removeLogo()
    {
        $oldLogo = Setting::get('logo');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }
        Setting::set('logo', null);

        return back()->with('success', 'Global logo removed. Default icon will be displayed.');
    }
}
