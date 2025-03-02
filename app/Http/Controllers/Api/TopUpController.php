<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\PaymentMethod;

class TopUpController extends Controller
{
    public function onStore(Request $request)
    {
        $data = $request->only('amount', 'pin', 'payment_method_code');

        $validator = Validator::make($data, [
            'amount' => 'required|integer|min:10000',
            'pin' => 'required|digits:6',
            'payment_method_code' => 'required|in:bni_va,bca_va,bri_va,gopay,shopeepay'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        $hasValidPin = onPinChecker($request->pin);

        if (!$hasValidPin) {
            return response()->json(['message' => 'Your PIN is wrong'], 400);
        }

        $transactionType = TransactionType::where('code', 'top_up')->first();
        $paymentMethod = PaymentMethod::where('code', $request->payment_method_code)->first();

        DB::beginTransaction();

        try {
            $transaction = Transaction::create([
                'user_id' => auth()->user()->id,
                'payment_method_id' => $paymentMethod->id,
                'transaction_type_id' => $transactionType->id,
                'transaction_code' => strtoupper(Str::random(10)),
                'amount' => $request->amount,
                'description' => 'Top Up via ' . $paymentMethod->name,
                'status' => 'pending'
            ]);

            $params = $this->buildMidtransParameters([
                'transaction_code' => $transaction->transaction_code,
                'amount' => $transaction->amount,
                'payment_method' => $paymentMethod->code
            ]);

            $midtrans = $this->callMidtrans($params);

            DB::commit();
            
            return response()->json($midtrans);
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return response()->json(['message' => $throwable->getMessage()], 500);
        }
    }

    private function callMidtrans(array $params)
    {
        // Midtrans documentation/integration-guide
        // Sample Request
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_IS_PRODUCTION');
        \Midtrans\Config::$isSanitized = (bool) env('MIDTRANS_IS_SANITIZED');
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANS_IS_3DS');

        $createTransaction = \Midtrans\Snap::createTransaction($params);
    
        return [
            'redirect_url' => $createTransaction->redirect_url,
            'token' => $createTransaction->token
        ];
    }

    private function buildMidtransParameters(array $params)
    {
        // Midtrans documentation/integration-guide
        // Sample Request
        $transactionDetails = [
            'order_id' => $params['transaction_code'],
            'gross_amount' => $params['amount']
        ];

        $user = auth()->user();
        $splitName = $this->splitToFirstLastName($user->name);
        // Midtrans documentation/integration-guide
        // Sample Request
        $customerDetails = [
            'first_name' => $splitName['first_name'],
            'last_name' => $splitName['last_name'],
            'email' => $user->email
        ];

        // Midtrans documentation/integration-guide
        // Sample Request
        $enabledPayments = [
            $params['payment_method']
        ];

        return [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
            'enabled_payments' => $enabledPayments
        ];
    }

    private function splitToFirstLastName($fullName)
    {
        $name = explode(' ', $fullName);

        $firstName = implode(' ', $name);
        $lastName = count($name) > 1 ? array_pop($name) : $fullName;

        return [
            'first_name' => $firstName,
            'last_name' => $lastName
        ];
    }
}



