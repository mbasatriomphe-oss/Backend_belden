<?php

namespace App\Console\Commands;

use App\Models\ventes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDebtReminders extends Command
{
    protected $signature = 'notifications:debt-reminders';

    protected $description = 'Send debt reminder emails automatically to customers with unpaid balances.';

    public function handle(): int
    {
        $debts = ventes::query()->where('reste_a_payer', '>', 0)->with('client.user')->get();

        if ($debts->isEmpty()) {
            $this->info('No debt reminders to send.');
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($debts as $vente) {
            $user = $vente->client?->user;
            $email = strtolower(trim((string) ($user?->email ?? '')));

            if ($email === '') {
                continue;
            }

            Mail::raw("Bonjour,\n\nNous vous rappelons que votre facture {$vente->code} a un montant restant à payer de {$vente->reste_a_payer}.\nMerci de procéder au paiement dès que possible.\n\nCordialement,\nBelden", function ($message) use ($email, $vente) {
                $message->to($email)->subject('Rappel de paiement - Facture ' . $vente->code);
            });

            $sent++;
        }

        $this->info('Debt reminder emails sent: ' . $sent);
        return self::SUCCESS;
    }
}
