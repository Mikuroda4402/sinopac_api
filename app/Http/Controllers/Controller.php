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
            'shop_no',
            'a1',
            'a2',
            'b1',
            'b2');
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
        return $message;
    }

}
