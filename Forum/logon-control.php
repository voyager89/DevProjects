<?php
	require_once "core.php";

	class LogonControl extends Forum89
	{
		protected static $logStatus = null;

		protected function is_letter($char, $matchAgainst)
		{
			$counter = false;
			
			for ($i = 0; $i < strlen($matchAgainst); ++$i)
				if ($matchAgainst[$i] == strtolower($char))
					$counter = true;

			return $counter;
		}
		
		protected function check_restricted($userName)
		{
			$check = false;
			
			for ($j = 0; $j < sizeof($this->restrictedList); ++$j)
				if (strpos($userName, $this->restrictedList[$j]) !== false)
					$check = true;

			return $check;
		}
		
		protected function check_badchars($userName)
		{
			$check = false;
			
			for ($x = 0; $x < strlen(strtolower($userName)); ++$x)
				if (!$this->is_letter($userName[$x], "abcdefghijklmnopqrstuvwxyz1234567890-_"))
					$check = true;

			return $check;
		}
	}
?>