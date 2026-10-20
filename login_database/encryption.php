<?php
class AES
{
    //设置AES秘钥
    private static $aes_key = 'bUYJ3nTV6VBasdJF'; //此处填写前后端共同约定的秘钥

    /**
     * 加密
     * @param string $str    要加密的数据
     * @return bool|string   加密后的数据
     */
    static public function encrypt($str) {

        $data = openssl_encrypt($str, 'AES-128-ECB', self::$aes_key, OPENSSL_RAW_DATA);
        $data = base64_encode($data);

        return $data;
    }

    /**
     * 解密
     * @param string $str    要解密的数据
     * @return string        解密后的数据
     */
    static public function decrypt($str) {

        $decrypted = openssl_decrypt(base64_decode($str), 'AES-128-ECB', self::$aes_key, OPENSSL_RAW_DATA);
        return $decrypted;
    }


}

?>
