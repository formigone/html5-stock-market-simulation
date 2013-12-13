<?php

namespace WebSocket;

/**
 * A generic PHP socket.
 */
class Socket
{
  protected $master;
  protected $allsockets;
  private $host;
  private $port;

  public function __construct($pHost = 'localhost', $pPort = 4567)
  {
    $this->allsockets = array();
    $this->host = $pHost;
    $this->port = $pPort;

    $this->createSocket($pHost, $pPort);
  }


  /**
   * socket_create parameters:
   *
   * Domain: AF_INET = IPv4 protocol
   * Type: SOCK_STREAM = TCP, full-duplex donnection based byte stream
   * Protocol: SOL_TCP = Reliable TCP
   */
  private function createSocket()
  {
    if( ($this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) < 0)
    {
      die( "Error: Could not create socket\n". socket_strerror($this->master) );
    }

    $this->log("Socket {$this->master} created");

    socket_set_option( $this->master, SOL_SOCKET, SO_REUSEADDR, 1);

    if( ($ret = socket_bind( $this->master, $this->host, $this->port)) < 0 )
    {
      die( "Error: Could not bind socket\n". socket_strerror($ret));
    }

    $this->log("Socket bound to http://{$this->host}:{$this->port}/");

    if( ($ret = socket_listen( $this->master, 5)) < 5 )
    {
      die( "Error: Could not connect to client\n". socket_strerror($ret));
    }

    $this->log("Listening on socket");

    $this->allsockets[] = $this->master;
  }


  /**
   * Push data to client
   */
  private function send($pClient, $pMsg)
  {
    socket_write($pClient, $pMsg, strlen($pMsg));
  }


  /**
   * Log message to standard out
   */
  public function log($pMsg)
  {
    echo '>> ';
    echo $pMsg, "\n";
  }
}