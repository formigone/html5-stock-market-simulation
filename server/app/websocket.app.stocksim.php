<?php

namespace WebSocket\App;

require "websocket.app.app.php";

class StockSim extends App
{

  private $companies = array(
    array( "symbol" => "GOOG",
      "data" => array(461.33, 460.00, 470.50, 472.00, 471.40, 450.45, 447.50, 428.43, 429.00, 431.00, 439.90, 431.47, 442.05, 425.01, 441.49), 
      "volume" => 83727), 
    array( "symbol" => "MSFT",
      "data" => array(27.67, 28.21, 28.02, 27.02, 28.10, 27.32, 27.85, 28.38, 29.41, 28.50, 28.70, 27.36, 29.47, 29.30, 29.33), 
      "volume" => 1099742), 
    array( "symbol" => "CSCO",
      "data" => array(23.91, 24.68, 24.30, 24.30, 24.07, 23.97, 24.09, 24.75, 25.14, 23.60, 25.50, 24.58, 24.93, 25.73, 25.30), 
      "volume" => 570479), 
    array( "symbol" => "FDO",
      "data" => array(20.32, 21.25, 20.73, 19.90, 19.16, 19.43, 20.01, 20.30, 19.24, 18.85, 19.25, 19.69, 18.75, 19.52, 18.87), 
      "volume" => 18313), 
    array( "symbol" => "ITT",
      "data" => array(57.29, 58.68, 58.58, 57.64, 56.06, 56.11, 56.25, 54.83, 54.52, 54.33, 55.57, 53.98, 55.35, 54.18, 54.87), 
      "volume" => 14448), 
    array( "symbol" => "LUV",
      "data" => array(12.97, 12.85, 12.79, 12.49, 12.22, 12.09, 12.29, 12.90, 12.38, 12.20, 12.32, 12.40, 11.21, 11.81, 11.39), 
      "volume" => 44196), 
    array( "symbol" => "UPS",
      "data" => array(72.27, 70.01, 70.89, 72.09, 71.34, 71.88, 71.50, 71.03, 70.82, 68.55, 70.40, 71.90, 70.01, 71.80, 73.50), 
      "volume" => 45845), 
    array( "symbol" => "WEN",
      "data" => array(24.60, 25.29, 24.60, 24.39, 24.20, 23.43, 23.90, 23.42, 23.78, 24.20, 24.29, 23.25, 23.22, 23.43, 23.34), 
      "volume" => 11921), 
    array( "symbol" => "NKE",
      "data" => array(62.11, 61.98, 61.31, 60.24, 59.63, 60.81, 60.62, 58.99, 58.18, 58.14, 61.60, 58.42, 60.27, 62.79, 64.71), 
      "volume" => 24944), 
    array( "symbol" => "ZION",
      "data" => array(51.02, 51.64, 52.28, 49.80, 47.34, 45.36, 46.51, 45.79, 46.43, 50.15, 46.95, 49.33, 45.81, 49.12, 47.80), 
      "volume" => 15893)
  );

  private $companyIterator;
  private $timer;

  private $clients = array();

  public function __construct()
  {
    shuffle($this->companies);
    shuffle($this->companies);
    shuffle($this->companies);

    $this->companyIterator = 0;
    $this->timer = 0;
  }

  public function onConnect( $pClient )
  {
    echo "\n>> Total active traders using the simulator: ", count($this->clients) + 1, "\n\n";

    $this->clients[] = $pClient;
    $pClient->send("<%status%>Welcome to the market!");
    $pClient->send("<%init%>". $this->getMarketInfo());
  }

  public function onDisconnect( $pClient )
  {
    $key = array_search( $pClient, $this->clients );
    if( $key )
    {
      unset($this->clients[$key]);
    }
  }


  public function onData( $pData, $pClient )
  {

    $message = explode('%>', $pData);
    $message = substr($message[0], 2);

    if( $message === 'poke' )
    {

      $timeDiff = (int)(time(true) - $this->timer);

      // push new data every 8 seconds
      if( $timeDiff > 1 )
      {
        $this->timer = time();

        $this->companyIterator = ($this->companyIterator + 1) % (count($this->companies[0]['data']) - 1);
        $this->clients[0]->send("<%status%>iterator = {$this->companyIterator}");

        $marketUpdate = $this->getMarketInfo();

        foreach( $this->clients as $toSend)
        {
          $toSend->send("<%data%>{$marketUpdate}");
          $toSend->send("<%status%>Updates received from the server");
        }
      }
    }/* poke */

    else if( $message === 'buy' )
    {
      $message = explode('%>', $pData);
      $tempArgs = explode(',', $message[1]);

      $args = array();

      foreach($tempArgs as $arg)
      {
        $tempArg = explode('=', $arg);
        $args[$tempArg[0]] = $tempArg[1];
      }

      // attempt to buy shares from company
      if( $this->companies[ $args['company'] - 1 ]['volume'] >= $args['volume'] )
      {
        $this->companies[ $args['company'] - 1]['volume'] -= $args['volume'];
        $pClient->send("<%confirmbuy%>{$args['company']},{$args['volume']},{$args['price']}");
        $pClient->send('<%status%>Purchase successful');

        $marketUpdate = $this->getMarketInfo();

        foreach( $this->clients as $toSend)
        {
          $toSend->send("<%data%>{$marketUpdate}");
          $toSend->send("<%status%>Updates received from the server");
        }
      }
      else
      {
        $pClient->send('<%confirmbuy%>fail');
        $pClient->send('<%status%>Could not purchase shares');
      }

    }

    else if( $message === 'sell' )
    {
      $message = explode('%>', $pData);
      $tempArgs = explode(',', $message[1]);

      $args = array();

      foreach($tempArgs as $arg)
      {
        $tempArg = explode('=', $arg);
        $args[$tempArg[0]] = $tempArg[1];
      }

      // attempt to buy shares from company
      $this->companies[ $args['company'] - 1]['volume'] += $args['volume'];
      $pClient->send("<%confirmsell%>{$args['company']},{$args['volume']},{$args['price']}");
      $pClient->send('<%status%>Sale successful');

      $marketUpdate = $this->getMarketInfo();

      foreach( $this->clients as $toSend)
      {
        $toSend->send("<%data%>{$marketUpdate}");
        $toSend->send("<%status%>Updates received from the server. More shares to buy!");
      }
    }
  }


  private function getMarketInfo()
  {
    $i = 0;
    $json = '[';

    foreach($this->companies as $comp)
    {
      $bid = $comp['data'][$this->companyIterator] + 3;

      $json .= "{\"symbol\":\"{$comp['symbol']}\",".
               "\"volume\":{$comp['volume']},".
               "\"ask\":{$comp['data'][$this->companyIterator]},".
               "\"bid\":{$bid}}";
      if(++$i > 2)
        return $json."]";
      else
        $json .= ",";
    }
  }
}