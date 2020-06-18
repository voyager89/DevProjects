<?php
	session_start();

	abstract class Forum89
	{
		private $SQL;
		
		// To add a new restricted word, simply add it in the array below.
		// I've removed my list of obscene words...add your own
		private $restrictedList = [
			"...",
			"...",
			"...",
		];
		
		const FORUM89_VERSION = 1.2;
		const FORUM89_RELEASE_DATE = "03/09/2018";
		const FORUM89_FINAL_TEST_DATE = "09/09/2018";

		// FILTER: No backslashes or HTML tags allowed!
		private function filterHtmlAndSlashes($data)
		{
			$output = stripslashes(trim($data));
			
			return htmlspecialchars($output);
		}
		
		// FILTER: No bad words
		private function filterBadLanguage($text)
		{
			$input = explode(" ", $text);

			for ($i = 0; $i < sizeof($input); ++$i)
				for ($j = 0; $j < sizeof($this->restrictedList); ++$j)
					if (stripos($input[$i], $this->restrictedList[$j]) !== false)
						$input[$i] = "*beep*";

			return implode(" ", $input);
		}
		
		protected function getStats()
		{
			return array(
				"version" => sprintf("%.2f", self::FORUM89_VERSION),
				"release_date" => self::FORUM89_RELEASE_DATE,
				"final_test_date" => self::FORUM89_FINAL_TEST_DATE
			);
		}
		
		private function V89DB() // For obvious reasons, database credentials are omitted
		{
			// production
			$this->SQL = new mysqli("","","","") ?? exit("Cannot connect to the database due to: ".$this->SQL->connect_error);
			
			// local testing
			//$this->SQL = new mysqli("","","","") ?? exit("Cannot connect to database due to: ".$this->SQL->connect_error);
		}
		
		protected function modifyRecords($data) // DELETE FROM, INSERT INTO, UPDATE - $SQL_C->affected_rows
		{
			$recordsModified = false;

			$this->V89DB();
		
			$this->SQL->query($data);
			
			if (isset($this->SQL->affected_rows) && $this->SQL->affected_rows > 0)
				$recordsModified = true;

			$this->SQL->close();
			$this->SQL = null;

			return $recordsModified;
		}
		
		protected function fetchRecords($query)
		{
			// if rows are an array, they must contain all data elements required
			// ir rows is a single element, only that element will be returned
			$records = [];
			
			$this->V89DB();
			
			$SQL_C = $this->SQL->query($query);
			
			if (isset($SQL_C->num_rows) && $SQL_C->num_rows > 0)
				while ($row = $SQL_C->fetch_assoc())
					array_push($records, $row);

			$this->SQL->close();
			$this->SQL = null;

			return sizeof($records) > 0 ? $records : false;
		}
		
		// Trims front zero, for e.g. remove the 0 from 09 and returns 9
		protected function trimFrontZero($data)
		{
			return !$data[0] ? $data[1] : $data;
		}

		// More user-friendly way of determining recency of post
		protected function isPostRecent($postDate)
		{
			$SQL = $this->fetchRecords("SELECT TIMEDIFF(NOW(),'$postDate') AS TDRS;");
			$days_rs = explode(":", $SQL[0]["TDRS"]); //For e.g. 3 minutes and 33 seconds - 00:03:33

			if ($days_rs[0] == 0)
			{
				if ($days_rs[1] == 0)
				{
					if ($days_rs[2] == 0)
					{
						return "Just now";
					}
					else if ($days_rs[2] > 0 && $days_rs[2] < 10)
					{
						return "A few seconds ago";
					}
					else if ($days_rs[2] > 9)
					{
						return $days_rs[2]." seconds ago";
					}
				}
				else if ($days_rs[1] >= 1)
				{
					$count = ($days_rs[1] == 1 ? "" : "s");

					return $this->trimFrontZero($days_rs[1])." minute$count ago";
				}
			}
			else if ($days_rs[0] == 1)
			{
				return $this->trimFrontZero($days_rs[0])." hour ago";
			}
			else if ($days_rs[0] > 1 && $days_rs[0] < 24)
			{
				return $this->trimFrontZero($days_rs[0])." hours ago";
			}
			else if ($days_rs[0] >= 24 && $days_rs[0] < 48)
			{
				return "1 day ago";
			}
			else if ($days_rs[0] >= 48 && $days_rs[0] < 168)
			{
				return (round($days_rs[0] / 24, 0, PHP_ROUND_HALF_DOWN))." days ago";
			}
			else {
				$conv_date = strtotime($postDate);
				return date("D M d Y - H:i:s", $conv_date);
			}
		}

		protected function applyFilters($data)
		{
			$output = $this->filterHtmlAndSlashes($data);
			
			return $this->filterBadLanguage($output);
		}
	}
?>