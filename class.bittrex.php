<?php
	/**
	* Project   : Free Mine
	* Author    : Muammer ÇATLI
	* Twitter   : @muammercatli
	* Last Edit : 19.08.2017
	*/


	class Exchange
	{
		private $apikey, $secretkey;
		function __construct($apikey = "", $secretkey = "")
		{
			$this->apikey    = $apikey;
			$this->secretkey = $secretkey;
		}
		private function send($url = "", array $req = [])
		{
			$req['apikey'] = $this->apikey;
			$req['nonce']  = time();
			$data		   = http_build_query($req, '', '&');
			$url		   = parse_url($url);
			$url		   = $url["scheme"] . "://" . $url["host"] . $url["path"] . "?" . (isset($url["query"]) ? $url["query"] . "&$data" : $data);
			$sign    = hash_hmac('sha512', $url, $this->secretkey);
			$headers = array(
				'apisign: ' . $sign
			);
			static $ch = null;
			if (is_null($ch)) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Poloniex PHP bot; ' . php_uname('a') . '; PHP/' . phpversion() . ')');
			}

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			$res = curl_exec($ch);
			if ($res === false)
				throw new Exception('Curl error: ' . curl_error($ch));
			$dec = json_decode($res, true);
			return $dec;
		}
		private function marketName($pair)
		{
			$pair = strtoupper($pair);
			$pair = str_replace("_", "-", $pair);
			$pair = str_replace("İ", "I", $pair);
			$pair = str_replace("İ", "I", $pair);
			return $pair;
		}

		public function getTicker($pair)
		{
			$pair = $this->marketName($pair);
			return $this->send("https://bittrex.com/api/v1.1/public/getticker", array(
				"market" => $pair
			));
		}
		public function getBalance($currency)
		{
			$pair = $this->marketName($currency);
			return $this->send("https://bittrex.com/api/v1.1/account/getbalance", array(
				"currency" => $currency
			));
		}
		public function buyLimit($pair, $rate, $amount)
		{
			$pair = $this->marketName($pair);
			return $this->send("https://bittrex.com/api/v1.1/market/buylimit", array(
				"market" => $pair,
				"rate" => number_format($rate,8),
				"quantity" => number_format($amount,8)
			));

		}
		public function sellLimit($pair, $rate, $amount)
		{
			$pair = $this->marketName($pair);
			return $this->send("https://bittrex.com/api/v1.1/market/selllimit", array(
				"market" => $pair,
				"rate" => number_format($rate,8),
				"quantity" => number_format($amount,8)
			));
		}
		public function cancelOrder($orderId)
		{
			return $this->send("https://bittrex.com/api/v1.1/market/cancel", array(
				"uuid" => $orderId
			));
		}
		public function getOrder($orderId)
		{
			return $this->send("https://bittrex.com/api/v1.1/account/getorder", array(
				"uuid" => $orderId
			));
		}
		public function markets()
		{
			return $this->send("https://bittrex.com/api/v1.1/public/getmarketsummaries");
		}
	}
	?>
