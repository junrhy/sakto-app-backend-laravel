<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\ClinicInventoryItem;
use App\Models\ClinicInventoryTransaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class ClinicInventoryController extends Controller
{
    /**
     * Display a listing of inventory items
     */
    public function index(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        
        $query = ClinicInventoryItem::forClient($clientIdentifier);
        
        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->byType($request->type);
        }
        
        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }
        
        // Filter by stock status
        if ($request->has('stock_status')) {
            switch ($request->stock_status) {
                case 'low_stock':
                    $query->lowStock();
                    break;
                case 'expiring_soon':
                    $query->expiringSoon();
                    break;
                case 'expired':
                    $query->expired();
                    break;
            }
        }
        
        // Search by name or SKU
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }
        
        $items = $query->orderBy('name')->paginate(20);
        
        // Get summary statistics
        $summary = [
            'total_items' => ClinicInventoryItem::forClient($clientIdentifier)->count(),
            'low_stock_items' => ClinicInventoryItem::forClient($clientIdentifier)->lowStock()->count(),
            'expiring_soon_items' => ClinicInventoryItem::forClient($clientIdentifier)->expiringSoon()->count(),
            'expired_items' => ClinicInventoryItem::forClient($clientIdentifier)->expired()->count(),
            'total_value' => ClinicInventoryItem::forClient($clientIdentifier)
                ->selectRaw('SUM(current_stock * unit_price) as total')
                ->value('total') ?? 0
        ];
        
        return response()->json([
            'items' => $items,
            'summary' => $summary
        ]);
    }

    /**
     * Store a newly created inventory item
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:medicine,equipment,supply,other',
            'category' => 'nullable|string|max:255',
            'sku' => 'required|string|max:255|unique:clinic_inventory_items,sku',
            'barcode' => 'nullable|string|max:255',
            'unit_price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'maximum_stock' => 'nullable|integer|min:0',
            'unit_of_measure' => 'required|string|max:255',
            'expiry_date' => 'nullable|date',
            'supplier' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string'
        ]);

        $item = ClinicInventoryItem::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'category' => $request->category,
            'sku' => $request->sku,
            'barcode' => $request->barcode,
            'unit_price' => $request->unit_price,
            'current_stock' => $request->current_stock,
            'minimum_stock' => $request->minimum_stock,
            'maximum_stock' => $request->maximum_stock,
            'unit_of_measure' => $request->unit_of_measure,
            'expiry_date' => $request->expiry_date,
            'supplier' => $request->supplier,
            'location' => $request->location,
            'client_identifier' => $request->client_identifier
        ]);

        // Create initial stock transaction if stock > 0
        if ($request->current_stock > 0) {
            ClinicInventoryTransaction::create([
                'clinic_inventory_item_id' => $item->id,
                'transaction_type' => 'in',
                'quantity' => $request->current_stock,
                'unit_price' => $request->unit_price,
                'total_amount' => $request->current_stock * $request->unit_price,
                'notes' => 'Initial stock',
                'performed_by' => $request->performed_by,
                'transaction_date' => now()->toDateString(),
                'client_identifier' => $request->client_identifier
            ]);
        }

        return response()->json([
            'message' => 'Inventory item created successfully',
            'item' => $item->load('transactions')
        ], 201);
    }

    /**
     * Display the specified inventory item
     */
    public function show(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        $item = ClinicInventoryItem::forClient($clientIdentifier)
            ->with('transactions')
            ->findOrFail($id);

        return response()->json($item);
    }

    /**
     * Update the specified inventory item
     */
    public function update(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        $item = ClinicInventoryItem::forClient($clientIdentifier)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:medicine,equipment,supply,other',
            'category' => 'nullable|string|max:255',
            'sku' => 'required|string|max:255|unique:clinic_inventory_items,sku,' . $id,
            'barcode' => 'nullable|string|max:255',
            'unit_price' => 'required|numeric|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'maximum_stock' => 'nullable|integer|min:0',
            'unit_of_measure' => 'required|string|max:255',
            'expiry_date' => 'nullable|date',
            'supplier' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $item->update($request->only([
            'name', 'description', 'type', 'category', 'sku', 'barcode',
            'unit_price', 'minimum_stock', 'maximum_stock', 'unit_of_measure',
            'expiry_date', 'supplier', 'location', 'is_active'
        ]));

        return response()->json([
            'message' => 'Inventory item updated successfully',
            'item' => $item
        ]);
    }

    /**
     * Remove the specified inventory item
     */
    public function destroy(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        $item = ClinicInventoryItem::forClient($clientIdentifier)->findOrFail($id);

        $item->delete();

        return response()->json([
            'message' => 'Inventory item deleted successfully'
        ]);
    }

    /**
     * Add stock to an inventory item
     */
    public function addStock(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        $item = ClinicInventoryItem::forClient($clientIdentifier)->findOrFail($id);

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'reference_number' => 'nullable|string|max:255'
        ]);

        DB::transaction(function () use ($item, $request, $clientIdentifier) {
            $quantity = $request->quantity;
            $unitPrice = $request->unit_price ?? $item->unit_price;
            $totalAmount = $quantity * $unitPrice;

            // Create transaction record
            ClinicInventoryTransaction::create([
                'clinic_inventory_item_id' => $item->id,
                'transaction_type' => 'in',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'reference_number' => $request->reference_number,
                'performed_by' => $request->performed_by,
                'transaction_date' => now()->toDateString(),
                'client_identifier' => $clientIdentifier
            ]);

            // Update stock
            $item->increment('current_stock', $quantity);
        });

        return response()->json([
            'message' => 'Stock added successfully',
            'item' => $item->fresh()
        ]);
    }

    /**
     * Remove stock from an inventory item
     */
    public function removeStock(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        $item = ClinicInventoryItem::forClient($clientIdentifier)->findOrFail($id);

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'reference_number' => 'nullable|string|max:255'
        ]);

        if ($item->current_stock < $request->quantity) {
            return response()->json([
                'error' => 'Insufficient stock. Available: ' . $item->current_stock
            ], 400);
        }

        DB::transaction(function () use ($item, $request, $clientIdentifier) {
            $quantity = $request->quantity;
            $unitPrice = $item->unit_price;
            $totalAmount = $quantity * $unitPrice;

            // Create transaction record
            ClinicInventoryTransaction::create([
                'clinic_inventory_item_id' => $item->id,
                'transaction_type' => 'out',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'reference_number' => $request->reference_number,
                'performed_by' => $request->performed_by,
                'transaction_date' => now()->toDateString(),
                'client_identifier' => $clientIdentifier
            ]);

            // Update stock
            $item->decrement('current_stock', $quantity);
        });

        return response()->json([
            'message' => 'Stock removed successfully',
            'item' => $item->fresh()
        ]);
    }

    /**
     * Adjust stock (for corrections)
     */
    public function adjustStock(Request $request, $id)
    {
        $clientIdentifier = $request->client_identifier;
        $item = ClinicInventoryItem::forClient($clientIdentifier)->findOrFail($id);

        $request->validate([
            'new_quantity' => 'required|integer|min:0',
            'notes' => 'required|string'
        ]);

        $oldQuantity = $item->current_stock;
        $newQuantity = $request->new_quantity;
        $difference = $newQuantity - $oldQuantity;

        DB::transaction(function () use ($item, $request, $clientIdentifier, $difference) {
            // Create transaction record
            ClinicInventoryTransaction::create([
                'clinic_inventory_item_id' => $item->id,
                'transaction_type' => 'adjustment',
                'quantity' => abs($difference),
                'unit_price' => $item->unit_price,
                'total_amount' => abs($difference) * $item->unit_price,
                'notes' => $request->notes,
                'performed_by' => $request->performed_by,
                'transaction_date' => now()->toDateString(),
                'client_identifier' => $clientIdentifier
            ]);

            // Update stock
            $item->update(['current_stock' => $request->new_quantity]);
        });

        return response()->json([
            'message' => 'Stock adjusted successfully',
            'item' => $item->fresh()
        ]);
    }

    /**
     * Get transaction history for an item
     */
    public function getTransactions($id, Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $item = ClinicInventoryItem::forClient($clientIdentifier)->findOrFail($id);

        $query = $item->transactions()->forClient($clientIdentifier);

        if ($request->has('type') && $request->type) {
            $query->byType($request->type);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->where('transaction_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('transaction_date', '<=', $request->end_date);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }

    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $items = ClinicInventoryItem::forClient($clientIdentifier)
            ->lowStock()
            ->orderBy('current_stock', 'asc')
            ->get();

        return response()->json($items);
    }

    /**
     * Get expiring items alerts
     */
    public function getExpiringAlerts(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $days = $request->get('days', 30);
        
        $items = ClinicInventoryItem::forClient($clientIdentifier)
            ->expiringSoon($days)
            ->orderBy('expiry_date', 'asc')
            ->get();

        return response()->json($items);
    }

    /**
     * Get expired items
     */
    public function getExpiredItems(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $items = ClinicInventoryItem::forClient($clientIdentifier)
            ->expired()
            ->orderBy('expiry_date', 'asc')
            ->get();

        return response()->json($items);
    }

    /**
     * Get inventory categories
     */
    public function getCategories(Request $request)
    {
        $clientIdentifier = $request->client_identifier;
        $categories = ClinicInventoryItem::forClient($clientIdentifier)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values();

        return response()->json($categories);
    }
}
