<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Sinopac extends Controller
{
    public $shop_no;
    private $key_a1;
    private $key_a2;
    private $key_b1;
    private $key_b2;

    public function __construct(string $shop_no, string $key_a1, string $key_a2, string $key_b1, string $key_b2)
    {
        $this->shop_no = $shop_no;
        $this->key_a1 = $key_a1;
        $this->key_a2 = $key_a2;
        $this->key_b1 = $key_b1;
        $this->key_b2 = $key_b2;
    }

    public function callApi($url, $post_data)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HEADER => 0,
            CURLOPT_NOBODY => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => ["Content-type: application/json; charset=utf-8"],
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSLVERSION => 6
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

    public function getNonce()
    {
        $url = 'https://sandbox.sinopac.com/QPay.WebAPI/api/Nonce';
        $result = $this->callApi($url, ['ShopNo' => $this->shop_no])['Nonce'] | '';
        if (!$result) {
            throw new \Exception('Get Nonce failure');
        }
        return $result;
    }

    public function calculateIv($nonce): string
    {
        $hash = hash('SHA256', $nonce);
        return strtoupper(substr($hash, 48));
    }

    public function calcHashId(): string
    {
        $a = $b = '';
        $length = strlen($this->key_a1);
        for ($i = 0; $i < $length; $i += 4) {
            $part_of_a = dechex(hexdec(substr($this->key_a1, $i, 4)) ^ hexdec(substr($this->key_a2, $i, 4)));
            $part_of_b = dechex(hexdec(substr($this->key_b1, $i, 4)) ^ hexdec(substr($this->key_b2, $i, 4)));
            $a .= str_pad($part_of_a, 4, "0", STR_PAD_LEFT);
            $b .= str_pad($part_of_b, 4, "0", STR_PAD_LEFT);
        }
        return strtoupper($a . $b);
    }

    public function generateSign($data, $nonce, $hash_id): string
    {
        $keys = array_keys($data);
        foreach ($keys as $key) {
            $value = array_shift($data);
            if (gettype($value) === 'array' || trim($value) === '') {
                continue;
            }

            $upper_key = strtoupper($key);
            $data[$upper_key] = "$key=$value";
        }

        ksort($data);
        $body = implode('&', $data) . $nonce . $hash_id;
        $result = strtoupper(hash('sha256', $body));
        return $result;
    }

    public function encryptMessage($data, $key, $iv): string
    {
        $data = json_encode(array_filter((array)($data)));

        $padding = 16 - (strlen($data) % 16);
        $data .= str_repeat(chr($padding), $padding);
        $encrypt = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

        return strtoupper(bin2hex($encrypt));
    }

    public function decryptMessage($data, $key, $iv)
    {
        $encrypt = hex2bin($data);
        $decrypt = openssl_decrypt($encrypt, 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        $padding = ord($decrypt[strlen($decrypt) - 1]);

        $result = substr($decrypt, 0, -$padding);
        return json_decode($result, true);
    }
}
