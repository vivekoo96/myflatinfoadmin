<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use \Auth;

class TruthScreenController extends Controller
{
    const API_URL = 'https://www.truthscreen.com/v1/apicall/nid/aadhar_get_otp';
    const CIPHER_METHOD = 'aes-128-cbc';
    const CIPHER_KEY_LEN = 16;

    /**
     * Generate the encryption key from the token using SHA-512 hashing.
     */
    private function generateEncryptionKey($token)
    {
        $hashedKey = hash('sha512', $token, false);
        return substr($hashedKey, 0, self::CIPHER_KEY_LEN);
    }

    /**
     * Encrypt the input data using AES-128 encryption.
     */
    private function encryptData($key, $data)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER_METHOD));
        $encryptedData = openssl_encrypt(
            $data,
            self::CIPHER_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($encryptedData) . ':' . base64_encode($iv);
    }

    /**
     * Decrypt the response data using AES-128 decryption.
     */
    private function decryptData($key, $encryptedPayload)
    {
        [$encryptedData, $iv] = explode(':', $encryptedPayload);

        return openssl_decrypt(
            base64_decode($encryptedData),
            self::CIPHER_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            base64_decode($iv)
        );
    }

    /**
     * Handle the TruthScreen Search API request.
     */
    public function search(Request $request)
    {
        // Replace these with your credentials or retrieve from the .env file
        $username = env('AUTHBRIDGE_USERNAME', 'production@analogueitsolutions.com');
        $token = env('AUTHBRIDGE_TOKEN', 'India@2608');

        // Step 1: Generate the encryption key
        $encryptionKey = $this->generateEncryptionKey($token);

        // Step 2: Prepare the input JSON structure
        $input = [
            'transID' => $request->transID, // Default or provided
            'docType' => $request->docType,         // Mandatory field
            'aadharNo' => $request->docNumber,        // Mandatory field
        ];

        if (!$input['aadharNo']) {
            return response()->json([
                'status' => 'error',
                'message' => 'docNumber is required.',
            ], 400);
        }

        $inputJson = json_encode($input);

        // Step 3: Encrypt the input JSON
        $encryptedData = $this->encryptData($encryptionKey, $inputJson);

        // Step 4: Prepare and send the HTTP request
        $payload = ['requestData' => $encryptedData];
        $response = Http::withHeaders([
            'username' => $username,
            'Content-Type' => 'application/json',
        ])->post(self::API_URL, $payload);

        // Step 5: Handle the API response
        if ($response->successful()) {
            $responseData = $response->json('responseData');
            $decryptedData = $this->decryptData($encryptionKey, $responseData);

            return response()->json([
                'status' => 'success',
                'data' => json_decode($decryptedData, true),
            ],200);
        }
        
        $responseData = $response->json('responseData');
        
        if($responseData){
            $decryptedData = $this->decryptData($encryptionKey, $responseData);
            return response()->json([
                'status' => 'error',
                'data' => json_decode($decryptedData, true),
            ],$response->status());
        }

        return response()->json([
            'status' => 'error',
            'message' => $response->body(),
        ], $response->status());
    }
    
    public function get_aadhar_otp(Request $request)
    {
        // Replace these with your credentials or retrieve from the .env file
        $username = env('AUTHBRIDGE_USERNAME', 'production@analogueitsolutions.com');
        $token = env('AUTHBRIDGE_TOKEN', 'India@2608');

        // Step 1: Generate the encryption key
        $encryptionKey = $this->generateEncryptionKey($token);

        // Step 2: Prepare the input JSON structure
        $input = [
            'transID' => $this->generateUniqueTransId(), // Default or provided
            'docType' => 211,         // Mandatory field
            'aadharNo' => $request->aadhar_no,        // Mandatory field
        ];

        if (!$input['aadharNo']) {
            return response()->json([
                'status' => 'error',
                'message' => 'aadharNo is required.',
            ], 400);
        }

        $inputJson = json_encode($input);

        // Step 3: Encrypt the input JSON
        $encryptedData = $this->encryptData($encryptionKey, $inputJson);

        // Step 4: Prepare and send the HTTP request
        $payload = ['requestData' => $encryptedData];
        $response = Http::withHeaders([
            'username' => $username,
            'Content-Type' => 'application/json',
        ])->post('https://www.truthscreen.com/v1/apicall/nid/aadhar_get_otp', $payload);

        // Step 5: Handle the API response
        if ($response->successful()) {
            $responseData = $response->json('responseData');
            $decryptedData = $this->decryptData($encryptionKey, $responseData);
            
            $user = Auth::User();
            $user->aadhar_no = $request->aadhar_no;
            $user->aadhar_status = 'Pending';
            $user->save();

            return response()->json([
                'status' => 'success',
                'data' => json_decode($decryptedData, true),
            ],200);
        }
        
        $responseData = $response->json('responseData');
        
        if($responseData){
            $decryptedData = $this->decryptData($encryptionKey, $responseData);
            return response()->json([
                'status' => 'error',
                'data' => json_decode($decryptedData, true),
            ],$response->status());
        }

        return response()->json([
            'status' => 'error',
            'message' => $response->body(),
        ], $response->status());
    }
    
    public function verify_aadhar_otp(Request $request)
    {
        // Replace these with your credentials or retrieve from the .env file
        $username = env('AUTHBRIDGE_USERNAME', 'production@analogueitsolutions.com');
        $token = env('AUTHBRIDGE_TOKEN', 'India@2608');

        // Step 1: Generate the encryption key
        $encryptionKey = $this->generateEncryptionKey($token);

        // Step 2: Prepare the input JSON structure
        $input = [
            'transId' => $request->tsTransId,
            'otp' => (int)$request->otp,
        ];

        if (!$input['otp']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Otp is required.',
            ], 400);
        }
        
        if (!$input['transId']) {
            return response()->json([
                'status' => 'error',
                'message' => 'tsTransId is required.',
            ], 400);
        }

        $inputJson = json_encode($input);

        // Step 3: Encrypt the input JSON
        $encryptedData = $this->encryptData($encryptionKey, $inputJson);

        // Step 4: Prepare and send the HTTP request
        $payload = ['requestData' => $encryptedData];
        $response = Http::withHeaders([
            'username' => $username,
            'Content-Type' => 'application/json',
        ])->post('https://www.truthscreen.com/v1/apicall/nid/aadhar_submit_otp', $payload);

        // Step 5: Handle the API response
        if ($response->successful()) {
            $responseData = $response->json('responseData');
            $decryptedData = $this->decryptData($encryptionKey, $responseData);
            
            $user = Auth::User();
            $user->aadhar_details = json_decode($decryptedData, true);
            $user->aadhar_status = 'Verified';
            $user->save();

            return response()->json([
                'status' => 'success',
                'data' => json_decode($decryptedData, true),
            ],200);
        }
        
        $responseData = $response->json('responseData');
        
        if($responseData){
            $decryptedData = $this->decryptData($encryptionKey, $responseData);
            return response()->json([
                'status' => 'error',
                'data' => json_decode($decryptedData, true),
            ],$response->status());
        }

        return response()->json([
            'status' => 'errorss',
            'message' => $response->body(),
        ], $response->status());
    }
    
    public function verify_pan_number(Request $request)
    {
        // Replace these with your credentials or retrieve from the .env file
        $username = env('AUTHBRIDGE_USERNAME', 'production@analogueitsolutions.com');
        $token = env('AUTHBRIDGE_TOKEN', 'India@2608');

        // Step 1: Generate the encryption key
        $encryptionKey = $this->generateEncryptionKey($token);

        // Step 2: Prepare the input JSON structure
        $input = [
            'transId' => $this->generateUniqueTransId(),
            'docType' => 549,         // Mandatory field
            'docNumber' => $request->pan_no,
            'name' => $request->name,
            'dob' => $request->dob,
        ];

        if (!$input['docNumber']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pan number is required.',
            ], 400);
        }

        $inputJson = json_encode($input);

        // Step 3: Encrypt the input JSON
        $encryptedData = $this->encryptData($encryptionKey, $inputJson);

        // Step 4: Prepare and send the HTTP request
        $payload = ['requestData' => $encryptedData];
        $response = Http::withHeaders([
            'username' => $username,
            'Content-Type' => 'application/json',
        ])->post('https://www.truthscreen.com/v1/apicall/nid/pan_online_verification', $payload);

        // Step 5: Handle the API response
        if ($response->successful()) {
            $responseData = $response->json('responseData');
            $decryptedData = $this->decryptData($encryptionKey, $responseData);
            
            $user = Auth::User();
            $user->pan_details = json_decode($decryptedData, true);
            $user->pan_status = 'Verified';
            $user->save();

            return response()->json([
                'status' => 'success',
                'data' => json_decode($decryptedData, true),
            ],200);
        }
        
        $responseData = $response->json('responseData');
        
        if($responseData){
            $decryptedData = $this->decryptData($encryptionKey, $responseData);
            return response()->json([
                'status' => 'error',
                'data' => json_decode($decryptedData, true),
            ],$response->status());
        }

        return response()->json([
            'status' => 'errorss',
            'message' => $response->body(),
        ], $response->status());
    }
    
    public function update_bank_details(Request $request)
    {
        $rules = [
            'account_no' => 'required|numeric',
            'ifsc_code' => 'required|string',
        ];
        $validation = \Validator::make( $request->all(), $rules );
        $error = $validation->errors()->first();
        if($error){
            return response()->json([
                'error' => $error
            ],422);
        }
        
        // Replace these with your credentials or retrieve from the .env file
        $username = env('AUTHBRIDGE_USERNAME', 'production@analogueitsolutions.com');
        $token = env('AUTHBRIDGE_TOKEN', 'India@2608');

        // Step 1: Generate the encryption key
        $encryptionKey = $this->generateEncryptionKey($token);

        // Step 2: Prepare the input JSON structure
        $input = [
            'transId' => $this->generateUniqueTransId(),
            'docType' => 430,         // Mandatory field
            'accountNumber' => $request->account_no,
            'ifscCode' => $request->ifsc_code,
        ];

        $inputJson = json_encode($input);

        // Step 3: Encrypt the input JSON
        $encryptedData = $this->encryptData($encryptionKey, $inputJson);

        // Step 4: Prepare and send the HTTP request
        $payload = ['requestData' => $encryptedData];
        $response = Http::withHeaders([
            'username' => $username,
            'Content-Type' => 'application/json',
        ])->post('https://www.truthscreen.com/BankIfscVerification/idsearch', $payload);

        // Step 5: Handle the API response
        if ($response->successful()) {
            $responseData = $response->json('responseData');
            $decryptedData = $this->decryptData($encryptionKey, $responseData);
            
            $user = Auth::User();
            $user->bank_details = json_decode($decryptedData, true);
            $user->bank_status = 'Verified';
            
            $user->profile_status = 'Verified';
            $user->document_status = 'Verified';
            $user->save();

            return response()->json([
                'status' => 'success',
                'data' => json_decode($decryptedData, true),
            ],200);
        }
        
        $responseData = $response->json('responseData');
        
        if($responseData){
            $decryptedData = $this->decryptData($encryptionKey, $responseData);
            return response()->json([
                'status' => 'error',
                'data' => json_decode($decryptedData, true),
            ],$response->status());
        }

        return response()->json([
            'status' => 'errorss',
            'message' => $response->body(),
        ], $response->status());
    }
    
    private function generateUniqueTransId()
    {
        return uniqid('trans_', true); // Prefix with 'trans_' and include more entropy
    }
}
