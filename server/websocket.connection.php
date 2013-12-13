<?php

namespace WebSocket;

class Connection
{
  private $server;
  private $socket;
  private $handshaked;
  private $app;

  public function __construct($pServer, $pSocket)
  {
    $this->server = $pServer;
    $this->socket = $pSocket;

    $this->log( "Server connected" );
  }

  private function handshake($pData)
  {
    $this->log( "Handshaking" );

    $lines = preg_split( "/\r\n/", $pData );

    if( count($lines) && preg_match( "/<policy-file-request.*>/", $lines[0]) )
    {
      $this->log( "Flash policy file request" );
      $this->serveFlashPolicy();
      return false;
    }

    if( !preg_match( "/\AGET (\S+) HTTP\/1.1\z/", $lines[0], $matches))
    {
      $this->log( "Invalid request: ".$lines[0] );
      socket_close( $this->socket );
      return false;
    }

    $path = $matches[1];

    // parse all Sec-WebSocke headers
    foreach( $lines as $line )
    {
      $line = chop( $line );

      if( preg_match( "/\A(\S+): (.*)\z/", $line, $matches))
      {
        $headers[ $matches[1] ] = $matches[2];
      }
    }

    $key3 = "";
    preg_match( "/\r\n(.*?)\$/", $pData, $match) && $key3 = $match[1];

    $origin = $headers['Origin'];
    $host = $headers['Host'];

    $this->app = $this->server->getApp( substr($path, 1) );

    if( !$this->app)
    {
      $this->log( "Error: Invalid app\n". $path);
      socket_close( $this->socket );
      return false;
    }

    // create handshake response

    $status = "101 Web Socket Protocol Handshake";
    if( array_key_exists( "Sec-WebSocket-Key1", $headers) )
    {
      // Draft 76! Not implemented in FF or IE (any version, as of 7/2011)

      $def_header = array(
        "Sec-WebSocket-Origin" => $origin,
        "Sec-WebSocket-Location" => "ws://{$host}{$path}"
      );

      $digest = $this->securityDigest( $headers["Sec-WebSocket-Key1"], $headers["Sec-WebSocket-Key2"], $key3 );
    }
    else
    {
      // Draft 75
      $def_header = array(
        "WebSocket-Origin" => $origin,
        "WebSocket-Location" => "ws://{$host}{$path}"
      );

      $digest = "";
    }

    $header_str = "";
    foreach( $def_header as $key => $value )
    {
      $header_str .= $key . ": " . $value . "\r\n";
    }

    $upgrade = "HTTP/1.1 {$status}\r\n".
               "Upgrade: WebSocket\r\n".
               "Connection: Upgrade\r\n".
               "{$header_str}\r\n".
               "$digest";

    socket_write( $this->socket, $upgrade, strlen($upgrade) );

    $this->handshaked = true;
    $this->log( "Handshake confirmed" );

    $this->app->onConnect( $this );

    return true;
  }

  public function onData( $pData )
  {
    if( $this->handshaked )
    {
      $this->handle( $pData );
    }
    else
    {
      $this->handshake( $pData );
    }
  }

  private function handle( $pData )
  {
    $chunks = explode( chr(255), $pData );

    for( $i = 0; $i < count($chunks) - 1; $i++)
    {
      $chunk = $chunks[$i];

      if( substr($chunk, 0, 1) != chr(0) )
      {
        $this->log( "Error: Data sent in wrong format" );
        socket_close( $this->socket );
        return false;
      }

      $this->app->onData( substr($chunk, 1), $this);
    }

    return true;
  }

  private function serveFlashPolicy()
  {
    $policy = '<?xml version="1.0"?>'. "\n".
              '<!DOCTYPE cross-domain-policy SYSTEM "http://www.macromedia.com/xml/dtds/cross-domain-policy.dtd">'. "\n".
              '<cross-domain-policy>'. "\n".
              '  <allow-access-from domain="*" to-ports="*"/>'. "\n".
              '</cross-domain-policy>'. "\n";
    socket_write( $this->socket, $policy, strlen($policy) );
    socket_close( $this->socket );
  }

  public function send( $pData )
  {
    if( !@socket_write( $this->socket, chr(0). $pData. chr(255), strlen($pData) + 2) )
    {
      @socket_close( $this->socket );
      $this->socket = false;
    }
  }

  public function onDisconnect()
  {
    $this->log( "Client disconnected\n" );

    if( $this->app )
    {
      $this->app->onDisconnect( $this );
    }

    socket_close( $this->socket );
  }

  private function securityDigest( $key1, $key2, $key3 )
  {
    return md5(
      pack( "N", $this->keyToBytes($key1)).
      pack( "N", $this->keyToBytes($key2)).
      $key3, true
    );
  }

  private function keyToBytes( $pKey )
  {
    return preg_match_all( "/[0-9]/", $pKey, $number ) &&
           preg_match_all( "/ /", $pKey, $space ) ?
             implode( "", $number[0] ) / count($space[0]) :
             "";
  }

  public function log( $pMsg )
  {
    socket_getpeername( $this->socket, $addr, $port );
    $this->server->log( "[client ". $addr. ":". $port. "] ". $pMsg );
  }
}