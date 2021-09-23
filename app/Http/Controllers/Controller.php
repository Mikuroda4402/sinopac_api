<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function receive_msg(Request $request)
    {
        Log::alert('Receive message Content', $request->all());
        $PayToken = $request->get('PayToken');

        if (!$PayToken) {
            Log::alert('PayToken Not exist');
            return ['Status' => 'F'];
        }

        $sinopac = $this->initSinopac();
        $data = $sinopac->requestDataset('OrderPayQuery', $request->all());
        $message = $sinopac->callApi('https://apisbx.sinopac.com/funBIZ/QPay.WebAPI/api/Order', $data);

        Log::info('Reply message', (array) $message);
        return ['Status' => 'S'];
    }

    public function requestDataset(string $service_name, array $dataset)
    {
        $nonce      = $this->getNonce();
        $hash_id    = $this->calcHashId();
        $iv         = $this->calculateIv($nonce);
        $sign       = $this->generateSign($dataset, $nonce, $hash_id);
        $message    = $this->encryptMessage($dataset, $hash_id, $iv);

        return [
            'Version'       => '1.0.0',
            'ShopNo'        => $this->shop_no,
            'APIService'    => $service_name,
            'Nonce'         => $nonce,
            'Sign'          => $sign,
            'Message'       => $message
        ];
    }

    private function initSinopac()
    {
        return new Sinopac(
            'NA0249_001',
            '86D50DEF3EB7400E',
            '01FD27C09E5549E5',
            '9E004965F4244953',
            '7FB3385F414E4F91');
    }

    public function create_order(Request $request)
    {
        $sinopac = $this->initSinopac();
        $data = [
            'ShopNo'        => $sinopac->shop_no,
            'OrderNo'       => date('YmdHis'),
            'Amount'        => random_int(4000, 10000),
            'CurrencyID'    => 'TWD',
            'PrdtName'      => '大河',
            'ReturnURL'     => 'http://10.11.22.113:8803/QPay.ApiClient-Sandbox/Store/Return',
            'BackendURL'    => 'https://sandbox.sinopac.com/funBIZ.ApiClient/AutoPush/PushSuccess',
            'PayType'       => 'A',
            'ATMParam'      => [
                'ExpireDate' => date('Ymd', time() + 604800),
            ],
        ];

        $data = $sinopac->requestDataset('OrderCreate', $data);
        $message = $sinopac->callApi('https://apisbx.sinopac.com/funBIZ/QPay.WebAPI/api/Order', $data);

        $reply_nonce = $message['Nonce'] | '';
        if (!$reply_nonce) {
            $msg = 'Reply message haven\'t Nonce';
            Log::error($msg , $message);
            throw new \HttpResponseException($msg);
        }

        // 1. nonce 計算 iv
        $iv = $sinopac->calculateIv($reply_nonce);
        // 2. 計算 hash_id (AES key)
        $hash_id = $sinopac->calcHashId();
        // 3. message 解密
        $decrypt_message = $sinopac->decryptMessage($message['Message'], $hash_id, $iv);
        // 4. 驗證 sign
        $sign = $sinopac->generateSign($decrypt_message, $reply_nonce, $hash_id);

        if (!($sign === $message['Sign'])) {
            throw new \HttpResponseException('驗證錯誤，內文簽章不同');
        }

        // 這裡的 – 是 \xE2  不是 \x2D
        $description = explode(' – ', $decrypt_message['Description']);
        if ($description[0] !== 'S0000') {
            Log::alert('訂單未建立成功', $decrypt_message['Description']);
            throw new \HttpResponseException("訂單未建立成功");
        }

        return [
            'Reply_Message' => $message,
            'Decrypt_content' => $decrypt_message
        ];
    }

}
