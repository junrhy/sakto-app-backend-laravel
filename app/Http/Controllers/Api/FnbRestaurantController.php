<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\ClientDetails;
use App\Models\FnbMenuItem;

class FnbRestaurantController extends Controller
{
    public function index()
    {
        // Use caching to avoid repeated database queries
        $cacheKey = 'fnb_restaurants_' . md5('all_restaurants');
        
        return cache()->remember($cacheKey, 300, function () { // Cache for 5 minutes
            $restaurants = ClientDetails::where('app_name', 'fnb')
                ->whereIn('name', [
                    'restaurant_info.restaurant_name',
                    'restaurant_info.contact_number',
                    'restaurant_info.website',
                    'restaurant_info.address'
                ])
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->select('client_id', 'client_identifier', 'name', 'value')
                ->get()
                ->groupBy('client_identifier');

            // Get all client identifiers for batch menu item query
            $clientIdentifiers = $restaurants->keys()->toArray();
            
            // Batch load all menu items to avoid N+1 queries
            $allMenuItems = FnbMenuItem::whereIn('client_identifier', $clientIdentifiers)
                ->select('id', 'name', 'price', 'category', 'image', 'is_available_personal', 'is_available_online', 'delivery_fee', 'client_identifier')
                ->get()
                ->groupBy('client_identifier');

            return $restaurants->map(function($items) use ($allMenuItems) {
                $restaurantInfo = [];
                foreach ($items as $item) {
                    $field = str_replace('restaurant_info.', '', $item->name);
                    $restaurantInfo[$field] = $item->value;
                }

                $clientIdentifier = $items->first()->client_identifier;
                $menuItems = $allMenuItems->get($clientIdentifier, collect());

                return [
                    'client_id' => $items->first()->client_id,
                    'client_identifier' => $clientIdentifier,
                    'restaurant_name' => $restaurantInfo['restaurant_name'] ?? '',
                    'contact_number' => $restaurantInfo['contact_number'] ?? '',
                    'website' => $restaurantInfo['website'] ?? '',
                    'address' => $restaurantInfo['address'] ?? '',
                    'menu_items' => $menuItems
                ];
            });
        });
    }
    
    
}
