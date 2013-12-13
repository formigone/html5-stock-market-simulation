<?php

namespace WebSocket;

require "websocket.socket.php";
require "websocket.connection.php";

class Server extends Socket
{
  private $clients;
  private $apps;

  public function __construct($pHost = 'localhost', $pPort = 4567)
  {
    parent::__construct($pHost, $pPort);
    $this->apps = array();
    $this->log( "Server created\n" );
  }

  public function setApp( $pName, $pApp )
  {
    $this->log("Setting up app");
    $this->apps[$pName] = $pApp;
  }

  public function exe()
  {
    $this->log( "HTML5 Web Socket server runneth over!\n" );
    while( true )
    {
      $this->mainLoop();
    }
  }

  private function mainLoop()
  {
    $changed_sockets = $this->allsockets;
    @socket_select( $changed_sockets, $write = null, $exceptions = null, 0);

    foreach( $this->apps as $app)
    {
      $app->onTick();
    }

    foreach( $changed_sockets as $socket )
    {

      if( $socket == $this->master)
      {
        if( ($rec = socket_accept( $this->master)) < 0 )
        {
          $this->log( "Error: Could not connect\n". socket_strerror( socket_last_error($rec) ));
          continue;
        }
        else
        {
          $this->log("New client connecting...");
          $client = new Connection( $this, $rec );
          $this->clients[ $rec ] = $client;
          $this->allsockets[] = $rec;
        }
      }

      else
      {
        $client = $this->clients[$socket];

        $bytes = @socket_recv( $socket, $data, 4096, 0);

        if( !$bytes)
        {
          $client->onDisconnect();
          unset( $this->clients[$socket] );
          $index = array_search( $socket, $this->allsockets );
          unset( $this->allsockets[$index]);
          unset( $client );
        }
        else
        {
          $client->onData( $data );
        }
      }
    }
  }

  public function getApp( $pKey )
  {
    if( array_key_exists($pKey, $this->apps) )
    {
      return $this->apps[$pKey];
    }
    else
    {
       #return false;
       return $this->apps["echo"];
    }
  }
}