<?php

class ThirdPartyAPI
{
	private function isConnected()
	{
		$isConnected = false;
		$connection = @fsockopen("www.voyager89.net", 80);

		if ($connection)
		{
			$isConnected = true;
			fclose($connection);
		}
		else {
			$isConnected = false;
		}

		return $isConnected;
	}

	// Sanitizer removes slashes and quotes (single and double), disables HTML/XML/PHP tags, hyphenates whitespace characters
	private function sanitizeInput($data)
	{
		$noSlashes = stripslashes($data);
		$output = preg_replace("/'/i", "", $noSlashes);
		$output = preg_replace('/"/i', "", $output);
		$output = preg_replace("/ /i", "-", $output);

		return htmlspecialchars($output);
	}

	public function __construct($query, $page)
	{
		if (!is_null($query) && !is_null($page))
		{
			if (strlen($query) > 0 && is_numeric($page) && $page > 0)
			{
				if (!$this->isConnected())
				{
					exit("Error: you are not currently connected to the internet.");
				}
				else {
					// Naturally, the client_id has been omitted as it's personal
					$queryAPI = "https://api.unsplash.com/search/photos/?client_id=...&query=" .$this->sanitizeInput($query) ."&page=$page";

					$handle = curl_init();

					// Set the url
					curl_setopt($handle, CURLOPT_URL, $queryAPI);
					// Set the result output to be a string.
					curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

					$output = curl_exec($handle);

					curl_close($handle);

					echo !$output ? "Error: the request could not be completed; please try again later." : $output;
				}
			}
			else {
				echo "Error: invalid query, or page number (or both).";
			}
		}
		else {
			echo "Error: either query or page left out of request.";
		}
	}
}

new ThirdPartyAPI($_GET["query"] ?? null, $_GET["page"] ?? null);
?>