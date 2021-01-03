<?php
	require_once "core.php";
	require_once "logger.php";

	class TopicIndex extends Forum89
	{
		// Getting the number of posts per topic
		private $Topic_A_total = 0;
		private $Topic_B_total = 0;
		private $Topic_C_total = 0;
		private $Topic_D_total = 0;

		public function __construct($params = null)
		{
			if (!is_null($params))
			{
				if ($params == "log_off" && isset($_SESSION["v89forum_logstat"]))
				{
					session_unset();
					session_destroy();
					
					UserLogger::setLogStatus("Successfully logged off.");
				}
			}
			else if (
				isset($_POST["user_name"]) &&
				strlen($_POST["user_name"]) > 0 &&
				isset($_POST["pass_word"]) &&
				strlen($_POST["pass_word"]) < 1 &&
				isset($_POST["athcmd"])
			)
			{
				UserLogger::setLogStatus("Please type in a password!");
			}
			else if (
				isset($_POST["user_name"]) &&
				strlen($_POST["user_name"]) < 1 &&
				isset($_POST["pass_word"]) &&
				strlen($_POST["pass_word"]) > 0
			)
			{
				UserLogger::setLogStatus("Please type in a username!");
			}
			else if (
				isset($_POST["user_name"]) &&
				strlen($_POST["user_name"]) < 1 &&
				isset($_POST["pass_word"]) &&
				strlen($_POST["pass_word"]) < 1 &&
				isset($_POST["athcmd"]) &&
				$_POST["athcmd"] == "1"
			)
			{
				UserLogger::setLogStatus("Please type in your username and password.");
			}
			else if (
				isset($_POST["user_name"]) &&
				strlen($_POST["user_name"]) > 2 &&
				isset($_POST["pass_word"]) &&
				strlen($_POST["pass_word"]) > 2 &&
				isset($_POST["athcmd"]) &&
				$_POST["athcmd"] == "1"
			)
			{
				$userLogger = new UserLogger();
				$userLogger->logSession($_POST["user_name"], base64_encode($_POST["pass_word"]));
			}

			$SQL = $this->fetchRecords("SHOW TABLES;");

			if ($SQL != false)
			{
				for ($i = 0; $i < sizeof($SQL); ++$i)// $SQL_RS = $SQL_GO->fetch_assoc())
				{
					$SQL_RS = $SQL[$i];
					$thisTab = $SQL_RS["Tables_in_teopen_projects"];

					if (strpos($thisTab, "Topic_A") !== false)
					{
						$SubSQL = $this->fetchRecords("SELECT `Status` AS Stat FROM $thisTab WHERE CMID=1;");
						
						if ($SubSQL != false)
							if (intval($SubSQL[0]["Stat"]) == 1)
								$this->Topic_A_total++;
					}
					if (strpos($thisTab, "Topic_B") !== false)
					{
						$SubSQL = $this->fetchRecords("SELECT `Status` AS Stat FROM $thisTab WHERE CMID=1;");
						
						if ($SubSQL != false)
							if (intval($SubSQL[0]["Stat"]) == 1)
								$this->Topic_B_total++;
					}
					if (strpos($thisTab, "Topic_C") !== false)
					{
						$SubSQL = $this->fetchRecords("SELECT `Status` AS Stat FROM $thisTab WHERE CMID=1;");
						
						if ($SubSQL != false)
							if (intval($SubSQL[0]["Stat"]) == 1)
								$this->Topic_C_total++;
					}
					if (strpos($thisTab, "Topic_D") !== false)
					{
						$SubSQL = $this->fetchRecords("SELECT `Status` AS Stat FROM $thisTab WHERE CMID=1;");
						
						if ($SubSQL != false)
							if (intval($SubSQL[0]["Stat"]) == 1)
								$this->Topic_D_total++;
					}
				}
				
			}
			
			$thisYear = date("Y");
			
			$menuLogStatus_0 = UserLogger::navLogger(0);
			$menuLogStatus_1 = UserLogger::navLogger(1);
			
			$topic_A = $this->Topic_A_total == 1 ? $this->Topic_A_total." topic" : $this->Topic_A_total." topics";
			$topic_B = $this->Topic_B_total == 1 ? $this->Topic_B_total." topic" : $this->Topic_B_total." topics";
			$topic_C = $this->Topic_C_total == 1 ? $this->Topic_C_total." topic" : $this->Topic_C_total." topics";
			$topic_D = $this->Topic_D_total == 1 ? $this->Topic_D_total." topic" : $this->Topic_D_total." topics";
			
			$logInformation = UserLogger::showUserLogStatus($_SESSION["v89forum_logstat"] ?? null);

			print <<<INDEX
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

		<link href="bootstrap-4.0.0-dist/css/bootstrap.css" rel="stylesheet" type="text/css"/>
		<link href="bootstrap-4.0.0-dist/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
		
		<style type="text/css">
			* {font-family:verdana; margin:0px; padding:0px;}
			a {color:#000; text-decoration:none;}
			a.underline {text-decoration:underline;}
			
			h1 {font-style:italic; margin-bottom:40px;}
			
			input {border:1px #000 solid; margin:5px; padding:5px; width:250px;}
			input[type="submit"],input[type="reset"] {width:117px;}
			
			div.logtext {margin-bottom:25px;}
			div.padded {padding:9px 25px 8px 25px;}
			
			div#log_box {margin:auto; padding:10px 0px 40px 0px; text-align:center; width:100%;}
			div.tab {margin-top:50px;}
			div.tab>div {float:left; margin-bottom:50px;}
			div.tab div br {margin-bottom:20px;}
			

			span.tp1 {background-color:rgb(26,196,225); display:inline-block; height:20px; width:20px;}
			span.tp2 {background-color:rgb(11,244,116); display:inline-block; height:20px; width:20px;}
			span.tp3 {background-color:rgb(255,113,45); display:inline-block; height:20px; width:20px;}
			span.tp4 {background-color:rgb(255,75,75); display:inline-block; height:20px; width:20px;}

			hr {background-color:#000; clear:both;}
			
			div.tab div:nth-child(2) a {color:#000; display:block; margin:auto; padding:9px 25px 8px 25px; text-align:center; width:100%;}
			div.tab div:nth-child(2) a:hover:nth-child(1) {background-color:rgb(26,196,225); text-decoration:none;}
			div.tab div:nth-child(2) a:hover:nth-child(2) {background-color:rgb(11,244,116); text-decoration:none;}
			div.tab div:nth-child(2) a:hover:nth-child(3) {background-color:rgb(255,113,45); text-decoration:none;}
			div.tab div:nth-child(2) a:hover:nth-child(4) {background-color:rgb(255,75,75); text-decoration:none;}

			input[type="submit"],input[type="reset"]
			 {background-color:#fff; border:2px #000 solid; cursor:pointer; transition:background-color,border,color,0.25s;}
			input[type="submit"]:hover,input[type="reset"]:hover
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
				/* div.tab div:nth-child(1) div:nth-child(2) {display:inline-block;} */
				div.hidden-xs {display:none;}
				
				input[type="submit"]:active,input[type="reset"]:active {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}
				
				nav ul li a {padding:10px;}
				nav ul li a:active {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}
				nav ul li:nth-child(3) a {display:none;}
			}
		</style>
		<title>Message Boards - Voyager 89</title>
	</head>
	<body>
		<nav>
  			<ul>
    			<li><a href="about.html">About</a></li>
  			</ul>
  			<ul>
			  	<li>$menuLogStatus_1 </li>
				<li><a href="index.html">Home</a></li>
				<li>$menuLogStatus_0 </li>
  			</ul>
		</nav>
		<div id="log_box">
			<h1>Forum 89</h1>
			$logInformation
		</div>

		<div class="tab col-md-12 col-sm-12">
			<div class="col-md-3 col-sm-3 hidden-xs">
				<div class="padded"><span class="tp1">&nbsp;</span> Science</div>
				<div class="padded"><span class="tp2">&nbsp;</span> Art</div>
				<div class="padded"><span class="tp3">&nbsp;</span> Computing</div>
				<div class="padded"><span class="tp4">&nbsp;</span> Universe</div>
			</div>
			<div class="col-md-6 col-sm-6">
				<a href="astronomy.html">ASTRONOMY</a>
				<a href="3d_modelling.html">3D MODELLING</a>
				<a href="cs_php.html">C# or PHP?</a>
				<a href="aliens_ufo.html">ALIENS &amp; UFOs</a>
			</div>
			<div class="col-md-3 col-sm-3 hidden-xs">
				<div class="padded">$topic_A </div>
				<div class="padded">$topic_B </div>
				<div class="padded">$topic_C </div>
				<div class="padded">$topic_D </div>
			</div>
		</div>
		
		<hr/>
		
		<div style="text-align:center;">&copy; 2006 - $thisYear by Voyager 89</div>
	</body>
</html>
INDEX;
		}
	}
?>