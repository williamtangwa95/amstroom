<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $systemName   = '';
        $slogan       = '';
        $logo         = '';
        $printerEnabled = Setting::get('printer_enabled_user_' . $user->id, '1');
        $notificationRingtone = Setting::get('notification_sound_user_' . $user->id);

        if ($user->isOwner()) {
            $systemName = Setting::get('system_name', 'AMSTROOM');
            $slogan     = Setting::get('slogan', 'Technology Innovations');
            $logo       = Setting::get('logo');
        } elseif ($user->isShopAdmin()) {
            $shop = $user->shop;
            $systemName = $shop->shop_name;
            $slogan     = $shop->slogan;
            $logo       = $shop->logo;
        }

        return view('settings.index', compact('systemName', 'slogan', 'logo', 'printerEnabled', 'notificationRingtone'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // Process ringtone upload for any user role
        if ($request->hasFile('notification_ringtone')) {
            $request->validate([
                'notification_ringtone' => 'required|file|mimes:mp3,wav,ogg,oga|max:5120',
            ]);

            $settingKey = 'notification_sound_user_' . $user->id;
            $oldSound = Setting::get($settingKey);
            if ($oldSound && Storage::disk('public')->exists($oldSound)) {
                Storage::disk('public')->delete($oldSound);
            }

            $path = $request->file('notification_ringtone')->store('ringtones', 'public');
            Setting::set($settingKey, $path);
        }

        if ($user->isOwner()) {
            $request->validate([
                'system_name' => 'required|string|max:150',
                'slogan'      => 'nullable|string|max:255',
                'logo'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'printer_enabled' => 'required|in:0,1',
            ]);

            Setting::set('system_name', $request->system_name);
            Setting::set('slogan', $request->slogan);
            Setting::set('printer_enabled_user_' . $user->id, $request->printer_enabled);

            if ($request->hasFile('logo')) {
                $oldLogo = Setting::get('logo');
                if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                    Storage::disk('public')->delete($oldLogo);
                }

                $path = $request->file('logo')->store('logos', 'public');
                Setting::set('logo', $path);
            }
            
            $msg = 'System branding settings updated successfully.';

        } elseif ($user->isShopAdmin()) {
            $request->validate([
                'system_name' => 'required|string|max:150',
                'slogan'      => 'nullable|string|max:255',
                'logo'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'printer_enabled' => 'required|in:0,1',
            ]);

            $shop = $user->shop;
            $shop->shop_name = $request->system_name;
            $shop->slogan = $request->slogan;

            if ($request->hasFile('logo')) {
                if ($shop->logo && Storage::disk('public')->exists($shop->logo)) {
                    Storage::disk('public')->delete($shop->logo);
                }

                $path = $request->file('logo')->store('logos', 'public');
                $shop->logo = $path;
            }
            $shop->save();

            Setting::set('printer_enabled_user_' . $user->id, $request->printer_enabled);
            $msg = 'Shop branding settings updated successfully.';

        } else { // Seller
            $request->validate([
                'printer_enabled' => 'required|in:0,1',
            ]);

            Setting::set('printer_enabled_user_' . $user->id, $request->printer_enabled);
            $msg = 'Printer settings updated successfully.';
        }

        return back()->with('success', $msg);
    }

    public function removeLogo()
    {
        $user = Auth::user();

        if ($user->isOwner()) {
            $oldLogo = Setting::get('logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            Setting::set('logo', null);
            $msg = 'Global logo removed. Default icon will be displayed.';
        } elseif ($user->isShopAdmin()) {
            $shop = $user->shop;
            if ($shop->logo && Storage::disk('public')->exists($shop->logo)) {
                Storage::disk('public')->delete($shop->logo);
            }
            $shop->logo = null;
            $shop->save();
            $msg = 'Shop logo removed. Global or default branding will be displayed.';
        } else {
            abort(403);
        }

        return back()->with('success', $msg);
    }

    public function removeRingtone()
    {
        $user = Auth::user();
        $settingKey = 'notification_sound_user_' . $user->id;
        $oldSound = Setting::get($settingKey);
        if ($oldSound && Storage::disk('public')->exists($oldSound)) {
            Storage::disk('public')->delete($oldSound);
        }
        Setting::set($settingKey, null);

        return back()->with('success', 'Custom ringtone removed. Default sound will be used.');
    }
}
