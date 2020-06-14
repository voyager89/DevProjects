<?php
	require_once "index.php";
	require_once "checkusername.php";
	require_once "general-navigation.php";
	require_once "register.php";
	require_once "topic.php";
	require_once "topic-control.php";
	require_once "user-control.php";
	
	class Controller
	{
		private function isPageQueryValid($document)
		{
			$isValid = false;
			$docAnalysis = [
				"query" => false,
				"valid" => false,
			];
			
			$pageList = [
				// page, takes arguments
				["3d_modelling", true],
				["404", true],
				["about", false],
				["aliens_ufo", true],
				["astronomy", true],
				["checkusername", false],
				["cs_php", true],
				["delete", true],
				["delete_acc", true],
				["edit", true],
				["index", true],
				["logoff", false],
				["profile", true],
				["register", false],
				["reply", true],
				["success", false]
			];
			
			for ($i = 0; $i < sizeof($pageList); ++$i)
			{
				$page = $pageList[$i][0];
				$parameters = $pageList[$i][1];

				if (strpos($document, $page) == 0)
				{
					if (strcmp($document, $page) == 0) // about = about (case-sensitive) (NO PARAMETERS)
					{
						$docAnalysis["valid"] = true;
					}
					else if (strpos($document, "-") !== false) // parameters
					{
						$data = explode("-", $document);
						
						switch ($data[0]) // name
						{
							case "profile":
								if (
									(sizeof($data) == 2 && $data[1] == "preview") ||
									(sizeof($data) == 3 && $data[1] == "user" && is_numeric($data[2]) && $data[2] > 0)
								)
								{
									$docAnalysis["query"] = true;
									$docAnalysis["valid"] = true;
								}
							break;
							case "index":
								if (sizeof($data) == 2 && $data[1] == "log_off")
								{
									$docAnalysis["query"] = true;
									$docAnalysis["valid"] = true;
								}
							break;
							case "404":
								if (sizeof($data) == 2 && sizeof(trim($data[1])) > 0)
								{
									$docAnalysis["query"] = true;
									$docAnalysis["valid"] = true;
								}
							break;
							case "3d_modelling": // e.g. 3d_modelling-1 OR 3d_modelling-post
							case "aliens_ufo":
							case "astronomy":
							case "cs_php":
								if (
									sizeof($data) == 2 && (
										(is_numeric($data[1]) && $data[1] > 0) ||
										($data[1] == "post")
									)
								)
								{
									$docAnalysis["query"] = true;
									$docAnalysis["valid"] = true;
								}
							break;
							case "delete":
							case "edit":
							case "reply": // e.g. reply-A-1-1
								if (
									sizeof($data) == 4 &&
									ctype_alpha($data[1]) && sizeof($data[1]) == 1 &&
									is_numeric($data[2]) && $data[2] > 0 &&
									is_numeric($data[3]) && $data[3] > 0
								)
								{
									$docAnalysis["query"] = true;
									$docAnalysis["valid"] = true;
								}
							break;
						}
					}
				}
			}
			
			return $docAnalysis;
		}
		
		public function __construct($url)
		{
			if (!is_null($url))
			{
				$docAnalysis = $this->isPageQueryValid($url);

				if ($docAnalysis["valid"] == true)
				{
					// documents requested with query parameters
					if ($docAnalysis["query"] == true)
					{
						$data = explode("-", $url);
						
						switch ($data[0]) // name
						{
							case "profile":
								if (
									(sizeof($data) == 2 && $data[1] == "preview") ||
									(sizeof($data) == 3 && $data[1] == "user" && is_numeric($data[2]) && $data[2] > 0)
								)
								{
									$queryString = "";
									
									if (sizeof($data) == 3)
									{
										$queryString = $data[1]."-".$data[2];
									}
									else {
										$queryString = $data[1];
									}
									
									new UserControl($queryString);
								}
							break;
							case "index":
								new TopicIndex("log_off");
							break;
							case "404":
								if (sizeof($data) == 2 && sizeof(trim($data[1])) > 0)
								{
									new GeneralNavigation("404", $data[1]);
								}
							break;
							case "3d_modelling":
								new TopicsList("B", "3D Modelling", "3d_modelling", $data[1]);
							break;
							case "aliens_ufo":
								new TopicsList("D", "Aliens &amp; UFOs", "aliens_ufo", $data[1]);
							break;
							case "astronomy":
								new TopicsList("A", "Astronomy", "astronomy", $data[1]);
							break;
							case "cs_php":
								new TopicsList("C", "C# or PHP", "cs_php", $data[1]);
							break;
							case "delete":			
							case "edit":
							case "reply": // e.g. reply-A-1-1
								if (
									sizeof($data) == 4 &&
									ctype_alpha($data[1]) && sizeof($data[1]) == 1 &&
									is_numeric($data[2]) && $data[2] > 0 &&
									is_numeric($data[3]) && $data[3] > 0
								)
								{
									new TopicControl($data[0], $data[1]."-".$data[2]."-".$data[3]);
								}
							break;
						}
					}
					else {
						// ordinary document - no query parameters - but may have POST data
						switch (strtolower($url))
						{
							case "about":
								new GeneralNavigation("about");
							break;
							case "checkusername":
								new CheckUsername();
							break;
							case "index":
								new TopicIndex();
							break;
							case "3d_modelling":
								new TopicsList("B", "3D Modelling", "3d_modelling");
							break;
							case "aliens_ufo":
								new TopicsList("D", "Aliens &amp; UFOs", "aliens_ufo");
							break;
							case "astronomy":
								new TopicsList("A", "Astronomy", "astronomy");
							break;
							case "cs_php":
								new TopicsList("C", "C# or PHP", "cs_php");
							break;
							case "logoff":
								session_unset();
								session_destroy();
								
								header("location: index.html");
							break;
							case "profile":
								new UserControl();
							break;
							case "register":
								new Registration();
							break;
							case "delete_acc": // delete account
								new TopicControl("delete_acc");
							break;
							case "success":
								// For successfully registered users
								$newUser = [];
								
								if (
									isset($_SESSION["v89forum_logstat"]) &&
									strlen($_SESSION["v89forum_logstat"]) > 0 &&
									strpos($_SESSION["v89forum_logstat"], ",0") !== false
								)
								{
									$newUser = explode(",", $_SESSION["v89forum_logstat"]);
									$_SESSION["v89forum_logstat"] = $newUser[0];
								}
								else
								{
									header("location: index.html");
								}
								
								$newUsername = $newUser[0];
								$logData = $_SESSION["v89forum_logstat"];
								$thisYear = date("Y");
								
								print <<<BLOCK
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<style type="text/css">
			* {font-family:verdana;}

			a {border:2px #000 solid; border-radius:2px; color:#000; display:inline-block;
			margin:10px; margin-top:30px; padding:5px; text-decoration:none; transition:background-color,color,0.25s; width:20%;}
			a:hover,a:active {background-color:#000; color:#fff;}
			a:active {background-color:#ffff00; color:#000;}

			h1 {text-align:center;}

			@media (max-width:799px)
			{
				a {width:45%!important;}
			}
		</style>
		<title>Successfully registered - $newUsername </title>
	</head>
	<body>
		<div>
			<h1>Welcome, $logData!</h1>
			<hr/>

			<div style="text-align:center;">
				<a href="profile.html">My Profile</a>
				<a href="logoff.html">Log Off</a>

				<div style="margin-top:50px;">
					&copy; 2000 - $thisYear by Voyager 89
				</div>
			</div>
		</div>
	</body>
</html>
BLOCK;
							break;
							default: // I don't know what this is; return 404
								new GeneralNavigation("404", $url);
							break;
						}
					}
				}
				else {
					//exit("Bad request: ".$url);
					new GeneralNavigation("404", $url);
				}
			}
		}
	}

	new Controller($_GET["requested_document"] ?? null);
?>