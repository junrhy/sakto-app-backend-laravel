<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientDetails;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LogisticsSettingsController extends Controller
{
    /**
     * Get logistics settings
     */
    public function index(Request $request)
    {
        try {
            // Initialize default settings structure
            $formattedSettings = [
                'general' => [
                    'company_name' => '',
                    'description' => '',
                    'address' => '',
                    'phone' => '',
                    'email' => '',
                    'website' => '',
                    'operating_hours' => [
                        'monday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                        'tuesday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                        'wednesday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                        'thursday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                        'friday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                        'saturday' => ['open' => '09:00', 'close' => '13:00', 'closed' => false],
                        'sunday' => ['open' => '00:00', 'close' => '00:00', 'closed' => true]
                    ]
                ],
                'fleet' => [
                    'max_trucks' => 10,
                    'truck_types' => [],
                    'capacity_units' => 'kg',
                    'insurance_required' => true,
                    'insurance_providers' => []
                ],
                'pricing' => [
                    'base_rate_per_km' => 0.0,
                    'minimum_charge' => 0.0,
                    'currency' => 'USD',
                    'payment_methods' => [],
                    'tax_rate' => 0.0
                ],
                'booking' => [
                    'advance_booking_days' => 30,
                    'cancellation_hours' => 24,
                    'auto_approval' => false,
                    'require_documents' => true,
                    'tracking_enabled' => true
                ],
                'notifications' => [
                    'email_notifications' => true,
                    'sms_notifications' => false,
                    'booking_confirmations' => true,
                    'status_updates' => true
                ]
            ];

            // Check if client exists
            $client = Client::where('client_identifier', $request->client_identifier)->first();
            
            if (!$client) {
                return response()->json([
                    'data' => $formattedSettings
                ]);
            }

            // Get settings from database
            $settings = ClientDetails::where('client_identifier', $client->client_identifier)
                ->where('app_name', 'logistics')
                ->get();

            // Format settings data
            foreach ($settings as $setting) {
                $parts = explode('.', $setting->name);
                $section = $parts[0];
                
                if (count($parts) > 1) {
                    $key = $parts[1];
                    
                    if ($section === 'general' && $key === 'operating_hours') {
                        $formattedSettings[$section][$key] = json_decode($setting->value, true) ?? [];
                    } elseif ($section === 'fleet' && $key === 'truck_types') {
                        $formattedSettings[$section][$key] = json_decode($setting->value, true) ?? [];
                    } elseif ($section === 'fleet' && $key === 'insurance_providers') {
                        $formattedSettings[$section][$key] = json_decode($setting->value, true) ?? [];
                    } elseif ($section === 'pricing' && $key === 'payment_methods') {
                        $formattedSettings[$section][$key] = json_decode($setting->value, true) ?? [];
                    } else {
                        $formattedSettings[$section][$key] = $setting->value;
                    }
                }
            }

            return response()->json([
                'data' => $formattedSettings
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching logistics settings: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch settings',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save logistics settings
     */
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

            // Process general settings
            if (isset($request->general)) {
                foreach ($request->general as $key => $value) {
                    if ($key === 'operating_hours') {
                        $value = json_encode($value);
                    }
                    
                    // Handle empty strings - convert to null or provide default
                    if ($value === '' || $value === null) {
                        $value = $key === 'operating_hours' ? '{}' : '';
                    }
                    
                    ClientDetails::updateOrCreate(
                        [
                            'app_name' => 'logistics',
                            'client_id' => $client->id,
                            'client_identifier' => $client->client_identifier,
                            'name' => "general.{$key}"
                        ],
                        [
                            'value' => $value
                        ]
                    );
                }
            }

            // Process fleet settings
            if (isset($request->fleet)) {
                foreach ($request->fleet as $key => $value) {
                    if ($key === 'truck_types' || $key === 'insurance_providers') {
                        $value = json_encode($value);
                    }
                    
                    // Handle empty values
                    if ($value === '' || $value === null) {
                        $value = in_array($key, ['truck_types', 'insurance_providers']) ? '[]' : '';
                    }
                    
                    ClientDetails::updateOrCreate(
                        [
                            'app_name' => 'logistics',
                            'client_id' => $client->id,
                            'client_identifier' => $client->client_identifier,
                            'name' => "fleet.{$key}"
                        ],
                        [
                            'value' => $value
                        ]
                    );
                }
            }

            // Process pricing settings
            if (isset($request->pricing)) {
                foreach ($request->pricing as $key => $value) {
                    if ($key === 'payment_methods') {
                        $value = json_encode($value);
                    }
                    
                    // Handle empty values
                    if ($value === '' || $value === null) {
                        $value = $key === 'payment_methods' ? '[]' : '';
                    }
                    
                    ClientDetails::updateOrCreate(
                        [
                            'app_name' => 'logistics',
                            'client_id' => $client->id,
                            'client_identifier' => $client->client_identifier,
                            'name' => "pricing.{$key}"
                        ],
                        [
                            'value' => $value
                        ]
                    );
                }
            }

            // Process booking settings
            if (isset($request->booking)) {
                foreach ($request->booking as $key => $value) {
                    // Handle empty values
                    if ($value === '' || $value === null) {
                        $value = '';
                    }
                    
                    ClientDetails::updateOrCreate(
                        [
                            'app_name' => 'logistics',
                            'client_id' => $client->id,
                            'client_identifier' => $client->client_identifier,
                            'name' => "booking.{$key}"
                        ],
                        [
                            'value' => $value
                        ]
                    );
                }
            }

            // Process notification settings
            if (isset($request->notifications)) {
                foreach ($request->notifications as $key => $value) {
                    // Handle empty values
                    if ($value === '' || $value === null) {
                        $value = '';
                    }
                    
                    ClientDetails::updateOrCreate(
                        [
                            'app_name' => 'logistics',
                            'client_id' => $client->id,
                            'client_identifier' => $client->client_identifier,
                            'name' => "notifications.{$key}"
                        ],
                        [
                            'value' => $value
                        ]
                    );
                }
            }

            // Return updated settings
            $settings = ClientDetails::where('client_identifier', $client->client_identifier)
                ->where('app_name', 'logistics')
                ->get();

            $formattedSettings = [
                'general' => [],
                'fleet' => [],
                'pricing' => [],
                'booking' => [],
                'notifications' => []
            ];

            foreach ($settings as $setting) {
                $parts = explode('.', $setting->name);
                $section = $parts[0];
                
                if (count($parts) > 1) {
                    $key = $parts[1];
                    
                    if ($section === 'general' && $key === 'operating_hours') {
                        $formattedSettings[$section][$key] = json_decode($setting->value, true) ?? [];
                    } elseif ($section === 'fleet' && $key === 'truck_types') {
                        $formattedSettings[$section][$key] = json_decode($setting->value, true) ?? [];
                    } elseif ($section === 'fleet' && $key === 'insurance_providers') {
                        $formattedSettings[$section][$key] = json_decode($setting->value, true) ?? [];
                    } elseif ($section === 'pricing' && $key === 'payment_methods') {
                        $formattedSettings[$section][$key] = json_decode($setting->value, true) ?? [];
                    } else {
                        $formattedSettings[$section][$key] = $setting->value;
                    }
                }
            }

            return response()->json([
                'message' => 'Settings saved successfully',
                'data' => $formattedSettings
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error saving logistics settings: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to save settings',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
