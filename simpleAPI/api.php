<?php
	class SimpleAPI
	{
		const INVALID_REQUEST = "Error: Invalid request. A request must be made in this format: /{category}/{task}/{input}?format={xml or json}&key={access key}";
		const REQUIRED_ARGUMENTS = 3; // simpleAPI/{one}/{two}/{three}

		private function removeSpecialCharsDisguise($str)
		{
			$input = $str;
			$output = "";
			
			$specialChars = [
				["\"", "[01]"],
				["`", "[02]"],
				["!", "[03]"],
				["@", "[04]"],
				["#", "[05]"],
				["$", "[06]"],
				["%", "[07]"],
				["^", "[08]"],
				["&", "[09]"],
				["*", "[10]"],
				["{", "[11]"],
				["}", "[12]"],
				["|", "[13]"],
				["<", "[14]"],
				[">", "[15]"],
				["=", "[16]"],
				["/", "[17]"],
				[" ", "+"]
			];
			
			for ($i = 0; $i < strlen($str); ++$i)
			{
				for ($j = 0; $j < sizeof($specialChars); ++$j)
				{
					$input = str_replace($specialChars[$j][1] , $specialChars[$j][0], $input);
				}
			}
			
			return $input;
		}

		private function isRequestStructureCorrect($request)
		{
			$dirs = explode("/", $request);

			$num_of_dirs = sizeof($dirs);

			if ($num_of_dirs < 3 								||
				($num_of_dirs == 4 && strlen($dirs[3]) > 0) 	||
				$num_of_dirs > (self::REQUIRED_ARGUMENTS + 1) 	||
				($num_of_dirs == 3 && strlen($dirs[2]) > 512) 	||
				!isset($_GET["format"]) 						||
				(isset($_GET["format"]) && $_GET["format"] != "json" && $_GET["format"] != "xml")
			)
			{
				exit($this->changeToSelectedFormat("json", ["Error", self::INVALID_REQUEST]));
			}
		}

		private function changeToSelectedFormat($format, $params)
		{
			switch($format)
			{
				case "json":
					return json_encode($params);
				case "xml":
					$writeXML = xmlwriter_open_memory();
					xmlwriter_set_indent($writeXML, 1);
					$res = xmlwriter_set_indent_string($writeXML, ' ');

					xmlwriter_start_document($writeXML, '1.0', 'UTF-8');

					xmlwriter_start_element($writeXML, "output");

					foreach ($params as $key => $value)
					{
						xmlwriter_start_element($writeXML, $key);
						xmlwriter_text($writeXML, $value);
						xmlwriter_end_element($writeXML);
					}

					xmlwriter_end_element($writeXML);
					xmlwriter_end_document($writeXML);

					return htmlspecialchars(xmlwriter_output_memory($writeXML));
			}
		}
		private function isTimezoneValid($timezone)
		{
			$input_timezone = $timezone;
			
			// e.g. Africa__Mogadishu becomes Africa/Mogadishu
			if (strpos($input_timezone, "__") !== false)
			{
				$regions = explode("__", $input_timezone);
				$input_timezone = implode("/", $regions);
			}

			$allTimezones = array("Africa/Maseru","Africa/Mbabane","Africa/Mogadishu","Africa/Monrovia","Africa/Nairobi","Africa/Ndjamena","Africa/Niamey","Africa/Nouakchott","Africa/Ouagadougou","Africa/Porto-Novo","Africa/Sao_Tome","Africa/Timbuktu","Africa/Tripoli","Africa/Tunis","Africa/Windhoek","America/Adak","America/Anchorage","America/Anguilla","America/Antigua","America/Araguaina","America/Argentina/Buenos_Aires","America/Argentina/Catamarca","America/Argentina/ComodRivadavia","America/Argentina/Cordoba","America/Argentina/Jujuy","America/Argentina/La_Rioja","America/Argentina/Mendoza","America/Argentina/Rio_Gallegos","America/Argentina/Salta","America/Argentina/San_Juan","America/Argentina/San_Luis","America/Argentina/Tucuman","America/Argentina/Ushuaia","America/Aruba","America/Asuncion","America/Atikokan","America/Atka","America/Bahia","America/Bahia_Banderas","America/Barbados","America/Belem","America/Belize","America/Blanc-Sablon","America/Boa_Vista","America/Bogota","America/Boise","America/Buenos_Aires","America/Cambridge_Bay","America/Campo_Grande","America/Cancun","America/Caracas","America/Catamarca","America/Cayenne","America/Cayman","America/Chicago","America/Chihuahua","America/Coral_Harbour","America/Cordoba","America/Costa_Rica","America/Creston","America/Cuiaba","America/Curacao","America/Danmarkshavn","America/Dawson","America/Dawson_Creek","America/Denver","America/Detroit","America/Dominica","America/Edmonton","America/Eirunepe","America/El_Salvador","America/Ensenada","America/Fort_Nelson","America/Fort_Wayne","America/Fortaleza","America/Glace_Bay","America/Godthab","America/Goose_Bay","America/Grand_Turk","America/Grenada","America/Guadeloupe","America/Guatemala","America/Guayaquil","America/Guyana","America/Halifax","America/Havana","America/Hermosillo","America/Indiana/Indianapolis","America/Indiana/Knox","America/Indiana/Marengo","America/Indiana/Petersburg","America/Indiana/Tell_City","America/Indiana/Vevay","America/Indiana/Vincennes","America/Indiana/Winamac","America/Indianapolis","America/Inuvik","America/Iqaluit","America/Jamaica","America/Jujuy","America/Juneau","America/Kentucky/Louisville","America/Kentucky/Monticello","America/Knox_IN","America/Kralendijk","America/La_Paz","America/Lima","America/Los_Angeles","America/Louisville","America/Lower_Princes","America/Maceio","America/Managua","America/Manaus","America/Marigot","America/Martinique","America/Matamoros","America/Mazatlan","America/Mendoza","America/Menominee","America/Merida","America/Metlakatla","America/Mexico_City","America/Miquelon","America/Moncton","America/Monterrey","America/Montevideo","America/Montreal","America/Montserrat","America/Nassau","America/New_York","America/Nipigon","America/Nome","America/Noronha","America/North_Dakota/Beulah","America/North_Dakota/Center","America/North_Dakota/New_Salem","America/Ojinaga","America/Panama","America/Pangnirtung","America/Paramaribo","America/Phoenix","America/Port-au-Prince","America/Port_of_Spain","America/Porto_Acre","America/Porto_Velho","America/Puerto_Rico","America/Punta_Arenas","America/Rainy_River","America/Rankin_Inlet","America/Recife","America/Regina","America/Resolute","America/Rio_Branco","America/Rosario","America/Santa_Isabel","America/Santarem","America/Santiago","America/Santo_Domingo","America/Sao_Paulo","America/Scoresbysund","America/Shiprock","America/Sitka","America/St_Barthelemy","America/St_Johns","America/St_Kitts","America/St_Lucia","America/St_Thomas","America/St_Vincent","America/Swift_Current","America/Tegucigalpa","America/Thule","America/Thunder_Bay","America/Tijuana","America/Toronto","America/Tortola","America/Vancouver","America/Virgin","America/Whitehorse","America/Winnipeg","America/Yakutat","America/Yellowknife","Antarctica/Casey","Antarctica/Davis","Antarctica/DumontDUrville","Antarctica/Macquarie","Antarctica/Mawson","Antarctica/McMurdo","Antarctica/Palmer","Antarctica/Rothera","Antarctica/South_Pole","Antarctica/Syowa","Antarctica/Troll","Antarctica/Vostok","Arctic/Longyearbyen","Asia/Aden","Asia/Almaty","Asia/Amman","Asia/Anadyr","Asia/Aqtau","Asia/Aqtobe","Asia/Ashgabat","Asia/Ashkhabad","Asia/Atyrau","Asia/Baghdad","Asia/Bahrain","Asia/Baku","Asia/Bangkok","Asia/Barnaul","Asia/Beirut","Asia/Bishkek","Asia/Brunei","Asia/Calcutta","Asia/Chita","Asia/Choibalsan","Asia/Chongqing","Asia/Chungking","Asia/Colombo","Asia/Dacca","Asia/Damascus","Asia/Dhaka","Asia/Dili","Asia/Dubai","Asia/Dushanbe","Asia/Famagusta","Asia/Gaza","Asia/Harbin","Asia/Hebron","Asia/Ho_Chi_Minh","Asia/Hong_Kong","Asia/Hovd","Asia/Irkutsk","Asia/Istanbul","Asia/Jakarta","Asia/Jayapura","Asia/Jerusalem","Asia/Kabul","Asia/Kamchatka","Asia/Karachi","Asia/Kashgar","Asia/Kathmandu","Asia/Katmandu","Asia/Khandyga","Asia/Kolkata","Asia/Krasnoyarsk","Asia/Kuala_Lumpur","Asia/Kuching","Asia/Kuwait","Asia/Macao","Asia/Macau","Asia/Magadan","Asia/Makassar","Asia/Manila","Asia/Muscat","Asia/Nicosia","Asia/Novokuznetsk","Asia/Novosibirsk","Asia/Omsk","Asia/Oral","Asia/Phnom_Penh","Asia/Pontianak","Asia/Pyongyang","Asia/Qatar","Asia/Qostanay","Asia/Qyzylorda","Asia/Rangoon","Asia/Riyadh","Asia/Saigon","Asia/Sakhalin","Asia/Samarkand","Asia/Seoul","Asia/Shanghai","Asia/Singapore","Asia/Srednekolymsk","Asia/Taipei","Asia/Tashkent","Asia/Tbilisi","Asia/Tehran","Asia/Tel_Aviv","Asia/Thimbu","Asia/Thimphu","Asia/Tokyo","Asia/Tomsk","Asia/Ujung_Pandang","Asia/Ulaanbaatar","Asia/Ulan_Bator","Asia/Urumqi","Asia/Ust-Nera","Asia/Vientiane","Asia/Vladivostok","Asia/Yakutsk","Asia/Yangon","Asia/Yekaterinburg","Asia/Yerevan","Atlantic/Azores","Atlantic/Bermuda","Atlantic/Canary","Atlantic/Cape_Verde","Atlantic/Faeroe","Atlantic/Faroe","Atlantic/Jan_Mayen","Atlantic/Madeira","Atlantic/Reykjavik","Atlantic/South_Georgia","Atlantic/St_Helena","Atlantic/Stanley","Australia/ACT","Australia/Adelaide","Australia/Brisbane","Australia/Broken_Hill","Australia/Canberra","Australia/Currie","Australia/Darwin","Australia/Eucla","Australia/Hobart","Australia/LHI","Australia/Lindeman","Australia/Lord_Howe","Australia/Melbourne","Australia/North","Australia/NSW","Australia/Perth","Australia/Queensland","Australia/South","Australia/Sydney","Australia/Tasmania","Australia/Victoria","Australia/West","Australia/Yancowinna","Brazil/Acre","Brazil/DeNoronha","Brazil/East","Brazil/West","Canada/Atlantic","Canada/Central","Canada/Eastern","Canada/Mountain","Canada/Newfoundland","Canada/Pacific","Canada/Saskatchewan","Canada/Yukon","Chile/Continental","Chile/EasterIsland","Etc/GMT","Etc/GMT+0","Etc/GMT+1","Etc/GMT+10","Etc/GMT+11","Etc/GMT+12","Etc/GMT+2","Etc/GMT+3","Etc/GMT+4","Etc/GMT+5","Etc/GMT+6","Etc/GMT+7","Etc/GMT+8","Etc/GMT+9","Etc/GMT-0","Etc/GMT-1","Etc/GMT-10","Etc/GMT-11","Etc/GMT-12","Etc/GMT-13","Etc/GMT-14","Etc/GMT-2","Etc/GMT-3","Etc/GMT-4","Etc/GMT-5","Etc/GMT-6","Etc/GMT-7","Etc/GMT-8","Etc/GMT-9","Etc/GMT0","Etc/Greenwich","Etc/UCT","Etc/Universal","Etc/UTC","Etc/Zulu","Europe/Amsterdam","Europe/Andorra","Europe/Astrakhan","Europe/Athens","Europe/Belfast","Europe/Belgrade","Europe/Berlin","Europe/Bratislava","Europe/Brussels","Europe/Bucharest","Europe/Budapest","Europe/Busingen","Europe/Chisinau","Europe/Copenhagen","Europe/Dublin","Europe/Gibraltar","Europe/Guernsey","Europe/Helsinki","Europe/Isle_of_Man","Europe/Istanbul","Europe/Jersey","Europe/Kaliningrad","Europe/Kiev","Europe/Kirov","Europe/Lisbon","Europe/Ljubljana","Europe/London","Europe/Luxembourg","Europe/Madrid","Europe/Malta","Europe/Mariehamn","Europe/Minsk","Europe/Monaco","Europe/Moscow","Europe/Nicosia","Europe/Oslo","Europe/Paris","Europe/Podgorica","Europe/Prague","Europe/Riga","Europe/Rome","Europe/Samara","Europe/San_Marino","Europe/Sarajevo","Europe/Saratov","Europe/Simferopol","Europe/Skopje","Europe/Sofia","Europe/Stockholm","Europe/Tallinn","Europe/Tirane","Europe/Tiraspol","Europe/Ulyanovsk","Europe/Uzhgorod","Europe/Vaduz","Europe/Vatican","Europe/Vienna","Europe/Vilnius","Europe/Volgograd","Europe/Warsaw","Europe/Zagreb","Europe/Zaporozhye","Europe/Zurich","Indian/Antananarivo","Indian/Chagos","Indian/Christmas","Indian/Cocos","Indian/Comoro","Indian/Kerguelen","Indian/Mahe","Indian/Maldives","Indian/Mauritius","Indian/Mayotte","Indian/Reunion","Mexico/BajaNorte","Mexico/BajaSur","Mexico/General","Pacific/Apia","Pacific/Auckland","Pacific/Bougainville","Pacific/Chatham","Pacific/Chuuk","Pacific/Easter","Pacific/Efate","Pacific/Enderbury","Pacific/Fakaofo","Pacific/Fiji","Pacific/Funafuti","Pacific/Galapagos","Pacific/Gambier","Pacific/Guadalcanal","Pacific/Guam","Pacific/Honolulu","Pacific/Johnston","Pacific/Kiritimati","Pacific/Kosrae","Pacific/Kwajalein","Pacific/Majuro","Pacific/Marquesas","Pacific/Midway","Pacific/Nauru","Pacific/Niue","Pacific/Norfolk","Pacific/Noumea","Pacific/Pago_Pago","Pacific/Palau","Pacific/Pitcairn","Pacific/Pohnpei","Pacific/Ponape","Pacific/Port_Moresby","Pacific/Rarotonga","Pacific/Saipan","Pacific/Samoa","Pacific/Tahiti","Pacific/Tarawa","Pacific/Tongatapu","Pacific/Truk","Pacific/Wake","Pacific/Wallis","Pacific/Yap","Poland","Portugal","PRC","PST8PDT","ROC","ROK","Singapore","Turkey","UCT","Universal","US/Alaska","US/Aleutian","US/Arizona","US/Central","US/East-Indiana","US/Eastern","US/Hawaii","US/Indiana-Starke","US/Michigan","US/Mountain","US/Pacific","US/Pacific-New","US/Samoa","UTC","W-SU","WET","Zulu");
			
			return in_array($input_timezone, $allTimezones);
		}
		
		private function sanitize($input)
		{
			$output = $this->removeSpecialCharsDisguise($input);

			return htmlspecialchars(stripslashes($output));
		}

		private function runCategoryWithOptions($request)
		{
			$requestData = explode("/", $request);

			$format = $this->sanitize($_GET["format"]);
			$category = $this->sanitize($requestData[0]);
			$task = $this->sanitize($requestData[1]);
			$input = $this->sanitize($requestData[2]);
			
			$output = "";

			switch($category)
			{
				case "timezone":
					if ($task == "currentTimeZone" && $input == "getTimeAndDate")
					{
						$output = $this->changeToSelectedFormat
						(
							$format,
							[
								"date" => date("H:i:s d M Y"),
								"timezone" => date_default_timezone_get()
							]
						);
					}
					else if ($this->isTimezoneValid($task) && $input == "getTimeAndDate")
					{
						$selectedTimezone = $task;

						if (strpos($selectedTimezone, "__") !== false)
						{
							$regions = explode("__", $selectedTimezone);
							$selectedTimezone = implode("/", $regions);
						}

						date_default_timezone_set($selectedTimezone);

						$output = $this->changeToSelectedFormat
						(
							$format,
							[
								"date" => date("H:i:s d M Y"),
								"timezone" => $selectedTimezone
							]
						);
					}
					else {
						exit($this->changeToSelectedFormat($format, ["Error", "Bad timezone request - ' $task ', ' $input '"]));
					}
				break;				
				case "tools":
					switch ($task)
					{
						case "base64Decode":
							$decoded = base64_decode($input, true);

							if ($decoded == false)
								$output = $this->changeToSelectedFormat($format, ["decoded" => "' $input ' cannot be decoded."]);
							else
								$output = $this->changeToSelectedFormat($format, ["decoded" => $decoded]);
						break;
						case "base64Encode":
							$output = $this->changeToSelectedFormat($format, ["encoded" => base64_encode($input)]);
						break;
						case "md5":
							$output = $this->changeToSelectedFormat($format, ["md5" => md5($input)]);
						break;
						default:
							exit($this->changeToSelectedFormat($format, ["Error", "Unrecognized task for $category: ' $task '."]));
					}
				break;
				default:
					exit($this->changeToSelectedFormat($format, ["Error", "Unrecognized category: ' $category '."]));
			}
			
			echo $output;
		}
		
		private function checkAccessKey($key)
		{
			if (is_null($key))
			{
				exit($this->changeToSelectedFormat("json", ["Error", "No access key provided. Request denied."]));
			}
			else {
				// Access key must be: simpleAPI123 encoded as base64
				$readKey = base64_decode($key, true); // c2ltcGxlQVBJMTIz
				
				if ($readKey == false || $readKey != "simpleAPI123")
				{
					exit($this->changeToSelectedFormat("json", ["Error", "Invalid key provided"]));
				}
			}
		}
		
		public function __construct($request)
		{
			if (!is_null($request))
			{
				if (strpos($request, "/") !== false) // subdirectories requested
				{
					$this->checkAccessKey($_GET["key"] ?? null);
					
					// Check to see if request is correctly structured, otherwise terminate
					$this->isRequestStructureCorrect($request);

					// Is the request itself valid, if not then terminate
					//$this->isRequestValid($request);

					// Send request to appropriate function
					$this->runCategoryWithOptions($request);
				}
				else {
					exit($this->changeToSelectedFormat("json", ["Error", self::INVALID_REQUEST]));
				}
			}
			else {
				exit($this->changeToSelectedFormat("json", ["Error", "Null request!"]));
			}
		}
	}

	new SimpleAPI($_GET["request"] ?? null);
?>