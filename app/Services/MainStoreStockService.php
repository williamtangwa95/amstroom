<?php

namespace App\Services;

use App\Models\MainStock;
use App\Models\StockLog;
use App\Models\Item;
use App\Models\ShopStock;
use App\Models\User;
use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MainStoreStockService
{
    /**
     * Process stock addition to the Main Store using the MIN selling price rule.
     *
     * @param int $itemId
     * @param int $quantity
     * @param float $buyingPrice
     * @param float $sellingPrice
     * @param string|null $dateReceived
     * @param int|null $userId
     * @param string $sourceNotes
     * @param bool $isReferenceOnly If true (e.g. direct shop stock addition), main_stock remaining_quantity is kept at 0 or unchanged.
     * @return array
     */
    public function processStockAddition(
        int $itemId,
        int $quantity,
        float $buyingPrice,
        float $sellingPrice,
        ?string $dateReceived = null,
        ?int $userId = null,
        string $sourceNotes = 'Stock addition',
        bool $isReferenceOnly = false
    ): array {
        if ($buyingPrice < 0) {
            throw new InvalidArgumentException('Buying price cannot be negative.');
        }

        if ($sellingPrice < 0) {
            throw new InvalidArgumentException('Selling price cannot be negative.');
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $item = Item::findOrFail($itemId);
        $dateReceived = $dateReceived ?? now()->toDateString();
        $performerId = $userId ?? Auth::id();

        return DB::transaction(function () use (
            $item,
            $itemId,
            $quantity,
            $buyingPrice,
            $sellingPrice,
            $dateReceived,
            $performerId,
            $sourceNotes,
            $isReferenceOnly
        ) {
            // Concurrency protection: lock the existing main stock row for update if present
            $existingMainStock = MainStock::where('item_id', $itemId)
                ->lockForUpdate()
                ->first();

            $previousPrice = null;
            $previousQuantity = 0;
            $priceChanged = false;

            if (!$existingMainStock) {
                // Rule A: Product does not exist in Main Store
                $finalPrice = (float) $sellingPrice;
                $newQuantity = $isReferenceOnly ? 0 : $quantity;

                $mainStock = MainStock::create([
                    'item_id'            => $itemId,
                    'buying_price'       => $buyingPrice,
                    'selling_price'      => $finalPrice,
                    'stocked_quantity'   => $quantity,
                    'remaining_quantity' => $newQuantity,
                    'date_received'      => $dateReceived,
                    'is_price_pending'   => false,
                ]);

                $flashMessage = "Stock added successfully to Main Store. Product: {$item->item_name}, Selling Price: TZS "
                    . number_format($finalPrice, 2) . ", Quantity: {$quantity}.";
            } else {
                $mainStock = $existingMainStock;
                $previousPrice = (float) $mainStock->selling_price;
                $previousQuantity = (int) $mainStock->remaining_quantity;
                $incomingPrice = (float) $sellingPrice;

                // Rule B & C: Final Main Store Selling Price = MIN(existing, incoming)
                if ($incomingPrice < $previousPrice) {
                    $finalPrice = $incomingPrice;
                    $priceChanged = true;
                } else {
                    $finalPrice = $previousPrice;
                }

                $newQuantity = $isReferenceOnly ? $previousQuantity : ($previousQuantity + $quantity);
                $newStockedQuantity = (int) $mainStock->stocked_quantity + $quantity;

                $mainStock->update([
                    'buying_price'       => $buyingPrice,
                    'selling_price'      => $finalPrice,
                    'stocked_quantity'   => $newStockedQuantity,
                    'remaining_quantity' => $newQuantity,
                    'date_received'      => $dateReceived,
                ]);

                if ($priceChanged) {
                    $flashMessage = "Stock added successfully. Main Store selling price updated from TZS "
                        . number_format($previousPrice, 2) . " to lower price TZS "
                        . number_format($finalPrice, 2) . ". Quantity Added: {$quantity}, New Main Store Quantity: {$newQuantity}.";
                } else {
                    $flashMessage = "Stock added successfully. Main Store price kept at TZS "
                        . number_format($finalPrice, 2) . " (Incoming: TZS "
                        . number_format($incomingPrice, 2) . "). Quantity Added: {$quantity}, New Main Store Quantity: {$newQuantity}.";
                }
            }

            // Sync shop stock prices if main store selling price dropped
            if ($priceChanged) {
                $isIndependent = Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';
                $shopStocks = ShopStock::where('item_id', $itemId)->get();

                foreach ($shopStocks as $shopStock) {
                    if ($isIndependent) {
                        $shopStock->update([
                            'buying_price'         => $finalPrice,
                            'is_sellable'           => false,
                            'is_price_pending'      => true,
                            'pending_selling_price' => null,
                        ]);

                        $usersToNotify = User::where('shop_id', $shopStock->shop_id)
                            ->whereIn('role', ['shop_admin', 'seller'])
                            ->get();

                        foreach ($usersToNotify as $u) {
                            Notification::create([
                                'user_id' => $u->id,
                                'title'   => 'Main Store Price Updated',
                                'message' => "Main Store selling price for \"{$item->item_name}\" dropped to TZS "
                                    . number_format($finalPrice, 2) . ". Please review and update shop selling price.",
                            ]);
                        }
                    } else {
                        $shopStock->update([
                            'is_price_pending'      => true,
                            'pending_selling_price' => $finalPrice,
                        ]);

                        $admins = User::where('shop_id', $shopStock->shop_id)
                            ->where('role', 'shop_admin')
                            ->get();

                        foreach ($admins as $admin) {
                            Notification::create([
                                'user_id' => $admin->id,
                                'title'   => 'Main Store Price Updated',
                                'message' => "Main Store selling price for \"{$item->item_name}\" updated to TZS "
                                    . number_format($finalPrice, 2) . ". Pending shop approval.",
                            ]);
                        }
                    }
                }
            }

            // Historical Audit Logging (StockLog)
            $logNote = "Main Store Stock Addition ({$sourceNotes}). ";
            if ($previousPrice !== null) {
                $logNote .= "Prev Price: TZS " . number_format($previousPrice, 2)
                    . ", Incoming Price: TZS " . number_format($sellingPrice, 2)
                    . ", Final Price: TZS " . number_format($finalPrice, 2) . ". "
                    . "Prev Qty: {$previousQuantity}, Added Qty: {$quantity}, New Qty: {$newQuantity}.";
            } else {
                $logNote .= "Initial Price: TZS " . number_format($finalPrice, 2)
                    . ", Added Qty: {$quantity}, New Qty: {$newQuantity}.";
            }

            StockLog::create([
                'item_id'          => $itemId,
                'from_location'    => 'Supplier',
                'to_location'      => 'Main Warehouse',
                'quantity'         => $quantity,
                'transaction_type' => 'STOCK_RECEIVED',
                'performed_by'     => $performerId,
                'date'             => $dateReceived,
                'notes'            => $logNote,
            ]);

            return [
                'main_stock'        => $mainStock,
                'previous_price'    => $previousPrice,
                'incoming_price'    => (float) $sellingPrice,
                'final_price'       => $finalPrice,
                'price_changed'     => $priceChanged,
                'previous_quantity' => $previousQuantity,
                'added_quantity'    => $quantity,
                'new_quantity'      => $newQuantity,
                'flash_message'     => $flashMessage,
            ];
        });
    }
}
