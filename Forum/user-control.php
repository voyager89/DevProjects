<?php
	require_once "core.php";
	require_once "logger.php";
	require_once "user-settings/Ctrl-XML.php"; // Custom XML read/write class

	class UserControl extends Forum89
	{
		private $currentUser = "";
		private $userStatus = "";
		private $userLastActive = "";
		private $userRegDate = "";
		private $userDescription = "";
		private $userSettings = "";
		private $userSex = '';
		private $userSignature = "";
		private $userProfileImage = "";
		private $newProfileImage = "";
		
		private $postCheck;
		private $uploadOk;

		// Update notifications
		private $updateProDesc = ["",""]; // Message about update profile description
		private $updateProSign = ["",""]; // Message about update profile signature
		private $updateProPic = ["",""]; // Message about updating profile picture
		private $updateProfile = ["",""];

		// Returns the specified update notice if it has been set
		private function getUpdateNotice($notice)
		{
			$color = $notice[0];
			$text = $notice[1];

			if (strlen($color) == 3 && strlen($text) > 0)
				return '<em style="color:#'.$color.'; font-size:14px;">'.$text.'</em>';
		}

		// Updates notice for any of these - description, image, signature
		// Arguments are the notice variable, color, message
		private function updateNotice(&$notice, $type, $message)
		{
			//$this->updateNotice($this->updateProDesc, "f00", "Description is too short")
			//$this->updateNotice($this->updateProDesc, "f00", [", and description is too short.", "Description is too short."])

			if (strlen($type) == 3 &&
				(gettype($message) == "string" && strlen($message) > 0) ||
				(
					gettype($message) == "array" && sizeof($message) == 2
					&& strlen($message[0] == 3 && strlen($message[1]) > 0)
				)
			)
			{
				if (sizeof($notice) == 2)
				{
					if (strlen($notice[0]) == 3 && strlen($notice[1]) > 0)
					{
						$notice[0] = $type;
						$notice[1] .= (gettype($message) == "array" ? $message[0] : $message);
					}
					else
					{
						$message_notice = (gettype($message) == "array" ? $message[1] : $message);
						$notice[0] = $type;
						$notice[1] = $message_notice;
					}
				}
			}
		}

		// Reads category and index, and returns a hyperlink to it
		private function readCat($task, $category)
		{
			$pageLink = [];
			
			$pageLink[0] = !$task ? "3d_modelling" : "3D Modelling";
			$pageLink[1] = !$task ? "cs_php" : "C# or PHP";
			$pageLink[2] = !$task ? "aliens_ufo" : "Aliens &amp; UFOs";

			switch ($category)
			{
				case "A": return ($task ? '<a href="astronomy.html">' : '').'Astronomy'.($task ? '</a>' : '');
				case "B": return ($task ? '<a href="3d_modelling.html">' : ''). $pageLink[0].($task ? '</a>' : '');
				case "C": return ($task ? '<a href="cs_php.html">' : '').$pageLink[1].($task ? '</a>' : '');
				case "D": return ($task ? '<a href="aliens_ufo.html">' : '').$pageLink[2].($task ? '</a>' : '');
			}
		}

		// Display all posts made by specified user
		private function showAllUserPosts($currentUser)
		{
			$output = "";
			// Fetch user ID
			$userID = $this->fetchRecords("SELECT UserID FROM `UserData` WHERE UserName='$currentUser';");

			// Get all topics this user has posted in
			$topics = $this->fetchRecords("SELECT CommentDate,CategoryIndex,Title FROM `UserPostHistory_User_".$userID[0]["UserID"]."` ORDER BY CommentDate DESC;");

			if ($topics != false)
			{
				// This shows the most recent posts, top to bottom
				for ($i = 0; $i < sizeof($topics); ++$i)
				{
					$row = $topics[$i];

					$categoryIndex = explode("_", $row["CategoryIndex"]);
					$category = $categoryIndex[0];
					$index = $categoryIndex[1];
					
					// Fetch the comment ID
					$commentID = $this->fetchRecords("SELECT CMID FROM `Topic_".$row["CategoryIndex"]."` WHERE CMDate='".$row["CommentDate"]."';");
					$CMID = $commentID != false ? $commentID[0]["CMID"] : 0;
					
					$output .= "\n\t\t\t\t<tr>\n\t\t\t\t\t<td>". $this->readCat(1, $category) ."</td>\n\t\t\t\t\t<td><a href='". strtolower($this->readCat(0, $category)) ."-". $index .".html#c$CMID'>". base64_decode($row["Title"]) ."</a></td>\n";
					$output .= "\t\t\t\t\t<td>". $this->isPostRecent($row["CommentDate"]) ."</td>\n\t\t\t\t</tr>";
				}
			}
			else {
				$output = "Cannot read user's posts.";
			}

			$byUser = $this->isRequestedUserLoggedOn($currentUser) ? "found in your history" : "by this user";
			$totalPosts = !$topics ? "\n\t\t\t\t<tr>\n\t\t\t\t\t<td class='no_posts' colspan='3'>No posts $byUser.</td>\n\t\t\t\t</tr>" : $output;
			
			return $totalPosts;
		}
		
		// Is specified user currently logged on?
		private function isRequestedUserLoggedOn($user)
		{
			$response = false;
			
			$SQL = $this->fetchRecords("SELECT UserName FROM ExistingUserNames WHERE UserID='$user';");
			
			if ($SQL != false && isset($_SESSION["v89forum_logstat"]))
			{
				if (strcmp($SQL[0]["UserName"], $_SESSION["v89forum_logstat"]) == 0)
				{
					$response = true;
				}
			}
			
			return $response;
		}

		public function __construct($params = null)
		{
			$this->uploadOk = 1;
			$this->postCheck = true;
			
			$preview = false;
			$user = false;

			if (!is_null($params))
			{
				if (strpos($params, "user") !== false)
				{
					$user = substr($params, (strpos($params, "-") + 1), strlen($params));
				}
				else {
					$preview = true;
				}
			}

			// When requesting to view another user's profile
			if ($user && !$this->isRequestedUserLoggedOn($user))
			{
				$doesUserExist = $this->fetchRecords("SELECT * FROM ExistingUserNames WHERE UserID='$user';");
				
				if ($doesUserExist != false) // User ID exists, load their profile unless deleted
				{
					// Load requested profile
					$user_data = $this->fetchRecords("SELECT UserName,DateRegistered AS DateReg,LastActive AS LAC,UserStatus AS user_stat,UserGender,UserSettings AS usersets FROM UserData WHERE UserID='$user';");
					
					if ($user_data != false)
					{
						$USD = $user_data[0];

						$uSex = $USD["UserGender"]=="M" ? "pro-male.jpg" : "pro-female.jpg";
						$this->userStatus = $USD["user_stat"];
						$this->userLastActive = $USD["LAC"];
						$this->userRegDate = $USD["DateReg"];
						$this->userSettings = $USD["usersets"];
						
						$this->currentUser = $USD["UserName"];

						if ($this->userSettings != '[Deleted]')
						{
							$rPro = [];
							$nx = new XML_Controller();
							$rPro = $nx->readDoc("user-settings/".$this->userSettings, "<profile>", ["<username>","<picture>", "<description>", "<signature>"]);
							$this->userProfileImage = strlen($rPro[1]) > 1 ? $rPro[1] : $uSex;
							$this->userDescription = $rPro[2];
							//$user_sign = $rPro[3];
							unset($rPro);
						}
						
						// What is the user status
						switch ($this->userStatus)
						{
							case "0": $this->userStatus = "Deleted"; 								break;
							case "1": $this->userStatus = "Administrator"; 							break;
							case "2": $this->userStatus = "User"; 									break;
							case "3": $this->userStatus = '<em style="color:#f00;">Disabled</em>'; 	break;
						}
					}
				}
				else { // No such user found
					$this->userStatus = "N/A";
					$this->userLastActive = "N/A";
					$this->userRegDate = "N/A";
					$this->currentUser = "[Non-existent]";
					$this->userDescription = '<em style="color:#f00;">No such user found!</em>';
				}
			}
			else if (!isset($_SESSION["v89forum_logstat"]) && !$user)
			{
				// If not logged in, go back to index page
				header("location: index.html");
			}
			else if ($preview && !isset($_SESSION["v89forum_logstat"]))
			{
				// You can't preview a profile if you're not signed in
				header("location: index.html");
			}
			else if ((isset($_SESSION["v89forum_logstat"]) && !$user) || ($user && $this->isRequestedUserLoggedOn($user)))
			{
				$this->currentUser = $_SESSION["v89forum_logstat"];
				
				if (isset($_POST["savpr"])) // Updating of profile description, or signature, or image?
				{
					$uPro = [];

					$nx = new XML_Controller();
					
					$SQL = $this->fetchRecords("SELECT UserSettings FROM UserData WHERE UserName='".$this->currentUser."';");
					
					$this->userSettings = "user-settings/".$SQL[0]["UserSettings"];
					$userIDnum = explode("_", $this->userSettings)[1]; // This is the User ID used for unique profile image renaming
					
					if (strlen($_POST["prodesc"]) > 250)
					{
						$this->postCheck = false;
						$this->updateNotice($this->updateProDesc, "f00", "Your profile description is too long.");
					}
					else {
						$uPro[2] = $this->applyFilters($_POST["prodesc"]);
						$this->updateNotice($this->updateProDesc, "070", "Your profile description has been updated.");
					}

					// Signature?
					if (strlen($_POST["prosign"]) > 100)
					{
						$this->postCheck = false;
						$this->updateNotice($this->updateProSign, "f00", "Your profile signature is too long.");
					}
					else {
						$uPro[3] = $this->applyFilters($_POST["prosign"]);
						$this->updateNotice($this->updateProSign, "070", "Your profile signature has been updated.");
					}
					
					// Updating profile pic?
					$profilePicFilename = strlen($_FILES["proPic"]["name"]);

					if ($profilePicFilename > 0)
					{
						if ($profilePicFilename > 4)
						{
							$this->uploadOk = 1;
							
							$target_dir = "user-pics/";
							$imgFile = $_FILES["proPic"]["tmp_name"];
							$target_file = $target_dir.basename($_FILES["proPic"]["name"]);
							$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
							
							// Check if image file is a actual image or fake image
							$finfo = finfo_open(FILEINFO_MIME_TYPE);
							$checkImageIsReal = finfo_file($finfo, $imgFile);
							finfo_close($finfo);
							
							// https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
							// Image must be a valid image and only certain file formats are allowed
							if (
								$checkImageIsReal && (
									strpos($checkImageIsReal, "image/jpeg") !== false ||
									strpos($checkImageIsReal, "image/png") !== false ||
									strpos($checkImageIsReal, "image/gif") !== false
								)
							)
							{
								/*
								$this->updateNotice($this->updateProPic, "070", "File is an image - ".$check["mime"].".");
								*/
								$this->uploadOk = 1;
							}
							else {
								$this->uploadOk = 0;

								$this->updateNotice($this->updateProPic, "f00", "This file is not an image, or not the allowed format. Only JPG, JPEG, PNG and GIF files are allowed. Please try again.");
								
							}
							
							// Check if file already exists
							/*
							if (file_exists($target_file))
							{
								echo "Sorry, file already exists.";
								$this->uploadOk=0;
							} */
							
							// Check file size
							if ($_FILES["proPic"]["size"] > 2000000) // Bigger than 2MB?
							{
								$this->uploadOk = 0;

								$this->updateNotice($this->updateProPic, "f00", [", and your photo is too large.", "Photo is too large."]);
							}
							
							// Check if $this->uploadOk is set to 0 by an error
							if ($this->uploadOk == 0)
							{
								// echo "Sorry, your file was not uploaded.";
								// if everything is ok, try to upload file
							}
							else {
								if (move_uploaded_file($_FILES["proPic"]["tmp_name"], $target_file) == true)
								{								
									// Renaming profile pic
									$FNFT = explode(".", $target_file);
									$imgFileType = $FNFT[sizeof($FNFT)-1];
									$this->newProfileImageName = base64_encode("profile_".$userIDnum."_".$imgFileType);
									rename($target_file,"user-pics/".$this->newProfileImageName);

									$this->updateNotice($this->updateProPic, "070", "The photo ".basename($_FILES["proPic"]["name"]). " has been uploaded.");
									//$uPro[1]="user-pics/".$this->newProfileImageName;
									$this->newProfileImage = "user-pics/".$this->newProfileImageName;
								}
								else
								{
									$this->uploadOk = 0;
									$this->updateNotice($this->updateProPic, "f00", "Your file could not be uploaded. Please try again.");
								}
							}
						}
						else {
							$this->uploadOk = 0;
							$this->updateNotice($this->updateProPic, "f00", "Image file name must be a minimum of 5 characters!");
						}
					}
					
					$exIMG = [];
					$nx = new XML_Controller();
					$exIMG = $nx->readDoc($this->userSettings, "<profile>",["<username>","<picture>","<description>","<signature>"]);
					$userProImg = strlen($this->newProfileImage) > 1 ? $this->newProfileImage : $exIMG[1];

					unset($exIMG);
					
					// Write to settings file
					if ($this->postCheck == true && $this->uploadOk == 1)
					{
						$nx->writeToDoc($this->userSettings, "<profile>", ["<username>", "<picture>","<description>", "<signature>"], [$_SESSION["v89forum_logstat"], $userProImg, $uPro[2], $uPro[3]]);
					}

					unset($uPro);
				}
				
				// Load the profile as usual
				$SQL = $this->fetchRecords("SELECT DateRegistered AS DateReg,LastActive AS LAC,UserStatus AS user_stat,UserSettings AS usersets,UserGender FROM UserData WHERE UserName='".$this->currentUser."';");
				
				if ($SQL != false)
				{
					for ($i = 0; $i < sizeof($SQL); ++$i)
					{
						$rec = $SQL[$i];

						$this->userStatus = $rec["user_stat"];
						$this->userLastActive = $rec["LAC"];
						$this->userRegDate = $rec["DateReg"];
						$this->userSex = $rec["UserGender"];
						$this->userSettings = "user-settings/".$rec["usersets"];
					}
				}
				
				// Read profile settings file
				$rPro = [];
				$nx = new XML_Controller();
				$rPro = $nx->readDoc($this->userSettings,"<profile>",["<username>","<picture>","<description>","<signature>"]);
				$this->userProfileImage = $rPro[1];
				$this->userDescription = $rPro[2];
				$this->userSignature = $rPro[3];

				unset($rPro);
				
				// Define user status
				switch ($this->userStatus)
				{
					case "0": $this->userStatus = "Deleted"; break;
					case "1": $this->userStatus = "Administrator"; break;
					case "2": $this->userStatus = "User"; break;
					case "3": $this->userStatus = '<em style="color:#f00;">Disabled</em>'; break;
				}
			}
			
			// Set profile title
			$getTitle = "";

			if ($preview && isset($_SESSION["v89forum_logstat"]))
			{
				$getTitle = "Profile of ".$this->currentUser." - ";
			}
			else if (isset($_SESSION["v89forum_logstat"]) && !$preview)
			{
				$getTitle = "My Profile - ";
			}
			else if ($user && !$preview)
			{
				$getTitle = $this->currentUser." profile - ";
			}
			
			// Navigation bar
			$navigationBar_0 = UserLogger::navLogger(0);
			$navigationBar_1 = UserLogger::navLogger(1);
			
			// User data
			$currentUser = $this->currentUser;
			$userStatus = $this->userStatus;
			$userLastActive = $this->isPostRecent($this->userLastActive);
			$userRegistrationDate = $this->isPostRecent($this->userRegDate);
			
			// Profile and Image ($profileImageAndDescription)
			$proImgDesc = '<d'.'iv style="background-color:rgb(220,220,220); border-bottom:1px #fff solid; padding:5px;">';
			$proImgDesc .= "\n";
			
			if ($preview && isset($_SESSION["v89forum_logstat"]) && !$user)
			{
				$proImgDesc .= $this->userDescription."\n";
				$proImgDesc .= "						</d"."iv>\n";
			}
			else if (!$preview && $user && !$this->isRequestedUserLoggedOn($user))
			{
				if (strpos($currentUser, "[") !== false || strpos($currentUser, "?") !== false) // Check to see if a non-existent or non-numeric user flag is up
				{
					$proImgDesc .= $this->userDescription."\n						</d"."iv>\n";
				}
				else
				{
					if ($this->userSettings != '[Deleted]')
					{
						$versionImage = microtime(true); // prevent browser caching image
						$proImgDesc .= '	<img alt="Profile Photo" src="'.$this->userProfileImage.'?v='.$versionImage.'" style="border:1px #000 solid; width:150px;"/>';
					}

					$proImgDesc .= $this->userDescription."\n";
					$proImgDesc .= "						</d"."iv>\n";
					$proImgDesc .= '				<d'.'iv style="background-color:rgb(220,220,220); padding:4px;">';
					$proImgDesc .= "\n";
					/*
					$proImgDesc .= '					<a href="#">Ignore</a> postings from <strong>'.$this->currentUser.'</strong> <br/>';
					$proImgDesc .= "\n";
					$proImgDesc .= '					<a href="#">Add</a> <strong>'.$this->currentUser.'</strong> to my friend list <br/>';
					$proImgDesc .= "\n";
					$proImgDesc .= '					<a href="#">Send</a> a private message to <strong>'.$this->currentUser.'</strong> <br/>';
					*/
					$proImgDesc .= "\n				</d"."iv>\n";
				}
			}
			else if (
				isset($_SESSION["v89forum_logstat"]) &&
				(!$preview && !$user) ||
				(!$preview && $user && $this->isRequestedUserLoggedOn($user))
			)
			{
				$userPrIM = "";
				
				if (strlen($this->userProfileImage) > 5) // User image
				{
					$versionImage = microtime(true); // prevent browser from caching
					/*$imgDT=explode("/",$userProImg);
					$userPrIM=$imgDT[0]."/".base64_decode($imgDT[1]); */
					$userPrIM = $this->userProfileImage."?v=$versionImage";
				}
				else
				{
					$userPrIM = ($this->userSex == 'M' ? "pro-male.jpg" : "pro-female.jpg");
				}
				
				$updateDesc = $this->getUpdateNotice($this->updateProDesc);

				$proImgDesc .= '					<form action="profile.html" enctype="multipart/form-data" method="post" style="margin:auto;">';
				$proImgDesc .= "\n";
				$proImgDesc .= '						<label for="proPic" style="font-size:12px;">Profile picture (max 2MB):</label><br/>';
				$proImgDesc .= "\n";
				$proImgDesc .= '						<img alt="Profile Photo" src="'.$userPrIM.'" style="border:1px #000 solid; width:150px;"/>';
				$proImgDesc .= "\n";
				$proImgDesc .= '						<br/><input id="proPic" name="proPic" style="width:99%;" type="file"/>';
				$proImgDesc .= "\n";
				$proImgDesc .= '						'.$this->getUpdateNotice($this->updateProPic).'<br/>';
				$proImgDesc .= "\n";
				$proImgDesc .= '						'.$updateDesc.'<br/>';
				$proImgDesc .= "\n";
				$proImgDesc .= '						'.$this->getUpdateNotice($this->updateProSign).'<br/>';
				$proImgDesc .= "\n";
				$proImgDesc .= '						'.$this->getUpdateNotice($this->updateProfile).'<br/>';
				
				$proImgDesc .= '						<label for="prodesc" style="font-size:12px;">Profile description (<em id="desclen" class="chars">250 characters maximum</em>):</label><br/>';
				$proImgDesc .= "\n";
				$proImgDesc .= '						<textarea name="prodesc">'.$this->userDescription.'</textarea><br/><br/>';
				$proImgDesc .= "\n\n";
				$proImgDesc .= '						<label for="prosign" style="font-size:12px;">Signature (<em class="chars">100 characters maximum</em>):</label><br/>';
				$proImgDesc .= "\n";
				$proImgDesc .= '						<input name="prosign" type="text" value="'.$this->userSignature.'">';
				$proImgDesc .= "\n";
				$proImgDesc .= '							<input name="savpr" style="width:49%;" type="submit" value="SAVE">';
				$proImgDesc .= "\n";
				$proImgDesc .= '							<input onclick="window.open(\'profile-preview.html\',\'\');" style="width:49%;" type="button" value="PREVIEW">';
				$proImgDesc .= "\n						</form>\n						\n";
				$proImgDesc .= "\n";
				$proImgDesc .= '						<d'.'iv style="margin:30px 0px 20px 0px; text-align:center;">';
				$proImgDesc .= '							<form action="delete_acc.html" method="post" onsubmit="return window.confirm(document.getElementById(\'confirmDelete\').value);">';
				$proImgDesc .= "\n";
				$proImgDesc .= '							<input id="confirmDelete" name="confirmDelete" type="hidden" value="Are you sure you want to delete your account? This cannot be undone."/>';
				$proImgDesc .= "\n";
				$proImgDesc .= ' 							<br/><br/><input title="Delete account" type="submit" value="Delete Account">';
				$proImgDesc .= "\n						</form>\n					</d"."iv>\n				</d"."iv>\n";
			}
			
			// Recent posts
			$recentPosts = "";
			
			if (strpos($currentUser, "[") === false && strpos($currentUser, "?") === false)
			{
				$recentPosts = <<<PostBlock
<tr>
				<td class="problue">Message Board</td>
				<td class="problue">Subject</td>
				<td class="problue">When</td>
			</tr>
PostBlock;
				$recentPosts .= $this->showAllUserPosts($this->currentUser);
			}
			else if (strpos($userStatus, "Deleted") !== false)
			{
				$recentPosts = <<<PostBlock
			<tr>
				<td colspan="3">There are no posts by this user</td>
			</tr>
PostBlock;
			}
			
			print <<<Profile
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

		<style type="text/css">
			a {color:#00f; text-decoration:none;}
			a:hover {color:#f00; text-decoration:underline;}
			font, table.xe-warning {display:none!important;}

			body {font-family:verdana; margin:0px;}
			div.topWin {margin:auto; padding:20px; width:95%;}
			div.pro_block {border:1px #000 solid; box-shadow:4px 4px 4px #c0c0c0; min-width:25%; max-width:35%;}
			em.chars {font-size:12px;}

			img {display:block; margin-bottom:10px;}

			input[name="prosign"] {font-size:14px; margin:10px 0px; padding:10px; width:95%;}
			input[type="submit"],input[type="button"] {background-color:#fff; border:2px #000 solid; cursor:pointer; padding:10px; transition:background-color,border,color,0.25s;}
			input[type="submit"]:hover,input[type="button"]:hover {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}

			nav {height:65px;}
			nav ul:nth-child(1) {float:left; margin:5px 0px 10px 20px;}
			nav ul:nth-child(2) {float:right; margin:5px 10px 10px 0px;}
			nav ul li {list-style-type:none;}
			nav ul:nth-child(2) li {float:right; margin-right:10px;}
			nav ul li a {color:#000; display:inline-block; font-family:verdana; padding:10px 20px 10px 20px; text-decoration:none; transition:background,color,0.25s;}
			nav ul li a:hover {background-color:rgb(2,105,189); color:#fff; text-decoration:none!important;}

			table.prodata {width:100%;}
			table.prodata td {margin:1px 0px 1px 0px; padding:4px 2px 4px 2px;}
			table.prodata td:nth-child(odd) {background-color:rgb(150,150,150); width:35%;}
			table.prodata td:nth-child(even){background-color:rgb(220,220,220);}

			table.recent_posts {border:1px #000 solid; box-shadow:4px 4px 4px #c0c0c0; margin-top:30px; width:99%;}
			table.recent_posts td {background-color:rgb(220,220,220); padding:4px;}
			table.recent_posts td:nth-child(1) {width:25%;}
			table.recent_posts td:nth-child(2) {max-width:57%;}
			table.recent_posts td:nth-child(3) {text-align:center; width:18%;}
			table.recent_posts td.problue {background-color:rgb(2,105,189); color:#fff; font-weight:bold;}
			table.recent_posts td.problue:nth-child(3) {text-align:center;}

			table.recent_posts td.no_posts {font-style:italic; text-align:center;}
			
			textarea[name="prodesc"] {height:100px; font-family:arial; font-size:14px; margin-top:5px; overflow-y:hidden; padding:10px; width:95%;}
			
			@media (max-width:799px)
			{
				* {margin:0px; padding:0px;}
				div.topWin {margin:auto; padding:0px; width:100%;}
				div.pro_block {border-width:0px; box-shadow:4px 4px 4px #c0c0c0; min-width:25%; max-width:100%;}
				input[type="file"] {font-size:18px;}
				input[type="button"],input[type="submit"] {padding:10px 10px 10px 10px;}
				input[type="submit"]:active,input[type="button"]:active {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}
				nav ul li a {padding:10px;}
				nav ul li a:active {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}
				nav ul li:nth-child(3) a {display:none;}
				table.recent_posts {border-width:0px; border-spacing:0px; box-shadow:4px 4px 4px #c0c0c0; margin-top:30px; width:100%;}
				table.recent_posts tr td:nth-child(1),table.recent_posts tr td:nth-child(3) {display:none;}
				table.recent_posts tr td:nth-child(2) {text-align:center;}
				table.recent_posts td.no_posts {display:table-cell!important;}
			}
		</style>
		<title>$getTitle V89 Forum</title>
	</head>
	<body>
		<nav>
			<ul>
				<li><a href="about.html">About</a></li>
			</ul>
			<ul>
				<li>$navigationBar_1</li>
				<li><a href="index.html">Home</a></li>
				<li>$navigationBar_0</li>
			</ul>
		</nav>
		<div class="topWin">
			<div class="pro_block">
				<div style="background-color:rgb(2,105,189); color:#fff; font-size:18px; font-weight:bold; padding:5px;">
					$currentUser
				</div>
				<table class="prodata">
					<tr>
						<td>Status</td>
						<td>$userStatus</td>
					</tr>
					<tr>
						<td>Last Active</td>
						<td>$userLastActive</td>
					</tr>
					<tr>
						<td>Registered</td>
						<td>$userRegistrationDate</td>
					</tr>
				</table>
				$proImgDesc
			</div>
			<table class="recent_posts">
				<tr>
					<td class="problue" colspan="3">Recent posts by $currentUser</td>
				</tr>
				$recentPosts
			</table>
		</div>
	</body>
</html>
Profile;
		}
	}
?>