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
            ->groupBy('client_identifier')
            ->map(function($items) {
                $restaurantInfo = [];
                foreach ($items as $item) {
                    $field = str_replace('restaurant_info.', '', $item->name);
                    $restaurantInfo[$field] = $item->value;
                }

                $menuItems = FnbMenuItem::where('client_identifier', $items->first()->client_identifier)
                    ->select('id', 'name', 'price', 'category', 'image', 'is_available_personal', 'is_available_online', 'delivery_fee')
                    ->get();

                return [
                    'client_id' => $items->first()->client_id,
                    'client_identifier' => $items->first()->client_identifier,
                    'restaurant_name' => $restaurantInfo['restaurant_name'] ?? '',
                    'contact_number' => $restaurantInfo['contact_number'] ?? '',
                    'website' => $restaurantInfo['website'] ?? '',
                    'address' => $restaurantInfo['address'] ?? '',
                    'menu_items' => $menuItems
                ];
            });

        return response()->json(['restaurants' => $restaurants]);
    }
    
    
}
