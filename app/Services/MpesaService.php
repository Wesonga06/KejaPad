<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    protected $consumerKey;
    protected $consumerSecret;
    protected $shortCode;
    protected $passkey;
    protected $callbackUrl;
    protected $env;
    protected $baseUrl;

    public function __construct()
    {
        $this->consumerKey    = env('MPESA_CONSUMER_KEY');
        $this->consumerSecret = env('MPESA_CONSUMER_SECRET');
        $this->shortCode      = env('MPESA_SHORTCODE');
        $this->passkey        = env('MPESA_PASSKEY');
        $this->callbackUrl    = env('MPESA_CALLBACK_URL');
        $this->env            = env('MPESA_ENV', 'sandbox');
        $this->baseUrl        = $this->env === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    // Get OAuth access token
    public function getAccessToken()
    {
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
        ])->get($this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials');

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        Log::error('M-Pesa Access Token Error', $response->json());
        return null;
    }

    // Initiate STK Push
    public function stkPush($phone, $amount, $accountRef, $description = 'Rent Payment')
    {
        $token     = $this->getAccessToken();
        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($this->shortCode . $this->passkey . $timestamp);

        // Format phone: 0712345678 → 254712345678
        $phone = $this->formatPhone($phone);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ])->post($this->baseUrl . '/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $this->shortCode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int) $amount,
            'PartyA'            => $phone,
            'PartyB'            => $this->shortCode,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $this->callbackUrl,
            'AccountReference'  => $accountRef,
            'TransactionDesc'   => $description,
        ]);

        Log::info('STK Push Response', $response->json());
        return $response->json();
    }

    // Format phone number to 254 format
    public function formatPhone($phone)
    {
        $phone = preg_replace('/\s+/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '254' . substr($phone, 1);
        }

        if (str_starts_with($phone, '+254')) {
            return substr($phone, 1);
        }

        return $phone;
    }
}