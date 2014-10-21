<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-10-15
 * Time: 下午4:58
 */

function validation_mobile($mobile){
    if($mobile){
        $result = preg_match('/^\d{6,20}$/',$mobile);
        return $result;
    }
    return false;

}
function validation_phone($mobile){
    if($mobile){
        $result = preg_match('/^\d+-\d+(?:-\d+)?$/',$mobile);
        return $result;
    }
    return false;

}

function connection_phone($phoneSection,$phoneCode,$phoneExt){
    if(empty($phoneSection)&& empty($phoneCode) && empty($phoneExt) ){
        return false;
    }
    $chars = "-";
    $c = array();
    $phoneSection && $c[0] = $phoneSection;
    $phoneCode && $c[1] = $phoneCode;
    $phoneExt && $c[2] = $phoneExt;

    return implode($chars,$c);

}

function check_postcode($code){

    if(empty($code)) return false;

    $result = preg_match('/^[\da-zA-Z\-]{3,10}$/',$code);

    return $result;
}