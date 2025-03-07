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
            ->where('name', 'restaurant_info.restaurant_name')
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->select('client_id', 'client_identifier', 'value')
            ->get()
            ->map(function($item) {
                $menuItems = FnbMenuItem::where('client_identifier', $item->client_identifier)
                    ->select('id', 'name', 'price', 'category', 'image', 'is_available_personal', 'is_available_online')
                    ->get();

                return [
                    'client_id' => $item->client_id,
                    'client_identifier' => $item->client_identifier,
                    'restaurant_name' => $item->value,
                    'menu_items' => $menuItems
                ];
            });

        return response()->json(['restaurants' => $restaurants]);
    }
    
    
}
