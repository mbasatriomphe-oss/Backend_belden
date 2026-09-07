<?php

namespace App\Http\Controllers;

use App\Models\clients;
use App\Models\User;
use App\Models\ventes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CustomerNotificationController extends Controller
{
    public function sendStockAvailable(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer',
            'manual' => 'nullable|boolean',
        ]);

        $userIds = $request->input('user_ids');
        $productIds = $request->input('product_ids');

        $clients = $this->getCustomerEmails($userIds);

        if ($clients->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun client avec e-mail valide trouvé.',
            ], 404);
        }

        $sent = 0;

        foreach ($clients as $client) {
            $email = strtolower(trim((string) $client->email));
            if ($email === '') {
                continue;
            }

            $productNames = $this->buildStockProductNames($productIds);
            $subject = 'Produit de retour en stock';
            $content = $productNames === ''
                ? 'Nous avons le plaisir de vous informer que plusieurs produits sont de nouveau disponibles.'
                : 'Nous avons le plaisir de vous informer que les produits suivants sont de nouveau en stock : ' . $productNames . '.';

            Mail::raw($content, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });

            $sent++;
        }

        return response()->json([
            'status' => 'success',
            'message' => $sent . ' notification(s) envoyée(s) aux clients.',
            'sent' => $sent,
        ]);
    }

    public function sendDebtReminders(Request $request): JsonResponse
    {
        $request->validate([
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'integer',
            'manual' => 'nullable|boolean',
        ]);

        $clientIds = $request->input('client_ids');
        $query = ventes::query()->where('reste_a_payer', '>', 0)->with('client.user');

        if (!empty($clientIds)) {
            $query->whereIn('id_client', $clientIds);
        }

        $debts = $query->get();

        if ($debts->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucune dette en attente de paiement.',
            ], 404);
        }

        $sent = 0;

        foreach ($debts as $vente) {
            $client = $vente->client;
            $user = $client?->user;
            $email = strtolower(trim((string) ($user?->email ?? '')));

            if ($email === '') {
                continue;
            }

            $subject = 'Rappel de paiement';
            $message = "Bonjour,\n\nNous vous rappelons que votre facture {$vente->code} présente un montant restant à payer de {$vente->reste_a_payer}.
\nMerci de procéder au paiement dès que possible.\n\nCordialement,\nBelden";

            Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)->subject($subject);
            });

            $sent++;
        }

        return response()->json([
            'status' => 'success',
            'message' => $sent . ' rappel(s) envoyé(s) aux clients concernés.',
            'sent' => $sent,
        ]);
    }

    protected function getCustomerEmails($userIds = null)
    {
        $query = User::query()->where('role', 'user')->whereNotNull('email')->where('email', '!=', '');

        if (!empty($userIds)) {
            $query->whereIn('id', $userIds);
        }

        return $query->get();
    }

    protected function buildStockProductNames($productIds = null): string
    {
        $query = \App\Models\produits::query()->select(['nom', 'code'])
            ->whereNotNull('nom');

        if (!empty($productIds)) {
            $query->whereIn('id', $productIds);
        }

        $products = $query->limit(10)->get();

        return $products->map(fn ($product) => $product->nom ?: $product->code)->implode(', ');
    }
}
