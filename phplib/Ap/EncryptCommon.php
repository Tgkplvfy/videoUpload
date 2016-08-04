<?php
/* 公共加密类
 * @author huangby
 * @date 2016-05-11
 *
 */
class Ap_EncryptCommon {

    protected $str = '';
    protected $strlen = 0;
    protected $encrypt = array("q","h","m","k");
    protected $randkey = array();

    const APP_ENCRYPT_BASE = 'mkw!@#-+';
    const APP_ENCRYPT_AES  = 'mukewang';

    # m3u8Encrypt 加密算法
    public function m3u8Encrypt($str) {
        $this->str=$str;
        $this->strlen=strlen($this->str);
        #记录算法顺序
        $table = array();
        $table[] = $this->encrypt[array_rand($this->encrypt,1)];
        $table[] = $this->encrypt[array_rand($this->encrypt,1)];
        $table[] = $this->encrypt[array_rand($this->encrypt,1)];
        $table[] = $this->encrypt[array_rand($this->encrypt,1)];
        // $table[] = "m";
        // $table[] = "q";
        // $table[] = "h";
        // $table[] = "k";
        foreach($table as $v){
            switch ($v) {
                case 'q':
                    $key=$this->getkey(12);
                    $keylen=strlen($key);
                    $this->randkey[]=$key;
                    $this->str=$this->yh($this->str,$this->strlen,$key,$keylen);
                    break;
                case 'h':
                    $this->str=$this->lx($this->str,$this->strlen);
                    break;
                case 'm':
                    $this->str=$this->cr($this->str,$this->strlen);
                    break;
                case 'k':
                    $key=$this->getkey(12);
                    $keylen=strlen($key);
                    $this->randkey[]=$key;
                    $this->str=$this->yhlx($this->str,$this->strlen,$key,$keylen);
                    break;
                default:
                    break;
            }
        }
        $return_result=array();
        #$return_result['info'] = $this->str;
        $this->str=base64_encode($this->str);
        #$return_result['old_info']=$this->str;
        $this->strlen=strlen($this->str);
        #$return_result['str_length']=$this->strlen;
        #$return_result['str_len']=strlen($this->str);
        #$return_result['encrypt_table']=$table;
        #print_r($table);
        #$return_result['key_table']=$this->randkey;
        #print_r($this->randkey);
        foreach($this->randkey as $v){
            $this->str.=$v;
            $this->strlen+=strlen($v);
        }
        #echo $this->str;
        $str_end = substr($this->str,-4);
        $str_end = str_split($str_end);
        #print_r($str_end);
        $i = 0;
        foreach($str_end as $val){

            $asc = ord($val);
            $m = $asc%4;
            switch($m){
                case 0:
                    $this->str=$this->str_insert($this->str,1,$table[$i]);
                    break;
                case 1:
                    $this->str=$this->str_insert($this->str,2,$table[$i]);
                    break;
                case 2:
                    $this->str=$this->str_insert($this->str,3,$table[$i]);
                    break;
                case 3:
                    $this->str=$this->str_insert($this->str,4,$table[$i]);
                    break;
                default:
                    $this->str=$this->str_insert($this->str,1,$table[$i]);
                    break;
            }
            $i++;

        }
        #echo $this->str;
        $this->strlen=$this->strlen+4;
        #$return_result['str_length']=$this->strlen;
        $return_result['info']=$this->str;

        return $return_result;

    }


    # 针对App的M3U8内容加密算法
    public function m3u8AppEncrypt ($str) 
    {
        if (empty($str)) return FALSE;

        $newkey = 0x10;

        $base_len = strlen(self::APP_ENCRYPT_BASE);

        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $newkey .= $str[$i] ^ self::APP_ENCRYPT_BASE[$i % $base_len];
        }

        for ($i = 0; $i < $len; $i++) {
            $newkey .= $newkey[$i + 1] ^ self::APP_ENCRYPT_AES[$i % $base_len];
        }

        return $newkey;
    }

    # yhlx
    public function yhlx($str,$strlen,$key,$keylen){
        for ($i = 0 ; $i < $strlen; $i++) {
            $str[$i] = $str[$i] ^ $key[$i%$keylen];
        }
        for ($i = 0; $i < $strlen; $i++) {
            $change = ord($str[$i]) % 5;
            if ($change != 0 && $change != 1 && ($i + $change) < $strlen) {
                $tmp = $str[$i + 1];
                $j = $i+2;
                $str[$i + 1] = $str[$i+$change];
                $str[$change + $i] = $tmp;
                $i = $i + $change + 1;
                if ($i-2 > $j) {
                    for (;$j<$i-2;$j++) {
                        $str[$j] = $str[$j] ^ $key[$j%strlen($key)];
                    }
                }
            }
        }

        return $str;
    }

    # cr
    public function cr($str,$strlen){
        $output=array();
        for($i=0;$i<$strlen;$i++){

            array_push($output, $str[$i]);
            $m = ord($str[$i])%2;
            if( $m>0){

                array_push($output, "T");

            }

        }
        $this->strlen=count($output);
        return implode("", $output);
    }

    # lx
    public function lx($str,$strlen){
        for($i=0;$i<$strlen;$i++){

            $m=ord($str[$i])%3;
            if($m>0){
                if(isset($str[$i+$m])){
                    $tmp=$str[$i+$m];
                    $str[$i+$m]=$str[$i+1];
                    $str[$i+1]=$tmp;
                }
                $i=$i+$m+1;
            }


        }
        return $str;
    }

    # yh
    public function yh($str,$strlen,$key,$keylen){
        $crytxt = '';
        for($i=0;$i<$strlen;$i++)
        {
            $crytxt .= $str[$i] ^ $key[$i%$keylen];
        }
        return $crytxt;
    }

    # getkey
    public function getkey($length = 8) {
        // 密码字符集，可任意添加你需要的字符
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = "";
        for ( $i = 0; $i < $length; $i++ ){
            $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $password;
    }

    # str_insert
    function str_insert($str, $i, $substr) {
        $startstr="";
        $laststr="";
        for($j=0; $j<$i; $j++){
            $startstr .= $str[$j];
        }
        for ($j=$i; $j<strlen($str); $j++){
            $laststr .= $str[$j];
        }
        $str = ($startstr . $substr . $laststr);
        return $str;
    }

    # xor_enc
    public function xor_enc($str,$key)
    {
        $crytxt = '';
        $keylen = strlen($key);
        for($i=0;$i<strlen($str);$i++)
        {
            #$k = $i%$keylen;
            $crytxt .= $str[$i] ^ $key[$i%$keylen];
        }
        return $crytxt;
    }

}

?>
