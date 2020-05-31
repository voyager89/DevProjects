<?php
	require_once "logon-control.php";

	class CheckUsername extends LogonControl
	{
		public function __construct()
		{
			if (isset($_POST["user_name"]) && (strlen($_POST["user_name"]) > 3 && strlen($_POST["user_name"]) < 16))
			{
				$UN = strtolower($_POST["user_name"]);
				
				if ($this->check_badchars($UN))
				{
					echo "Only letters, numbers, hyphens and underscores allowed.";
				}
				else if ($this->check_restricted($UN))
				{
					echo "No. Pick another one.";
				}
				else {
					$SQL = $this->fetchRecords("SELECT LCASE(UserName) FROM ExistingUserNames WHERE UserName='$UN';");
					
					if ($SQL != false)
						echo "Sorry, this username is taken!";
					else
						echo "This username is available!";
				}
			}
			else if (isset($_POST["user_name"]) && (strlen($_POST["user_name"]) < 4 || strlen($_POST["user_name"]) > 15))
			{
				echo "Your username must be between 4 and 15 characters!";
			}
		}
	}
?>