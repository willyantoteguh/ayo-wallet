<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\TransactionType;
use App\Models\Transaction;
use App\Models\PaymentMethod;
use App\Models\DataPlan;
use App\Models\DataPlanHistory;
use App\Models\Wallet;


class DataPlanController extends Controller
{
    public function onStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_plan_id' => 'required|integer',
            'phone_number' => 'required|string',
            'pin' => 'required|digits:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }

        $userId = auth()->user()->id;

        $transactionType = TransactionType::where('code', 'internet')->first();

        $paymentMethod = PaymentMethod::where('code', 'ayo')->first();
        
        // User that want to topup
        $userWallet = Wallet::where('user_id', $userId)->first();

        $dataPlan = DataPlan::find($request->data_plan_id);

        if(!$dataPlan) {
            return response()->json(['message' => 'Data plan not found'], 400);
        }

        $hasValidPin = onPinChecker($request->pin);

        if (!$hasValidPin) {
            return response()->json(['message' => 'Your PIN is wrong'], 400);
        }
        
        if ($userWallet->balance < $dataPlan->price) {
            return response()->json(['message' => 'Your balance is not enough'], 400);
        }

        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'user_id' => $userId,
                'transaction_type_id' => $transactionType->id,
                'payment_method_id' => $paymentMethod->id,
                'amount' => $dataPlan->price,
                'transaction_code' => strtoupper(Str::random(10)),
                'description' => 'Internet Data Plan '.$dataPlan->name,
                'status' => 'success'
            ]);

            DataPlanHistory::create([
                'data_plan_id' => $request->data_plan_id,
                'transaction_id' => $transaction->id,
                'phone_number' => $request->phone_number,
            ]);

            $userWallet->decrement('balance', $dataPlan->price);

            DB::commit();
            return response()->json(['message' => 'Buy Data Plan Success']);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['message' => $th->getMessage()], 500); 
        }
    }
}
