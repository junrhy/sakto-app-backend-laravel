<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientDetails;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
class FnbSettingsController extends Controller
{
    public function index(Request $request)
    {
        $settings = ClientDetails::where('client_identifier', $request->client_identifier)->get();
        
        $formattedSettings = [
            'restaurant_info' => [],
            'social_links' => [],
            'opening_hours' => [],
            'auth' => []
        ];

        foreach ($settings as $setting) {
            list($section, $key) = explode('.', $setting->name);
            $formattedSettings[$section][$key] = $setting->value;
        }

        return response()->json($formattedSettings);
    }
    
    public function store(Request $request)
    {
        try {
            // Check if client exists
            $client = Client::where('client_identifier', $request->client_identifier)->first();
            
            if (!$client) {
                return response()->json([
                    'error' => 'Client not found',
                    'message' => 'No client found with the provided identifier'
                ], 404);
            }

            // Process restaurant info
            foreach ($request->restaurant_info as $key => $value) {
                ClientDetails::updateOrCreate(
                    [
                        'app_name' => 'fnb',
                        'client_id' => $client->id,
                        'client_identifier' => $client->client_identifier,
                        'name' => "restaurant_info.{$key}"
                    ],
                    [
                        'value' => $value
                    ]
                );
            }

            // Delete old social links and save new ones
            ClientDetails::where('client_id', $client->id)
                ->where('app_name', 'fnb')
                ->where('name', 'like', 'social_links.%')
                ->delete();

            foreach ($request->social_links as $platform => $url) {
                ClientDetails::create([
                    'app_name' => 'fnb',
                    'client_id' => $client->id,
                    'client_identifier' => $client->client_identifier,
                    'name' => "social_links.{$platform}",
                    'value' => $url
                ]);
            }

            // Delete old opening hours and save new ones
            ClientDetails::where('client_id', $client->id)
                ->where('app_name', 'fnb')
                ->where('name', 'like', 'opening_hours.%')
                ->delete();

            foreach ($request->opening_hours as $day => $hours) {
                ClientDetails::create([
                    'app_name' => 'fnb',
                    'client_id' => $client->id,
                    'client_identifier' => $client->client_identifier,
                    'name' => "opening_hours.{$day}",
                    'value' => $hours
                ]);
            }

            // Process auth settings
            foreach ($request->auth as $key => $value) {
                if ($key === 'password' && !empty($value)) {
                    $value = Hash::make($value);
                }
                ClientDetails::updateOrCreate(
                    [
                        'app_name' => 'fnb',
                        'client_id' => $client->id,
                        'client_identifier' => $client->client_identifier,
                        'name' => "auth.{$key}"
                    ],
                    [
                        'value' => $value
                    ]
                );
            }

            $settings = ClientDetails::where('client_identifier', $client->client_identifier)->get();
            return response()->json($settings);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update settings',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
