<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\DB;

// use Illuminate\Support\Facades\DB;

class MpesaApi extends Controller
{
    public function generateToken()
    {
        $consumer_key = env('MPESA_CONSUMER_KEY');
        $consumer_secret = env('MPESA_CONSUMER_SECRET');

        $url = "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";

        $credentials = base64_encode("{$consumer_key}:{$consumer_secret}");

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic {$credentials}",
                    'Content-Type:application/json',
                ),
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
            ]
        );
        $curl_response = curl_exec($curl);
        $access_token = json_decode($curl_response);
        curl_close($curl);

        return $access_token->access_token;
    }

    private function makeHttp($url, $body)
    {
        $token = $this->generateToken();
        Log::info("Token: {$token}");

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_HEADER => false,
                CURLOPT_HTTPHEADER => [
                    'Content-Type:application/json',
                    "Authorization:Bearer {$token}",
                ],
            ]
        );
        $data_string = json_encode($body);
        curl_setopt_array(
            $curl,
             [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data_string,
            ]
        );
        $response = curl_exec($curl);
        return $response;
    }

    public function registerUrl()
    {

        $body = [
            'ShortCode' => env('MPESA_SHORTCODE'),
            'ResponseType' => 'Completed',
            'ConfirmationURL' => 'https://uat.tabor.ac.ke/finance/confirmation',
            'ValidationURL' => ''
        ];

        $url = "https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl";

        $result = $this->makeHttp($url, $body);

        return $result;
    }
    public function c2b(Request $request)
    {
        $url = 'https://api.safaricom.co.ke/mpesa/c2b/v1/simulate';

        $data = $request->all();

        $body = [
            'ShortCode' => $data['ShortCode'],
            'CommandID' => 'CustomerPayBillOnline',
            'Amount' => $data['Amount'],
            'Msisdn' => $data['Msisdn'],
            'BillRefNumber' => 'Test Payment',
        ];
        $response = $this->makeHttp($url, $body);

        return $response;
    }

    //FUNCTION TO TRIGGER STKPUSH ON PHONE
    public function triggerStk(Request $request)
    {
        $amount = $request->amount;
        $phone = $request->phone;
        if (substr($phone, 0, 1) == "0") {
            $phone = "254" . substr($phone, -9);
        }
        $TransactionDesc = rand(1, 3);
        $MERCHANT_ID = env('MPESA_SHORTCODE');
        $PASSKEY = env('MPESA_PASSKEY');
        $TIMESTAMP = date("YmdHis", time());
        $password = base64_encode("{$MERCHANT_ID}{$PASSKEY}{$TIMESTAMP}");

        $body = [
            'BusinessShortCode' => $MERCHANT_ID,
            'Password' => $password,
            'Timestamp' => $TIMESTAMP,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $MERCHANT_ID,
            'PhoneNumber' => $phone,
            'CallBackURL' => 'https://uat.tabor.ac.ke/finance/stk_response',
            'AccountReference' => $TransactionDesc,
            'TransactionDesc' => $TransactionDesc
        ];

        $url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

        $response = $this->makeHttp($url, $body);

        $response_array = json_decode($response);

        if ($response_array->{'ResponseCode'} == 0) {

            echo json_encode(array("status" => "success", "message" => "Processing... Check your phone to enter pin", ''));
        } else {
            echo json_encode(array("status" => "failure", "message" => "Payment request failed, please try again"));
        }
    }

    //FUNCTION TO RECEIVE RESPONSES FOR BOTH STK PUSH AND C2B TRANSACTIONS
    public function c2bConfirmation(Request $request)
    {
        DB::beginTransaction();
        try {
            $response = $request->all();
            Log::info('C2B Confirmation: ' . request()->ip());
            Log::info($response);

            $mpesa_transaction_id = $response['TransID'];
            $date_time = $response['TransTime'];
            $amount = $response['TransAmount'];
            $account = strtoupper(preg_replace('/\s+/', '', $response['BillRefNumber']));
            $phone = $response['MSISDN'];
            $name = ($response['FirstName'] ?? '') . ' ' . ($response['MiddleName'] ?? '') . ' ' . ($response['LastName'] ?? '');
            $payer = preg_replace('!\s+!', ' ', ucwords(strtolower($name)));

            //save the transaction in database
            if (!empty($mpesa_transaction_id)) {

                $transaction = Payment::create([
                    'payer' => $payer,
                    'transaction_id' => $mpesa_transaction_id,
                    'account_refference' => $account,
                    'amount' => $amount,
                    'phone' => $phone,
                    'paid_at' => $date_time,
                ]);

            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::info($exception);
        }
    }

    //VALIDATION END POINT
    public function c2bValidation(Request $request)
    {
        Log::info("validation hit");
    }

    //STK CALLBACK FUNCTION. THE RESPONSE IS IGNORED HERE AND SAVED IN C2B CONFIRMATION ENDPOINT
    public function stkCallbackAction(Request $request)
    {
        $data = $request->all();

        //log the responses
        Log::info("stk callback endpoint hit");
        Log::info($data);


        //access the array data response
        $array = $data['Body']['stkCallback']['CallbackMetadata']['Item'];
        $array1 = $data['Body']['stkCallback'];

        $MerchantRequestID = $array1['MerchantRequestID'];
        $CheckoutRequestID = $array1['CheckoutRequestID'];
        $ResultCode = $array1['ResultCode'];

        // success stk
        if ($ResultCode == 0) {
        } else {
        }
    }

    public function confirmPayment(Request $request)
    {
        $trans_id = $request->trans_id;

        $result = Payment::where('mpesa_transaction_id', $trans_id)
            ->first();

        if (empty($result->id)) {
            return json_encode(
                array(
                    'status' => 'failure',
                    'message' => 'Payment not yet received! Try again.'
                )
            );
        } else {
            return json_encode(
                array(
                    'status' => 'success',
                    'message' => 'Payment of Ksh ' . $result->amount . ' Received Successfully!',
                    'amount' => $result->amount
                )
            );
        }
    }

}
