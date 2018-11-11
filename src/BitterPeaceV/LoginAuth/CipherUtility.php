<?php

namespace BitterPeaceV\LoginAuth;

abstract class CipherUtility
{
    const METHOD = "AES-256-CBC";

    /**
     * 暗号化
     * 
     * @param string $data データ
     * @param string $pass パスワード
     * 
     * @return array [0 => 暗号化された文字列, 初期化ベクトル]
     */
    public static function encrypt($data, string $pass): array
    {
        $iv_size = \openssl_cipher_iv_length(self::METHOD);
        $iv = openssl_random_pseudo_bytes($iv_size);
        return [openssl_encrypt($data, self::METHOD, $pass, 0, $iv), $iv];
    }

    /**
     * 復号化
     * 
     * @param string $data データ
     * @param string $pass パスワード
     * 
     * @return string 復号された文字列
     */
    public static function decrypt($data, string $pass, string $iv): string
    {
        return openssl_decrypt($data, self::METHOD, $pass, 0, $iv);
    }
}
