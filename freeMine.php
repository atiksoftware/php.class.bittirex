<?php

	include "class.bittrex.php";
	$satoshi=0.00000001;
	$btcamount=0.01;
	$buyorders=array();
	$exchange = new exchange("apikey", "secretkey");
	$sums=$exchange->markets()["result"];
	$spreads=array();
	foreach($sums as $sum){
		$spreads[]=strpos($sum["MarketName"],"BTC-") !== false ? ($sum["Ask"]-$sum["Bid"])/$sum["Bid"] : 0;
	}
	$sum=$sums[array_search(max($spreads),$spreads)];
	$price=$sum["Bid"]+$satoshi;
	$market=$sum["MarketName"];
	$amount=$btcamount/$price;
	$boughtFromCanceled=0;
	$soldFromCanceled=0;
	$sold=0;
	$bought=0;
	$buyorder = $exchange->buyLimit($market, $price, $amount);
	if ($buyorder["success"] == 1){
		while(true){
			$ticker=$exchange->getTicker($market);
			$buyUUID=@isset($buyorder) && is_array($buyorder) && array_key_exists("uuid",$buyorder["result"]) ? $buyorder["result"]["uuid"] : $buyorder["result"]["OrderUuid"];
			$sellUUID=@isset($sellorder) && is_array($sellorder) && array_key_exists("uuid",$sellorder["result"]) ? $sellorder["result"]["uuid"] :( @isset($sellorder) ? $sellorder["result"]["OrderUuid"] : null);
			$buyorder=$exchange->getOrder($buyUUID);
			$bought=$buyorder["result"]["Quantity"]-$buyorder["result"]["QuantityRemaining"];
			if(@isset($sellorder)){
				$sellorder=$exchange->getOrder($sellUUID);
				$sold=$sellorder["result"]["Quantity"]-$sellorder["result"]["QuantityRemaining"];
			}
			if(
			($boughtFromCanceled+$bought>$soldFromCanceled+$sold || !@isset($sellorder) || $sellorder["result"]["Limit"] != $ticker["result"]["Ask"])
			&& ($bought+$boughtFromCanceled)-($sold+$soldFromCanceled)>=0.001){
				$soldFromCanceled+=$sold;
				$sold=0;
				if(@isset($sellorder)) $exchange->cancelOrder($sellUUID);
				$sellorder = $exchange->sellLimit($market, $ticker["result"]["Ask"]-$satoshi, $boughtFromCanceled+$bought-$soldFromCanceled);
			}
			if($buyorder["result"]["Limit"] != $ticker["result"]["Bid"] && $amount-($bought+$boughtFromCanceled)>=0.001){
				$exchange->cancelOrder($buyUUID);
				$boughtFromCanceled+=$bought;
				$bought=0;
				$buyorder=$exchange->buyLimit($market, $ticker["result"]["Bid"]+$satoshi, $amount-$boughtFromCanceled);
			}
			if($amount-($bought+$boughtFromCanceled)<0.001 && ($bought+$boughtFromCanceled)-($sold+$soldFromCanceled)<0.001){
				break;
			}
		}
	}

?>
