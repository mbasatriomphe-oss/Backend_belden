<?php

namespace App\Console\Commands;

use App\Models\produits;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendStockAvailableNotifications extends Command
{
    protected $signature = 'notifications:stock-available';

    protected $description = 'Send stock available email notifications to customers automatically.';

    public function handle(): int
    {
        $products = produits::query()
            ->where('quantite_stock', '>', 0)
            ->orderBy('id')
            ->get();

        $currentProductIds = $products->modelKeys();
        $previousProductIds = Cache::get('notifications.stock_available.product_ids', null);

        if ($previousProductIds === null) {
            $availableProducts = $products;
        } else {
            $newProductIds = array_values(array_diff($currentProductIds, $previousProductIds));
            $availableProducts = $products->whereIn('id', $newProductIds)->values();
        }

        Cache::forever('notifications.stock_available.product_ids', $currentProductIds);

        if ($availableProducts->isEmpty()) {
            $this->info('No newly available products for customer notification.');
            return self::SUCCESS;
        }

        $customers = User::query()->where('role', 'user')->whereNotNull('email')->where('email', '!=', '')->get();

        if ($customers->isEmpty()) {
            $this->warn('No customer emails found.');
            return self::SUCCESS;
        }

        $productNames = $availableProducts->map(fn ($product) => $product->nom ?: $product->code)->implode(', ');

        $sent = 0;

        foreach ($customers as $customer) {
            $email = strtolower(trim((string) $customer->email));
            if ($email === '') {
                continue;
            }

            Mail::raw("Bonjour,\n\nNous avons le plaisir de vous informer que les produits suivants sont de nouveau en stock : {$productNames}.\n\nMerci de votre fidélité.\n\nBelden", function ($message) use ($email) {
                $message->to($email)->subject('Produits de retour en stock');
            });

            $sent++;
        }

        $this->info('Stock available notifications sent to ' . $sent . ' customers.');
        return self::SUCCESS;
    }
}
