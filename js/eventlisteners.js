/**
 * Initiate a BUY transaction
 */
$('.stockCompBtnBuy1').bind('click', function(){

  var companyToBuy = $(this).parent().parent().attr('id').slice(-1);

  $('#trans1For'+companyToBuy).hide();

  $('#trans2SellFor'+companyToBuy).hide();
  $('#tableToSellFor'+companyToBuy).hide();

  $('#trans2BuyFor'+companyToBuy).show();
  $('#tableToBuyFor'+companyToBuy).show();
  $('#inpanelFor' + companyToBuy).slideDown();

  $('#stockCompBuyTot'+ companyToBuy).text('$'+ (25 * window.companies[companyToBuy - 1].bid).toFixed(2) );
  $('#stockCompBuyMycash'+ companyToBuy).text('$'+ parseFloat(window.myPortfolio.myCash).toFixed(2) );

});


/**
 * Adjust costs and volumes based on the value of the volume slider
 */
$('input[type=range]').change(function(){
  var companyToTrans = $(this).attr('id').slice(-1);
  var action = $(this).attr('id').slice('stockComp'.length, (0 - 'SlidX'.length));

  if( action === 'Buy' )
  {
    var price = window.companies[companyToTrans - 1].ask;
    var volume = $(this).attr('value');
    var cost = (volume * price);

    $('#stockCompBuyVol'+ companyToTrans).text(volume);
    $('#stockCompBuyTot'+ companyToTrans).text('$' + cost.toFixed(2) );
    $('#stockComp'+ companyToTrans + 'TrasMycash').text(window.myPortfolio.myCash);
  }

  if( action === 'Sell' )
  {
    var price = window.companies[companyToTrans - 1].bid;
    var volume = $(this).attr('value');
    var cost = (volume * price);
    var vol = $('#portCompVol'+ companyToTrans).text() + 0;

    $('#stockCompSellVol'+ companyToTrans).text(volume);
    $('#stockCompSellTot'+ companyToTrans).text('$' + cost.toFixed(2) );
    $('#stockCompSellMyshares' + companyToTrans).text(vol);
  }
});


/**
 * Confirm a BUY transaction
 */
$('.confirmBuy button:first-child').bind('click', function(){
  var companyToBuy = $(this).parent().attr('id').slice(-1);
  var volume = $('#stockCompBuySlid' + companyToBuy).attr('value');
  var price = window.companies[companyToBuy - 1].ask;
  var total = (volume * price).toFixed(2);

  // make the purchase!
  if( window.myPortfolio.myCash >= total )
  {
    socket.send('<%buy%>company='+companyToBuy+',volume='+volume+',price='+price);
  }
  else
  {
    showAlert('No money, no honey', "Looks like you can't afford this many shares of " + window.companies[ companyToBuy - 1 ].symbol + 
      ".<br/><br/>Try selecting a smaller amount, or selling some shares in order to make some more money...");
  }
});


/**
 * Confirm a SELL transaction
 */
$('.confirmSell button:last-child').bind('click', function(){
  var companyToSell = $(this).parent().attr('id').slice(-1);
  var volume = $('#stockCompSellSlid' + companyToSell).attr('value');
  var price = window.companies[companyToSell - 1].ask;
  var total = (volume * price).toFixed(2);

  // make the purchase!
  if( window.myPortfolio['comp'+companyToSell].volume >= volume )
  {
    socket.send('<%sell%>company='+companyToSell+',volume='+volume+',price='+price);
  }
  else
  {
    showAlert("You can't sell what you don't have", "Looks like you're tryint to sell more shares of " + window.companies[ companyToSell - 1 ].symbol + 
      ".<br/><br/>Try selecting a smaller amount, or buying more shares in order to make some more money...");
  }
});


/**
 * Cancel a transaction
 */
$('.stockCompBtnCancel').bind('click', function(){
  var companyCancel = $(this).parent().parent().attr('id').slice(-1);

  $('#inpanelFor' + companyCancel).slideUp();
  $('#trans2BuyFor' + companyCancel).hide();
  $('#trans2SellFor' + companyCancel).hide();
  $('#trans1For' + companyCancel).show();

});


/**
 * Initiate a SELL transaction
 */
$('.stockCompBtnSell1').bind('click', function(){

  var companyToSell = $(this).parent().parent().attr('id').slice(-1);
  var vol = $('#portCompVol'+ companyToSell).text() + 0;

  $('#trans1For'+companyToSell).hide();

  $('#trans2BuyFor'+companyToSell).hide();
  $('#tableToBuyFor'+companyToSell).hide();

  $('#stockCompSellMyshares'+companyToSell).text( vol );

  $('#trans2SellFor'+companyToSell).show();
  $('#tableToSellFor'+companyToSell).show();
  $('#inpanelFor' + companyToSell).slideDown();

  $('#stockCompSellTot'+ companyToSell).text('$'+ (25 * window.companies[companyToSell - 1].bid).toFixed(2) );
  $('#stockCompSellMycash'+ companyToSell).text('$'+ parseFloat(window.myPortfolio.myCash).toFixed(2) );

});


/**
 * Cancel an alert
 */
$('#alert button').bind('click', function(){
  hideAlert();
});