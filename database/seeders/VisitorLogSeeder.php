<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class VisitorLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Clemence Simon if they do not exist
        $clemence = User::firstOrCreate(
            ['email' => 'clemence@amstroom.com'],
            [
                'name'     => 'Clemence Simon',
                'phone'    => '+255700000002',
                'password' => Hash::make('password'),
                'role'     => 'owner',
            ]
        );

        // Fetch other users to randomly assign to request logs
        $users = User::all();

        // 2. Define the distribution lists
        // Locations distribution: (Total 157)
        // Morogoro: 87, Singapore: 14, Ashburn: 13, Santa Clara: 8, Mountain View: 4, Dar es Salaam: 10, London: 8, Tokyo: 4, Berlin: 5, Dodoma: 4
        $locations = [];
        $addLocations = function($city, $country, $count) use (&$locations) {
            for ($i = 0; $i < $count; $i++) {
                $locations[] = ['city' => $city, 'country' => $country];
            }
        };
        $addLocations('Morogoro', 'Tanzania', 87);
        $addLocations('Singapore', 'Singapore', 14);
        $addLocations('Ashburn', 'United States', 13);
        $addLocations('Santa Clara', 'United States', 8);
        $addLocations('Mountain View', 'United States', 4);
        $addLocations('Dar es Salaam', 'Tanzania', 10);
        $addLocations('London', 'United Kingdom', 8);
        $addLocations('Tokyo', 'Japan', 4);
        $addLocations('Berlin', 'Germany', 5);
        $addLocations('Dodoma', 'Tanzania', 4);

        // Browsers distribution: (Total 157)
        // Chrome: 104, Edge: 21, Unknown: 15, Safari: 11, Firefox: 6
        $browsers = [];
        $addBrowsers = function($browser, $count) use (&$browsers) {
            for ($i = 0; $i < $count; $i++) {
                $browsers[] = $browser;
            }
        };
        $addBrowsers('Chrome', 104);
        $addBrowsers('Edge', 21);
        $addBrowsers('Unknown', 15);
        $addBrowsers('Safari', 11);
        $addBrowsers('Firefox', 6);

        // Devices distribution: (Total 157)
        // Desktop: 124, Mobile: 33
        // Desktop maps to: Windows (90), macOS (24), Linux (10)
        // Mobile maps to: Android (20), iOS (13)
        $platforms = [];
        $devices = [];
        $addPlatforms = function($device, $platform, $count) use (&$platforms, &$devices) {
            for ($i = 0; $i < $count; $i++) {
                $devices[] = $device;
                $platforms[] = $platform;
            }
        };
        $addPlatforms('Desktop', 'Windows', 90);
        $addPlatforms('Desktop', 'macOS', 24);
        $addPlatforms('Desktop', 'Linux', 10);
        $addPlatforms('Mobile', 'Android', 20);
        $addPlatforms('Mobile', 'iOS', 13);

        // Shuffle arrays for realistic distribution pairing
        shuffle($locations);
        shuffle($browsers);

        $zippedPlatformDevice = [];
        for ($i = 0; $i < 157; $i++) {
            $zippedPlatformDevice[] = [
                'device'   => $devices[$i],
                'platform' => $platforms[$i]
            ];
        }
        shuffle($zippedPlatformDevice);

        // Generate 48 distinct IP addresses and map hits per IP
        $ipMap = [];
        $ipMap['Morogoro_Tanzania'] = array_merge(['45.221.196.65'], array_map(fn($n) => "196.43.12." . $n, range(1, 20)));
        $ipMap['Singapore_Singapore'] = array_merge(['111.223.45.1'], array_map(fn($n) => "111.223.45." . $n, range(2, 4)));
        $ipMap['Ashburn_United States'] = array_merge(['8.8.8.8'], array_map(fn($n) => "8.8.4." . $n, range(1, 2)));
        $ipMap['Santa Clara_United States'] = ['172.217.16.1', '172.217.16.2'];
        $ipMap['Mountain View_United States'] = ['172.217.0.1'];
        $ipMap['Dar es Salaam_Tanzania'] = array_map(fn($n) => "41.59.100." . $n, range(1, 5));
        $ipMap['London_United Kingdom'] = array_map(fn($n) => "188.165.12." . $n, range(1, 4));
        $ipMap['Tokyo_Japan'] = array_map(fn($n) => "210.140.23." . $n, range(1, 2));
        $ipMap['Berlin_Germany'] = array_map(fn($n) => "46.4.15." . $n, range(1, 3));
        $ipMap['Dodoma_Tanzania'] = array_map(fn($n) => "197.250.2." . $n, range(1, 3));

        $ipHitsDistribution = [];
        $addIpHits = function($locKey, $hitsCount, $ips) use (&$ipHitsDistribution) {
            $mainIp = $ips[0];
            $mainHits = $hitsCount - (count($ips) - 1);
            $ipHitsDistribution[$locKey][] = ['ip' => $mainIp, 'hits' => $mainHits];
            for ($i = 1; $i < count($ips); $i++) {
                $ipHitsDistribution[$locKey][] = ['ip' => $ips[$i], 'hits' => 1];
            }
        };

        $addIpHits('Morogoro_Tanzania', 87, $ipMap['Morogoro_Tanzania']);
        $addIpHits('Singapore_Singapore', 14, $ipMap['Singapore_Singapore']);
        $addIpHits('Ashburn_United States', 13, $ipMap['Ashburn_United States']);
        $addIpHits('Santa Clara_United States', 8, $ipMap['Santa Clara_United States']);
        $addIpHits('Mountain View_United States', 4, $ipMap['Mountain View_United States']);
        $addIpHits('Dar es Salaam_Tanzania', 10, $ipMap['Dar es Salaam_Tanzania']);
        $addIpHits('London_United Kingdom', 8, $ipMap['London_United Kingdom']);
        $addIpHits('Tokyo_Japan', 4, $ipMap['Tokyo_Japan']);
        $addIpHits('Berlin_Germany', 5, $ipMap['Berlin_Germany']);
        $addIpHits('Dodoma_Tanzania', 4, $ipMap['Dodoma_Tanzania']);

        // Align IPs to location indices
        $locationIps = [];
        foreach ($locations as $loc) {
            $key = $loc['city'] . '_' . $loc['country'];
            foreach ($ipHitsDistribution[$key] as &$dist) {
                if ($dist['hits'] > 0) {
                    $locationIps[] = $dist['ip'];
                    $dist['hits']--;
                    break;
                }
            }
        }

        $paths = [
            '/dashboard', '/reports/analytics', '/admin/logs/visitors',
            '/sales/create', '/sales', '/items', '/main-stock',
            '/settings', '/chats'
        ];

        $now = Carbon::now();
        
        for ($i = 0; $i < 157; $i++) {
            $loc = $locations[$i];
            $browser = $browsers[$i];
            $platDev = $zippedPlatformDevice[$i];
            $ip = $locationIps[$i];
            
            $ua = $this->getUserAgentString($browser, $platDev['platform']);

            // Pick path: index 0 (latest log) will match the screenshot: Clemence Simon requesting /admin/logs/visitors
            if ($i === 0) {
                $path = '/admin/logs/visitors';
                $method = 'GET';
                $userId = $clemence->id;
                $timestamp = $now->copy();
            } else {
                $path = $paths[array_rand($paths)];
                $method = (rand(1, 10) > 8 && $path !== '/admin/logs/visitors') ? 'POST' : 'GET';
                
                if ($i < 15) {
                    $userId = $clemence->id;
                } elseif (rand(1, 10) > 7) {
                    $userId = $users->random()->id;
                } else {
                    $userId = null;
                }
                
                $timestamp = $now->copy()->subMinutes(rand(1, 2880));
            }

            VisitorLog::create([
                'ip_address'  => $ip,
                'url'         => $path,
                'method'      => $method,
                'user_id'     => $userId,
                'user_agent'  => $ua,
                'device_type' => $platDev['device'],
                'browser'     => $browser,
                'platform'    => $platDev['platform'],
                'city'        => $loc['city'],
                'country'     => $loc['country'],
                'session_id'  => md5($ip . '_' . $userId),
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ]);
        }
    }

    /**
     * Map browser and platform to a standard User-Agent.
     */
    private function getUserAgentString($browser, $platform): string
    {
        if ($browser === 'Chrome') {
            if ($platform === 'Windows') {
                return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
            } elseif ($platform === 'macOS') {
                return 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
            } elseif ($platform === 'Android') {
                return 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';
            } else {
                return 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
            }
        } elseif ($browser === 'Edge') {
            return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0';
        } elseif ($browser === 'Safari') {
            if ($platform === 'iOS') {
                return 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/605.1.15';
            } else {
                return 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15';
            }
        } elseif ($browser === 'Firefox') {
            return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/120.0';
        } else {
            return 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
        }
    }
}
