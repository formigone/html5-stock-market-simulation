/**
 * keeps track of the companies being
 * traded in teh simulation
 */
var companies = [{
  symbol: null,
  volume: 0,
  ask: 0,
  bid: 0 }, {
  symbol: null,
  volume: 0,
  ask: 0,
  bid: 0 }, {
  symbol: null,
  volume: 0,
  ask: 0,
  bid: 0 }];


/**
 * keeps track of all transactions made
 * by the user
 */
var myPortfolio = {
  myCash: 100000,
  comp1: { volume: 0, price: 0 },
  comp2: { volume: 0, price: 0 },
  comp3: { volume: 0, price: 0 }
};


// the backbone of the application: the HTML5 web socket!
var socket = new WebSocket("ws://localhost:4567/cs460/websocket.html5server.cs460.php");


/**
 * register callbacks on the socket
 */

socket.onopen = function(e){
  $('#status').text("Connection opened");
  $('#stretch img').remove();
  openTheMarket();
  pokeServer();
}

socket.onclose = function(e){
  $('#stretch img').remove();
  $('#main').fadeOut(1500, function(){ $('#main').remove();} );
  $('#bottom').fadeOut(1500, function(){ $('#bottom').remove();} );
  $('#status').text("Connection closed");
  showAlert('Game Over', 'The market is now closed. The server has been powered off (or maybe it just crashed or got hacked, who knows...)<br/><br/> Thanks for playing!');
}

socket.onmessage = function(e){ 
  parseMessage(e.data); 
}

socket.onerror = function(e) { showAlert('Error =(', "Error: "+e); }