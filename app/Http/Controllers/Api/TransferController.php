<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\TransferHistory;
use App\Models\TransactionType;
use App\Models\Wallet;
use App\Models\User;
use App\Models\PaymentMethod;

class TransferController extends Controller
{
    public function onStore(Request $request)
    {
        $data = $request->only('amount', 'pin', 'send_to');

        $validator = Validator::make($data, [
            'amount' => 'required|integer|min:10000',
            'pin' => 'required|digits:6',
            'send_to' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        $sender = auth()->user();
        $receiver = User::select('users.id', 'users.username')->join('wallets', 'wallets.user_id', 'users.id')->where('users.username', $request->send_to)->orWhere('wallets.card_number', $request->send_to)->first();

        $hasValidPin = onPinChecker($request->pin);

        if (!$hasValidPin) {
            return response()->json(['message' => 'Your PIN is wrong'], 400);
        }

        if (!$receiver) {
            return response()->json(['message' => 'User receiver not found'], 404);
        }

        if ($sender->id == $receiver->id) {
            return response()->json(['message' => "You can't transfer to yourself"], 400);
        }

        $senderWallet = Wallet::where('user_id', $sender->id)->first();

        if ($senderWallet->balance < $request->amount) {
            return response()->json(['message' => 'Your balance is not enough'], 400);
        }

        DB::beginTransaction();
        try {
            $transactionType = TransactionType::where('code', ['receive', 'transfer'])->orderBy('code', 'asc')->get();
        
            $receiveTransactionType = $transactionType->first();
            $transferTransactionType = $transactionType->last();

            $transactionCode = strtoupper(Str::random(10));
            $paymentMethod = PaymentMethod::where('code', 'ayo')->first();

            // Transaction for Transfer
            $transferTransaction = Transaction::create([
                'user_id' => $sender->id,
                'transaction_type_id' => $transferTransactionType->id,
                'description' => 'Transfer funds to '.$receiver->username,
                'amount' => $request->amount,
                'transaction_code' => $transactionCode,
                'status' => 'success',
                'payment_method_id' => $paymentMethod->id
            ]);
            
            // Decrease Balance Sender
            $senderWallet->decrement('balance', $request->amount);

            // Transaction for Receive
            $transferTransaction = Transaction::create([
                'user_id' => $receiver->id,
                'transaction_type_id' => $receiveTransactionType->id,
                'description' => 'Receive funds from '.$sender->username,
                'amount' => $request->amount,
                'transaction_code' => $transactionCode,
                'status' => 'success',
                'payment_method_id' => $paymentMethod->id
            ]);
            
            // Increase Balance Receiver
            Wallet::where('user_id', $receiver->id)->increment('balance', $request->amount);

            // Create Transfer History
            TransferHistory::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'transaction_code' => $transactionCode
            ]);

            DB::commit();
            return response(['message' => 'Transfer Success']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
}
