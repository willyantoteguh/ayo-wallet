<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Melihovv\Base64ImageDecoder\Base64ImageDecoder;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Wallet;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'pin' => 'required|digits:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }
        
        $user = User::where('email', $request->email)->exists();

        if ($user) {
            return response()->json(['message' => 'Email already taken'], 409);
        }

        // Memulai transaction manual
        DB::beginTransaction();

        try {
            $profilePicture = null;
            $ktp = null;

            if ($request->profile_picture) {
                $profilePicture = $this->uploadBase64Image($request->profile_picture);
            }
            
            if ($request->ktp) {
                $ktp = $this->uploadBase64Image($request->ktp);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => bcrypt($request->password),
                'profile_picture' => $profilePicture,
                'ktp' => $ktp,
                'has_verified' => ($ktp) ? true : false
            ]);

            $cardNumber = $this->generateCardNumber(16);

            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'pin' => $request->pin,
                'card_number' => $cardNumber
            ]);

            // Jika success maka melakukan commit
            DB::commit();

            $token = JWTAuth::attempt(['email' => $request->email, 'password' => $request->password]);

            return showUserResponse($request, $token);
        } catch (\Throwable $throwable) {
            // Ketika ada proses gagal maka data tidak diinsert ke DB
            DB::rollBack();
            return response()->json(['message' => $throwable->getMessage()], 500);
        }

    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string|min:6|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        try {
            $token = JWTAuth::attempt($credentials);

            if (!$token) {
                return response()->json(['message' => 'Login credentials are invalid']);
            }

            return showUserResponse($request, $token);
        } catch (JwtException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function generateCardNumber($length)
    {
        $result = '';
        for ($i=0; $i < $length; $i++) { 
            $result .= mt_rand(0, 9);
        }

        $wallet = Wallet::where('card_number', $result)->exists();

        if ($wallet) {
            return $this->generateCardNumber($length);
        }

        return $result;
    }    

    private function uploadBase64Image($base64Image) 
    {
        $decoder = new Base64ImageDecoder($base64Image, $allowedFormats = ['jpeg', 'png', 'jpg']);

        $decodedContent = $decoder->getDecodedContent();    //Dapatkan content gambar yg sudah terdecode
        $format = $decoder->getFormat();    //Mendapatkan informasi format gambar
        $image = Str::random(10).'.'.$format;   //Nama file gambar
        Storage::disk('public')->put($image, $decodedContent);  //Simpan di Storage
    
        return $image;
    }
}
