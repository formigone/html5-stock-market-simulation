<?php

#error_reporting(E_ALL);

require "server/websocket.server.php";
require "server/app/websocket.app.stocksim.php";

// create server
$server = new \websocket\Server();

// specify what apps will be running on the server
$server->setApp( "echo", \WebSocket\App\StockSim::getInstance() );

// start server
$server->exe();

