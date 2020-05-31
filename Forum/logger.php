<?php
	require_once "logon-control.php";
	
	class UserLogger extends LogonControl
	{		
		public static function getLogStatus()
		{
			return self::$logStatus;
		}
		
		public static function setLogStatus($status)
		{
			self::$logStatus = $status;
		}
		
		public static function showUserLogStatus($logSession)
		{
			if (is_null($logSession))
			{
				$log_status = self::getLogStatus();
				
				return <<<FORM
<div class="logtext">
				Please log on or <a class="underline" href="register.html">register</a> to continue.
			</div>
			<form action="index.html" method="post">
				<input id="user_name" name="user_name" placeholder="Username" type="text"><br>
				<input id="pass_word" name="pass_word" placeholder="Password" type="password"><br>
				<input id="athcmd" name="athcmd" type="hidden" value="1">
				<input type="submit" value="Log On">
				<input type="reset" value="Reset">
				<span style="color:#ff0000; display:block; font-size:14px; font-style:italic;"> $log_status </span>
			</form>
FORM;
			}
			else {
				return "Welcome back, ".$_SESSION["v89forum_logstat"].'!<br><a class="underline" href="index-log_off.html">Log Off</a>';
			}
		}

		public static function navLogger($task)
		{
			switch($task)
			{
				case 1:
					$eventViewProfile = strpos($_SERVER['REQUEST_URI'], "index.html") !== false ? "document.getElementById('user_name').focus();document.getElementById('user_name').select();" : "window.location.href='index.html';";
					return !is_null($_SESSION["v89forum_logstat"] ?? null) ? '<a href="profile.html">View Profile</a>' : '<a href="#" onclick="'.$eventViewProfile.' return false;">View Profile</a>';

				case 0:
					$eventViewProfile = strpos($_SERVER['REQUEST_URI'], "index.html") !== false ? "document.getElementById('user_name').focus();document.getElementById('user_name').select();" : "window.location.href='index.html';";
					return !is_null($_SESSION["v89forum_logstat"] ?? null) ? '<a href="index-log_off.html">Log Off</a>' : '<a href="#" onclick="'.$eventViewProfile.' return false;">Log On</a>';
			}
		}

		public function logSession($postedUsername, $postedPassword)
		{			
			$SQL = $this->fetchRecords("SELECT UserName AS user_name, UserPWD AS user_password FROM UserData;");
			
			if ($SQL != false)
			{
				$isUserValid = false;
				
				for ($i = 0; $i < sizeof($SQL); ++$i)
				{
					$row = $SQL[$i];

					if (strcmp(strtolower($row["user_name"]), strtolower($postedUsername)) == 0)
					{
						$isUserValid = true;
						
						if (strcasecmp($row["user_password"], $postedPassword) == 0)
						{
							$_SESSION["v89forum_logstat"] = $row["user_name"];
						}
						else
						{
							self::$logStatus = "Incorrect password!";
						}
					}
				}
				
				if (!$isUserValid)
					self::$logStatus = "No such username!";
			}
		}
	}
?>