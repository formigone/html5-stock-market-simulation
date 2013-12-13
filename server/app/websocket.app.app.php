<?php

namespace WebSocket\App;

abstract class App
{
  protected static $instance = array();

  protected function __construct() {}

  final public static function getInstance()
  {
    $calledClassName = get_called_class();

    if( !isset(self::$instance[$calledClassName]) )
    {
      self::$instance[$calledClassName] = new $calledClassName();
    }

    return self::$instance[$calledClassName];
  }

  public function onConnect( $pConnection ) {}
  public function onDisconnect( $pConnection ) {}
  public function onTick( ) {}
  public function onData( $pData, $pClient ) {}
}