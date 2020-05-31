<?php	
	require_once "logon-control.php";
	require_once "logger.php";
	require_once "user-settings/Ctrl-XML.php";

	class Registration extends LogonControl
	{
		private $firstName;
		private $lastName;
		private $userName;
		private $userNameReq = "";
		private $userMail;
		private $userPWD;
		private $userSEX;
		private $userDOB;
		private $dateReg;
		
		public function __construct()
		{
			$this->firstName = $_POST["first_name"] ?? "";
			$this->lastName = $_POST["last_name"] ?? "";
			$this->userName = $_POST["user_name"] ?? "";
			$this->userMail = $_POST["user_mail"] ?? "";
			$this->userPWD = $_POST["user_pwd"] ?? "";
			$this->userSEX = $_POST["user_gender"] ?? "";
			$this->userDOB = ($_POST["dob_year"] ?? "") ."-". ($_POST["dob_month"] ?? "") ."-". ($_POST["dob_day"] ?? "");
			$this->dateReg = date("Y-m-d H:i:s");
			
			if (isset($_SESSION["v89forum_logstat"]))
			{
				header("location: index.html");
			}
			else
			{
				$allReqInpSet = false;
				$reqInpCorrect = true; // Is all input data correct?
				$reqInpErrors = "";
				
				if (!empty($this->firstName) && !empty($this->lastName) && !empty($this->userName) && !empty($this->userName))
					if (!empty($this->userMail) && !empty($this->userPWD) && !empty($this->userSEX) && isset($_POST["dob_day"]) && isset($_POST["dob_month"]) && isset($_POST["dob_year"]))
						$allReqInpSet = true;

				if (!$allReqInpSet && isset($_POST["athcmd"]))
				{
					$reqInpErrors = "<em>Please fill out all fields and check that your username isn't taken!</em>";
				}
				else if ($allReqInpSet && isset($_POST["athcmd"]))
				{
					// First Name checking point
					$flag_1 = false;
					
					if (strlen($_POST["first_name"]) < 2 || strlen($this->firstName) > 15)
					{
						$reqInpCorrect = false;
						$reqInpErrors .= "Your first name must be between 2 and 15 characters!<br/>\n";
					}

					for ($x = 0; $x < strlen(strtolower($this->firstName)); $x++)
					{
						if (!$this->is_letter($this->firstName[$x], "abcdefghijklmnopqrstuvwxyz"))
						{
							$flag_1 = true;
							$reqInpCorrect = false;
						}
					}
					
					if ($flag_1)
						$reqInpErrors .= "Your first name must only contain letters!<br/>\n";
					
					// Last Name checking point
					$flag_2 = false;
					
					if (strlen($_POST["last_name"]) < 2 || strlen($_POST["last_name"]) > 15)
					{
						$reqInpCorrect = false;
						$reqInpErrors .= "Your last name must be between 2 and 15 characters!<br/>\n";
					}
					
					for ($xt = 0; $xt < strlen(strtolower($this->lastName)); $xt++)
					{
						if (!$this->is_letter($this->lastName[$xt],"abcdefghijklmnopqrstuvwxyz"))
						{
							$flag_2 = true;
							$reqInpCorrect = false;
						}
					}
					
					if ($flag_2)
						$reqInpErrors .= "Your last name must only contain letters!<br/>\n";
					
					// Username checkpoint
					$username = strtolower($this->userName);
					
					if ($this->check_badchars($username))
					{
						$this->userNameReq = "Only letters, numbers, hyphens and underscores allowed.";
						$reqInpCorrect = false;
					}
					else if ($this->check_restricted($username))
					{
						$reqInpCorrect = false;
						$this->userNameReq = "No. Pick another one.";
					}
					else {
						$SQL = $this->fetchRecords("SELECT LCASE(UserName) FROM ExistingUserNames WHERE UserName='$username';");
						
						if ($SQL != false)
							$this->userNameReq = "Sorry, this username is taken!";
					}
					
					// User E-mail checking point
					if (strlen($_POST["user_mail"]) < 9 || strlen($_POST["user_mail"]) > 25 && !filter_var($_POST["user_mail"], FILTER_VALIDATE_EMAIL))
					{
						$reqInpCorrect = false;
						$reqInpErrors .= "Your e-mail address is invalid or is not between 9 and 25 characters!<br/>\n";
					}
					
					$SQL = $this->fetchRecords("SELECT * FROM ExistingUserNames WHERE UserMail LIKE '".$_POST["user_mail"]."';");
					
					if ($SQL != false)
					{
						$reqInpCorrect = false;
						$reqInpErrors .= "The e-mail address specified already exists!";
					}
					
					// Password checking point
					if (strlen($this->userPWD) < 7 || strlen($this->userPWD) > 25)
					{
						$reqInpCorrect = false;
						$reqInpErrors .= "Your password must be between 7 and 25 characters!<br/>\n";
					}
					
					// Display found errors, if any
					// Create new user record if no errors
					if ($reqInpCorrect)
					{
						$query_1 = "INSERT INTO UserData(FirstName, LastName, UserName, UserMail, UserPWD, UserGender, UserDOB, DateRegistered, LastActive, UserStatus) ";
						$query_1 .= "VALUES('".$this->firstName."','".
							$this->lastName ."','".
							$this->userName ."','".
							$this->userMail ."','".
							base64_encode($this->userPWD) ."','".
							$this->userSEX ."','".
							$this->userDOB ."','".
							$this->dateReg."',NOW(),2);";

						$SQL = $this->modifyRecords($query_1);
						
						if ($SQL == true)
						{
							// Fetch UserID
							$UD = $this->fetchRecords("SELECT UserID FROM UserData WHERE UserName='".$this->userName."';");
							$UserID = $UD != false ? $UD[0]["UserID"] : 0;
							
							$multiSQL = new mysqli("","","","") or die("Unable to connect to database due to: ".$multiSQL);

							$multiSQL->query("CREATE TABLE `UserPostHistory_User_$UserID`(CommentDate DATETIME PRIMARY KEY NOT NULL,CategoryIndex VARCHAR(10) NOT NULL,Title TEXT NOT NULL);");
							
							// Create new user records in other tables
							$multiSQL->query("SET @IDbasedOnName='';");
							$multiSQL->query("SELECT UserID INTO @IDbasedOnName FROM UserData WHERE UserName='".$this->userName."';");
							$multiSQL->query("UPDATE UserData SET UserSettings=CONCAT('SETS-User_',@IDbasedOnName,'_.xml') WHERE UserMail='".$this->userMail."';"); // User settings file (XML)
							
							// Create XML settings file for this user
							$SQL_2 = $multiSQL->query("SELECT UserSettings AS UserXML,UserName AS uName FROM UserData WHERE UserMail='".$this->userMail."';");
							$newXML = new XML_Controller(); // Instantiate custom created XML class
							$newXML->writeToDoc(
								"user-settings/".$SQL_2->fetch_assoc()["UserXML"],
								"<profile>",[
									"<username>",
									"<picture>",
									"<description>",
									"<signature>"
								],[
									base64_encode($SQL_2->fetch_assoc()["uName"]),
									"",
									"",
									""
								]
							);
							
							$multiSQL->query("INSERT INTO ExistingUserNames(UserName,UserID,UserMail) VALUES('".$this->userName."',@IDbasedOnName,'".$this->userMail."');");
							
							if (property_exists($multiSQL, "affected_rows") && $multiSQL->affected_rows > 0)
							{
								$multiSQL->close();

								// Go to "Account Successfully Created" page...
								$_SESSION["v89forum_logstat"] = $this->userName.",0";
								header("location: success.html");
							}
							else {
								$multiSQL->close();
								
								exit("Error: user could not be registered! Please try again.");
							}
						}
					}
				}
			}
		
			$usernameReq = (strlen($this->userNameReq) > 0 ? $this->userNameReq : "&nbsp;");
			$userMail = $this->userMail;
			$userPassword = $this->userPWD;
			$userName = $this->userName;
			$firstName = $this->firstName;
			$lastName = $this->lastName;
			
			$menuLogStatus_0 = UserLogger::navLogger(0);
			$menuLogStatus_1 = UserLogger::navLogger(1);

			print <<<REGISTRATIONFORM
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<script type="text/javascript">
			let DOByear = "";
			const t_day = new Date();
			const t_year = t_day.getFullYear();
			
			function adjustDOB_input(event)
			{				
				DOByear = document.getElementById("dob_year");

				if (DOByear)
				{
					for (let xt = (t_year-5); xt >= (t_year-90); xt--)
						DOByear.innerHTML += '<option value="' + xt + '">' + xt + '</option>\\n';

					window.setDOBDay(1); // window.alert(DOByear.options[DOByear.selectedIndex].value);
				}
				else
					window.setTimeout("window.adjustDOB_input(event);", 1000);
			}
			
			function setDOBDay(DOB_month)
			{
				let num_days = 0;
				let DOBday = document.getElementById("dob_day");
				DOBday.innerHTML = "";
				
				switch (DOB_month)
				{
					case 1: case 3: case 5: case 7:
					case 8: case 10: case 12: num_days = 31;
					break;
					case 2: num_days = t_year % 4 == 0 ? 29 : 28;
					break;
					case 4: case 6: case 9: case 11: num_days = 30;
					break;
				}
				
				for (let yt = 1; yt <= num_days; yt++)
					DOBday.innerHTML += '<option value="' + yt + '">' + yt + '</option>\\n';
			}
			
			function checkUN(username)
			{
				const XMLHttp = new XMLHttpRequest();
				
				XMLHttp.onreadystatechange = function()
				{
					if (this.status == 200 && this.readyState == 4)
					{
						const ucs = document.getElementById("usercheckstat");
						ucs.innerText = this.responseText;
						
						if (ucs.innerText.toLowerCase().indexOf("available") > -1)
							ucs.setAttribute("style", "color:#080;");
						else
							ucs.setAttribute("style", "color:#f00;");
					}
				};
				
				XMLHttp.open("POST", "checkusername.html", true);
				XMLHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				XMLHttp.send("user_name="+username.toLowerCase());
			}
			
			window.onload = window.adjustDOB_input;
		</script>
		<style type="text/css">
			form {box-shadow:5px 5px 5px #AAA; font-family:verdana; margin:auto; padding:10px; padding-bottom:35px; width:50%;}
			form input[type="text"],form input[type="password"],form input[type="email"] {display:block; font-size:16px; margin:auto; padding:5px; width:300px;}
			form select {margin-top:5px; padding:5px; text-align:center; width:96px;}
			form select option {text-align:center;}
			form input[type="submit"],form input[type="button"] {padding:6px; vertical-align:top; width:49%;}
			h1 {margin-bottom:45px; text-align:center;}

			input[type="button"],input[type="submit"],input[type="reset"]
			 {background-color:#fff; border:2px #000 solid; cursor:pointer; transition:background-color,border,color,0.25s;}
			input[type="button"]:hover,input[type="submit"]:hover,input[type="reset"]:hover
			 {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}

			nav {height:65px;}
			nav ul:nth-child(1) {float:left; margin:5px 0px 10px 20px;}
			nav ul:nth-child(2) {float:right; margin:5px 10px 10px 0px;}
			nav ul li {list-style-type:none;}
			nav ul:nth-child(2) li {float:right; margin-right:10px;}
			nav ul li a {color:#000; display:inline-block; font-family:verdana; padding:10px 20px 10px 20px; text-decoration:none; transition:background,color,0.25s;}
			nav ul li a:hover {background-color:rgb(2,105,189); color:#fff; text-decoration:none!important;}
			@media (max-width:799px)
			{
				* {margin:0px;}
				form select {width:90px;}
				form div {text-align:center;}
				form input[type="button"][value="Check!"] {width:70px!important;}
				form input[type="submit"],form input[type="button"][value="Reset"] {width:45%!important;}
				form#regForm {border-width:0px; padding:10px 0px 35px 0px; width:100%;}
				form input[type="text"],form input[type="password"],form input[type="email"] {display:block; margin:auto; width:86%;}
				input[type="button"]:active,input[type="submit"]:active,input[type="reset"]:active {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}
				nav ul {padding:0px;}
				nav ul li a {padding:10px;}
				nav ul li a:active {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}
				nav ul li:nth-child(3) a {display:none;}
			}
		</style>
		<title>Registration - V89 Forum</title>
	</head>
	<body>
		<nav>
			<ul>
				<li><a href="about.html">About</a></li>
			</ul>
			<ul>
				<li> $menuLogStatus_1 </li>
				<li><a href="index.html">Home</a></li>
				<li> $menuLogStatus_0 </li>
			</ul>
		</nav>
		<form action="register.html" id="regForm" method="post">
			<h1>Registration Form</h1>
			<input id="first_name" name="first_name" placeholder="First Name*" type="text" value="$firstName"> <br/>
			<input id="last_name" name="last_name" placeholder="Last Name*" type="text" value="$lastName"> <br/>
			<div style="margin-bottom:20px; text-align:center;">
				<input id="user_name" name="user_name" placeholder="Username*" style="display:inline-block; width:200px;" type="text" value="$userName">
				<input onclick="window.checkUN(document.getElementById('user_name').value);" style="width:96px;" type="button" value="Check!">
				<br/><em id="usercheckstat" style="color:#ff0000;"> $usernameReq </em>
			</div>
			<input id="user_mail" name="user_mail" placeholder="E-mail address*" type="email" value="$userMail"> <br/>
			<input id="user_pwd" name="user_pwd" placeholder="Password*" type="password" value="$userPassword"> <br/>
			<div style="margin-bottom:20px; text-align:center;">
				Choose gender: <span style="color:#ff0000;">*</span>
				<input checked id="user_xy" name="user_gender" type="radio" value="M"> <label for="user_xy">Male</label>
				<input id="user_xx" name="user_gender" type="radio" value="F"> <label for="user_xx">Female</label>
			</div>
			<div style="margin:auto; width:300px;">
				Date of birth: <span style="color:#ff0000;">*</span><br/>
				<select id="dob_day" name="dob_day">
					
				</select>
				<select id="dob_month" name="dob_month">
					<option onclick="window.setDOBDay(1);" value="1">January</option>
					<option onclick="window.setDOBDay(2);" value="2">February</option>
					<option onclick="window.setDOBDay(3);" value="3">March</option>
					<option onclick="window.setDOBDay(4);" value="4">April</option>
					<option onclick="window.setDOBDay(5);" value="5">May</option>
					<option onclick="window.setDOBDay(6);" value="6">June</option>
					<option onclick="window.setDOBDay(7);" value="7">July</option>
					<option onclick="window.setDOBDay(8);" value="8">August</option>
					<option onclick="window.setDOBDay(9);" value="9">September</option>
					<option onclick="window.setDOBDay(10);" value="10">October</option>
					<option onclick="window.setDOBDay(11);" value="11">November</option>
					<option onclick="window.setDOBDay(12);" value="12">December</option>
				</select>
				<select id="dob_year" name="dob_year">
				
				</select>
			</div>
			<input id="athcmd" name="athcmd" type="hidden" value="1">
			<div style="margin:40px auto; width:300px;">
				<input type="submit" value="Register">
				<input onclick="if(window.confirm('Are you sure you want to reset the form?')){document.getElementById('regForm').reset();}" type="button" value="Reset">
			</div>
			<div style="color:#ff0000; margin:auto; width:75%;">
				<div style="text-align:center;">* All fields are required.</div><hr/>
				<?php echo $reqInpErrors; ?>	
			</div>
		</form>
	</body>
</html>
REGISTRATIONFORM;
		}
	}
?>