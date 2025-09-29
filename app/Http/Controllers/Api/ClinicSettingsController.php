<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientDetails;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ClinicSettingsController extends Controller
{
    /**
     * Get clinic settings
     */
    public function index(Request $request)
    {
        try {
            // Initialize default settings structure
            $formattedSettings = [
                'general' => [
                    'clinic_name' => '',
                    'description' => '',
                    'address' => '',
                    'phone' => '',
                    'email' => '',
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
                'appointments' => [
                    'enable_appointments' => true,
                    'appointment_duration' => 30,
                    'appointment_buffer' => 15,
                    'enable_reminders' => true,
                    'reminder_hours' => 24,
                    'enable_online_booking' => true
                ],
                'features' => [
                    'enable_insurance' => true,
                    'insurance_providers' => [],
                    'enable_prescriptions' => true,
                    'enable_lab_results' => true,
                    'enable_dental_charts' => true,
                    'enable_medical_history' => true,
                    'enable_patient_portal' => true
                ],
                'billing' => [
                    'enable_billing' => true,
                    'tax_rate' => 10,
                    'currency' => 'USD',
                    'payment_methods' => [],
                    'invoice_prefix' => 'INV-',
                    'invoice_footer' => 'Thank you for choosing our clinic!'
                ]
            ];

            // Get settings from database
            $settings = ClientDetails::where('client_identifier', $request->client_identifier)
                ->where('app_name', 'clinic')
                ->get();
            
            // Process settings if any exist
            foreach ($settings as $setting) {
                $parts = explode('.', $setting->name);
                $section = $parts[0];
                
                if (count($parts) > 1) {
                    $key = $parts[1];
                    
                    if ($section === 'general' && $key === 'operating_hours') {
                        $formattedSettings[$section][$key] = json_decode($setting->value, true) ?? $formattedSettings[$section][$key];
                    } elseif ($section === 'features' && $key === 'insurance_providers') {
                        $formattedSettings[$section][$key] = json_decode($setting->value, true) ?? [];
                    } elseif ($section === 'billing' && $key === 'payment_methods') {
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
            Log::error('Error fetching clinic settings: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch settings',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save clinic settings
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
                            'app_name' => 'clinic',
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

            // Process appointment settings
            if (isset($request->appointments)) {
                foreach ($request->appointments as $key => $value) {
                    // Handle empty values
                    if ($value === '' || $value === null) {
                        $value = '';
                    }
                    
                    ClientDetails::updateOrCreate(
                        [
                            'app_name' => 'clinic',
                            'client_id' => $client->id,
                            'client_identifier' => $client->client_identifier,
                            'name' => "appointments.{$key}"
                        ],
                        [
                            'value' => $value
                        ]
                    );
                }
            }

            // Process feature settings
            if (isset($request->features)) {
                foreach ($request->features as $key => $value) {
                    if ($key === 'insurance_providers') {
                        $value = json_encode($value);
                    }
                    
                    // Handle empty values
                    if ($value === '' || $value === null) {
                        $value = $key === 'insurance_providers' ? '[]' : '';
                    }
                    
                    ClientDetails::updateOrCreate(
                        [
                            'app_name' => 'clinic',
                            'client_id' => $client->id,
                            'client_identifier' => $client->client_identifier,
                            'name' => "features.{$key}"
                        ],
                        [
                            'value' => $value
                        ]
                    );
                }
            }

            // Process billing settings
            if (isset($request->billing)) {
                foreach ($request->billing as $key => $value) {
                    if ($key === 'payment_methods') {
                        $value = json_encode($value);
                    }
                    
                    // Handle empty values
                    if ($value === '' || $value === null) {
                        $value = $key === 'payment_methods' ? '[]' : '';
                    }
                    
                    ClientDetails::updateOrCreate(
                        [
                            'app_name' => 'clinic',
                            'client_id' => $client->id,
                            'client_identifier' => $client->client_identifier,
                            'name' => "billing.{$key}"
                        ],
                        [
                            'value' => $value
                        ]
                    );
                }
            }

            // Return updated settings
            $settings = ClientDetails::where('client_identifier', $client->client_identifier)
                ->where('app_name', 'clinic')
                ->get();

            $formattedSettings = [
                'general' => [],
                'appointments' => [],
                'features' => [],
                'billing' => []
            ];

            foreach ($settings as $setting) {
                $parts = explode('.', $setting->name);
                $section = $parts[0];
                
                if (count($parts) > 1) {
                    $key = $parts[1];
                    
                    if ($section === 'general' && $key === 'operating_hours') {
                        $formattedSettings[$section][$key] = json_decode($setting->value, true) ?? [];
                    } elseif ($section === 'features' && $key === 'insurance_providers') {
                        $formattedSettings[$section][$key] = json_decode($setting->value, true) ?? [];
                    } elseif ($section === 'billing' && $key === 'payment_methods') {
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
            Log::error('Error saving clinic settings: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to save settings',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
