<?php

use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Melihovv\Base64ImageDecoder\Base64ImageDecoder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function getUser($param)
{
    $user = User::where('id', $param)->orWhere('email', $param)->orWhere('username', $param)->first();

    $wallet = Wallet::where('user_id', $user->id)->first();
    $user->profile_picture = $user->profile_picture ? url('storage/' . $user->profile_picture) : "";
    $user->ktp = $user->ktp ? url('storage/' . $user->ktp) : "";
    $user->balance = $wallet->balance;
    $user->card_number = $wallet->card_number;
    $user->pin = $wallet->pin;

    return $user;
}

function showUserResponse($request, $token)
{
    $user =  getUser($request->email);
    $user->token = $token;
    $user->token_expires_in = auth()->factory()->getTTL() * 60;
    $user->token_type = 'bearer';

    $userResponse = response()->json($user);

    return $userResponse;
}

// Pengecekan tiap ada transaksi
function onPinChecker($pin) {
    $userId = auth()->user()->id;
    $wallet = Wallet::where('user_id', $userId)->first();

    if ($wallet == null) return false;

    if ($wallet->pin == $pin) return true;

    return false;
}

function uploadBase64Image($base64Image) 
{
    $decoder = new Base64ImageDecoder($base64Image, $allowedFormats = ['jpeg', 'png', 'jpg']);

    $decodedContent = $decoder->getDecodedContent();    //Dapatkan content gambar yg sudah terdecode
    $format = $decoder->getFormat();    //Mendapatkan informasi format gambar
    $image = Str::random(10).'.'.$format;   //Nama file gambar
    Storage::disk('public')->put($image, $decodedContent);  //Simpan di Storage

    return $image;
}