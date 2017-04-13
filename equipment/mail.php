<?php
/**
 * メール モジュール
 *
 * @author   Hideshige Sawada
 * @version  1.0.4.0
 * @package  equipment
 *
 */

class mail {

  /**
   * メール送信
   * @param string $to 送信先メールアドレス
   * @param string $subject 件名
   * @param string $body 本文
   * @return true
   */
  public static function send_mail( $to, $subject, $body ) {
    $to = str_replace( array ( "\n", "\r" ), '', $to );
    mb_internal_encoding( 'ISO-2022-JP' );
    $from_name = mb_encode_mimeheader( mb_convert_encoding( FROM_NAME, 'ISO-2022-JP', DEFAULT_CHARSET ), 'ISO-2022-JP', 'B' );
    mb_internal_encoding( DEFAULT_CHARSET );

    $subject = citadel::h_decode( $subject );
    $subject = mb_convert_encoding( $subject, 'ISO-2022-JP', DEFAULT_CHARSET );
    $subject = '=?iso-2022-jp?B?' . base64_encode( $subject ) . '?=';
    $body = citadel::h_decode( $body );
    $body = mb_convert_encoding( $body, 'ISO-2022-JP', DEFAULT_CHARSET );
    $headers = "MIME-Version: 1.0 \n";
    $headers .= sprintf( "From: %s<%s> \n", $from_name, FROM_EMAIL );
    $headers .= sprintf( "Reply-To: %s<%s> \n", $from_name, FROM_EMAIL );
    $headers .= "Content-Type: text/plain;charset=ISO-2022-JP \n";
    $f = sprintf( '-f%s', EMAIL_RETURN_PATH );

    $res = mail( $to, $subject, $body, $headers, $f );
    if ( $res === false ) throw new Exception( 'send mail error' );
    return true;
  }
}

