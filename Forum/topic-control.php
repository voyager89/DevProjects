<?php
	require_once "core.php";
	require_once "logger.php";
	require_once "user-settings/Ctrl-XML.php";

	class TopicControl extends Forum89
	{
		private $warning = "";
		private $farewellList = []; // when deleting a profile, this is needed to delete all posts made by said user

		private function markTableForDeletion()
		{
			$microtime = str_replace(" ", "_", microtime());
			return str_replace(".", "_", $microtime);
		}

		private function readSpecialChars($text) // Special formatting, for example [quote] - and preventing HTML
		{
			$output = "";
			$output = str_replace("<",'&lt;', $text);
			$output = str_replace(">",'&gt;', $output);
			$output = str_replace("[quote]",'<hr/><div class="quote">', $output);
			$output = str_replace("[/quote]",'</div><hr/>', $output);
			$output = str_replace("\n",'<br/>', $output);
			$output = str_replace("[laugh]",'HA', $output);
			
			return $output;
		}

		private function reverseReadSpecialChars($text) // Special formatting, for example <div class="quote"> to [quote]
		{
			$output = "";
			//$output = str_replace("<",'&lt;', $text);
			//$output = str_replace(">",'&gt;', $output);
			//$output = str_replace("[quote]",'<hr/><div class="quote">', $output);
			//$output = str_replace("[/quote]",'</div><hr/>', $output);
			$output = str_replace("<br/>","\n", $text);
			$output = str_replace("<br>","\n", $output);
			$output = str_replace("<hr/>","", $output);
			$output = str_replace('<div class="quote">','[quote]', $output);
			$output = str_replace('</div>','[/quote]', $output);
			//$output = str_replace("[laugh]",'HA', $output);
			
			return $output;
		}

		private function readMessageSpecialChars($text) // Convert any [icons] or [formatting] to HTML
		{
			$output = "";

			for ($x = 0; $x < strlen($text); ++$x) // Convert newline \n characters to <br/> (HTML)
				$output .= ($text[$x] == "\n" ? "<br/>\n" : $text[$x]);

			return $output;
		}
		
		private function showEditForm($topic, $comment)
		{		
			$replyData = [];

			$SQL = $this->fetchRecords("SELECT CMUser,CMTitle,CMData,CMDate FROM Topic_$topic WHERE CMID=$comment;");
			
			if ($SQL != false)
			{
				$SQL_RS = $SQL[0];
					
				array_push($replyData, $SQL_RS["CMTitle"], $SQL_RS["CMData"], $SQL_RS["CMDate"], $SQL_RS["CMUser"]);
				
				$postTitle = base64_decode($replyData[0]);
				$postMessage = base64_decode($replyData[1]);
				$topicData = explode("_", $topic);
				
				echo "    <div class=\"post_title\">\n";
				echo '      <div class="post_title title" style="font-weight:bold;">'.$postTitle.'</div>';
				echo "\n";
				echo '      <div class="post_title user">by <strong>'.$replyData[3].'</strong> on '.$replyData[2].'</div>';
				echo "\n";
				echo '      <div class="post_title actions"> </div>';
				echo "      <hr/>\n";
				echo '      <div class="post_title msg">'.$this->readMessageSpecialChars(base64_decode($replyData[1])).'</div>';
				echo "\n\n      <hr/>\n";
				echo '      <form action="edit-'.$topicData[0].'-'.$topicData[1].'-'.$comment.'.html" method="post">';
				echo "\n";
				echo '        <input id="postTitle" name="postTitle" type="text" value="'.$postTitle.'"><br/>';
				echo "\n";
				echo '        <textarea id="postMsg" name="postMsg">';
				echo "\n".$this->reverseReadSpecialChars($postMessage)."</textarea><br/><br/>";
				echo "\n      ".$this->warning."\n";
				echo '        <input type="submit" value="&lt;&lt; Edit Post &gt;&gt;"> <input onclick="window.history.back();" type="button" value="Cancel">';
				echo "\n\n";
				echo "      </form>";
				echo "\n    </div>\n	</body>\n</html>";
			}
			else {
				exit("<strong>This post cannot be found!!</strong>\n	</body>\n</html>");
			}
		}
		
		private function showReplyForm($topic, $comment)
		{
			$replySet = [];
			$topicData = explode("_", $topic);

			$SQL = $this->fetchRecords("SELECT UserSettings FROM UserData WHERE UserName='".$_SESSION["v89forum_logstat"]."';");
			$userSignature = $SQL[0]["UserSettings"]; // Get the user signature via user settings
			
			$profileData = [];
			$newXmlControl = new XML_Controller();
			$profileData = $newXmlControl->readDoc("user-settings/".$userSignature, "<profile>", ["<username>", "<picture>", "<description>", "<signature>"]);
			//$userProImg = $profileData[1];
			//$user_descri = $profileData[2];
			$userSign = $profileData[3];
			unset($profileData);

			$fetchComment = $this->fetchRecords("SELECT CMUser,CMTitle,CMData,CMDate FROM `Topic_$topic` WHERE CMID='$comment';");

			if ($fetchComment != false)
			{
				$SQL_RS = $fetchComment[0];
				
				array_push($replySet, $SQL_RS["CMTitle"], $SQL_RS["CMData"], $SQL_RS["CMDate"], $SQL_RS["CMUser"]);
				
				$postTitle = base64_decode($replySet[0]);
				$postTitle = strpos(strtolower($postTitle),"re: ") !== false ? $postTitle : "Re: ".$postTitle;
				$postData = $this->reverseReadSpecialChars(base64_decode($replySet[1]));
				
				// Below is disabled - value will NOT be trimmed;
				$trimPost = false; // Disabled. true will enabled it
				$postDataSizeTrim = (strlen($postData) > 100 ? 100 : strlen($postData));
				$trimmedPost = ($trimPost ? substr($postData, 0, $postDataSizeTrim) : $postData);
				
				echo "    <div class=\"post_title\">\n";
				echo '      <div class="post_title title" style="font-weight:bold;">'.$postTitle.'</div>';
				echo "\n";
				echo '      <div class="post_title user">by <strong>'.$replySet[3].'</strong> on '.$replySet[2].'</div>';
				echo "\n";
				echo '      <div class="post_title actions"> </div>';
				echo "      <hr/>\n";
				echo '      <div class="post_title msg">'.base64_decode($replySet[1]).'</div>';
				echo "\n\n      <hr/>\n";
				echo '      <form action="reply-'.$topicData[0].'-'.$topicData[1].'-'.$comment.'.html" method="post">';
				echo "\n";
				echo '        <input id="postTitle" name="postTitle" type="text" value="'.$postTitle.'"><br/>';
				echo "\n";
				echo '        <textarea id="postMsg" name="postMsg">[quote]'.trim($trimmedPost).'[/quote]';
				
				if (strlen(trim($userSign)) == 0)
				{
					echo "\n\n</textarea>";
				}
				else
				{
					echo "\n\n\n$userSign </textarea>";
				}
				
				echo "<br/><br/>\n      ".$this->warning."\n";
				echo '        <input type="submit" value="&lt;&lt; Post Reply Now &gt;&gt;">';
				echo "\n\n      </form>\n    </div>\n	</body>\n</html>";
			}
			else {
				exit("<strong>This post cannot be found!!</strong>\n	</body>\n</html>");
			}
		}
		
		private function getCssLayout($action)
		{
			switch($action)
			{
				case "edit":
					return <<<CssBlock
* {font-family:verdana;}
      		div.post_title {border:1px #000 solid; margin:auto; margin-bottom:10px; padding:2px; width:75%;}
      		div.post_title div.title {background-color:rgb(2,105,189); color:#fff; margin-bottom:10px; width:auto;}
      		div.post_title div.msg,div.post_title div.user {border-width:0px; margin-bottom:8px; padding:0px 10px 0px 10px; width:auto;}
      		div.post_title div.actions {display:none;}
			div.post_title div.user {display:inline-block;}
      		div.post_title div.actions {border-width:0px; float:right; width:100px;}
      		div.actions a {background-color:rgb(2,105,189); border:1px rgb(2,105,189) solid; color:#fff; padding:5px 10px 5px 10px;
				text-align-decoration:none; transition:background-color,border,color,0.5s;}
      		div.actions a:hover {background-color:rgb(42,145,229); border-color:rgb(42,145,229); color:#000;}

      		form {margin:auto; text-align:center;}
      		form input[type="text"] {font-size:18px; margin:10px; width:95%;}
      		form textarea {font-size:18px; height:200px; margin:10px; width:95%;}
			form input[type="submit"], form input[type="button"] {font-size:16px; margin-bottom:25px; width:47.5%;}

			input[type="submit"],input[type="button"] {background-color:#fff; border:2px #000 solid; cursor:pointer; padding:10px; transition:background-color,border,color,0.25s;}
			input[type="submit"]:hover,input[type="button"]:hover {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}

			nav {height:65px;}
			nav ul:nth-child(1) {float:left; margin:5px 0px 10px 20px;}
			nav ul:nth-child(2) {float:right; margin:5px 10px 10px 0px;}
			nav ul li {list-style-type:none;}
			nav ul:nth-child(2) li {float:right; margin-right:10px;}
			nav ul li a {color:#000; display:inline-block; font-family:verdana; padding:10px 20px 10px 20px; text-decoration:none; transition:background,color,0.25s;}
			nav ul li a:hover {background-color:rgb(2,105,189); color:#fff; text-decoration:none!important;}
			@media (max-width:799px)
			{
				* {margin:0px; padding:0px;}
				div.post_title {border-width:0px; width:98%;}
				form input[type="text"] {font-size:18px; margin:10px; width:90%;}
				form textarea {font-size:18px; height:200px; margin:10px; width:90%;}
				input[type="submit"],input[type="button"] {padding:5px 10px; width:90%!important;}

				input[type="submit"]:active,input[type="button"]:active {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}
			    nav ul li a {padding:10px;}
			    nav ul li a:active {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}
			    nav ul li:nth-child(3) a {display:none;}
			}
CssBlock;
				case "reply":
					return <<<CssBlock
* {font-family:verdana;}
		div.post_title {border:1px #000 solid; margin:auto; margin-bottom:10px; padding:2px; width:75%;}
		div.post_title div.title {background-color:rgb(2,105,189); color:#fff; margin-bottom:10px; width:auto;}
		div.post_title div.msg,div.post_title div.user {border-width:0px; margin-bottom:8px; padding:0px 10px 0px 10px; width:auto;}
		div.post_title div.actions, div.post_title div.user {display:inline-block;}
		div.post_title div.actions {border-width:0px; float:right; width:100px;}
		div.actions a {background-color:rgb(2,105,189); border:1px rgb(2,105,189) solid; color:#fff; padding:5px 10px 5px 10px;
		   text-decoration:none; transition:background-color,border,color,0.5s;}
		div.actions a:hover {background-color:rgb(42,145,229); border-color:rgb(42,145,229); color:#000;}

		form {margin:auto; text-align:center;}
		form input[type="text"] {font-size:18px; margin:10px; width:95%;}
		form textarea {font-size:18px; height:200px; margin:10px; width:95%;}
		form input[type="submit"] {font-size:16px; margin-bottom:25px; width:75%;}

		input[type="submit"],input[type="button"] {background-color:#fff; border:2px #000 solid; cursor:pointer; padding:10px; transition:background-color,border,color,0.25s;}
				input[type="submit"]:hover,input[type="button"]:hover {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}

		nav {height:65px;}
		nav ul:nth-child(1) {float:left; margin:5px 0px 10px 20px;}
		nav ul:nth-child(2) {float:right; margin:5px 10px 10px 0px;}
		nav ul li {list-style-type:none;}
		nav ul:nth-child(2) li {float:right; margin-right:10px;}
		nav ul li a {color:#000; display:inline-block; font-family:verdana; padding:10px 20px 10px 20px; text-decoration:none; transition:background,color,0.25s;}
		nav ul li a:hover {background-color:rgb(2,105,189); color:#fff; text-decoration:none!important;}
		
		@media (max-width:799px)
		{
			* {margin:0px; padding:0px;}
			div.post_title {background-color:#fff; border-width:0px; margin:auto; margin-bottom:10px; padding:2px; width:99%;}
			div.post_title div.title {background-color:rgb(2,105,189); color:#fff; margin-bottom:10px; width:auto;}
			div.post_title div.msg,div.post_title div.user {border-width:0px; margin-bottom:8px; padding:0px 10px 0px 10px; width:auto;}
			div.post_title div.actions {display:none;}
			div.post_title div.user {display:inline-block;}
			div.post_title div.actions {border-width:0px; float:right; width:100px;}
			div.actions a {background-color:rgb(2,105,189); border:1px rgb(2,105,189) solid; color:#fff; padding:5px 10px 5px 10px;
				  text-decoration:none; transition:background-color,border,color,0.5s;}
			div.actions a:hover {background-color:rgb(42,145,229); border-color:rgb(42,145,229); color:#000;}

			form {margin:auto; text-align:center;}
			form input[type="text"] {font-size:18px; margin:10px; width:90%;}
			form textarea {font-size:18px; height:200px; margin:10px; width:90%;}
			form input[type="submit"] {font-size:16px; margin-bottom:25px; padding:10px; width:92%;}

			input[type="submit"]:active,input[type="button"]:active {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}
			nav ul li a {padding:10px;}
			nav ul li a:active {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}
			nav ul li:nth-child(3) a {display:none;}
		}
CssBlock;
			}
		}
		
		private function runHtmlTemplate($action)
		{
			$menuLogStatus_0 = UserLogger::navLogger(0);
			$menuLogStatus_1 = UserLogger::navLogger(1);
			
			$cssBlock = $this->getCssLayout($action);
			$title = ($action == "edit" ? "Edit a post" : "Reply to a post");
			
			print <<<TopicControl
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<style type="text/css">
			$cssBlock
    	</style>
    	<title>$title</title>
    </head>
    <body>
        <nav>
			<ul>
    			<li><a href="about.html">About</a></li>
  			</ul>
  			<ul>
				<li>$menuLogStatus_1</li>
			    <li><a href="index.html">Home</a></li>
			    <li>$menuLogStatus_0</li>
  			</ul>
		</nav>

TopicControl;
		}
		
		private function doEditProcess($action, $params) // A-9-1
		{
			$redirection = "";

			$particulars = explode("-", $params);
			$topic = $particulars[0]."_".$particulars[1];
			$comment = $particulars[2];

			if (isset($_SESSION["v89forum_logstat"])) // Are you logged on?
			{
				if (!isset($_POST["postTitle"]) && !isset($_POST["postMsg"]))
				{
					$this->runHtmlTemplate($action);
					$this->showEditForm($topic, $comment);
				}
				else if (isset($_POST["postTitle"]) && isset($_POST["postMsg"])) // Updating reply
				{
					$postCheck = true;
					$postMessage = $this->readSpecialChars($_POST["postMsg"]);
					$postTitle = $_POST["postTitle"];
					
					// Determine which topic is being referenced
					switch (strtoupper($particulars[0]))
					{
						case "A": $redirection = "astronomy"; 		break;
						case "B": $redirection = "3d_modelling"; 	break;
						case "C": $redirection = "cs_php"; 			break;
						case "D": $redirection = "aliens_ufo"; 		break;
					}

					// Start checks
					if (strlen($postTitle) < 3 || strlen($postTitle) > 40 || strlen(trim($postTitle)) == 0)
					{
						$postCheck = false;
						$this->warning .= "\t Topic title must be between 9 - 41 characters!<br/>\n";
					}
					if (strlen($postMessage) < 2 || strlen($postMessage) > 3000 || strlen(trim($postMessage)) == 0)
					{
						$postCheck = false;
						$this->warning .= "\t Content must be between 9 and 3001 characters!<br/><br/>\n\n";
					}

					if ($postCheck == true)
					{
						// Should TopicList Title be updated?
						$SQL = $this->fetchRecords("SELECT TopicTitle FROM `TopicList` WHERE Category='".$particulars[0]."' AND TopicID=".$particulars[1].";");
						$existingTitle = ($SQL != false ? $SQL[0]["TopicTitle"] : false);
						
						if (strcmp($existingTitle, $postTitle) != 0 && $particulars[2] == 1) // TopicList will not be updated unless it's the original poster
						{
							$this->modifyRecords("UPDATE TopicList SET TopicTitle='".base64_encode($postTitle)."' WHERE Category='".$particulars[0]."' AND TopicID=".$particulars[1].";");
						}

						// Update post history
						$userRow = $this->fetchRecords("SELECT UserID FROM UserData WHERE UserName='".$_SESSION["v89forum_logstat"]."';");
						$date = $this->fetchRecords("SELECT CMDate FROM `Topic_$topic` WHERE CMID=".$particulars[2].";");
						
						$updateHistory = true;
						
						if (strcmp($existingTitle, base64_encode($postTitle)) != 0)
						{
							$updateHistory = $this->modifyRecords("UPDATE `UserPostHistory_User_".$userRow[0]["UserID"]."` SET Title='".base64_encode($postTitle)."' WHERE CategoryIndex='$topic' AND CommentDate='".$date[0]["CMDate"]."';");
						}

						// Update the post
						$updatePost = $this->modifyRecords("UPDATE `Topic_$topic` SET CMTitle='".base64_encode($postTitle)."',CMData='".base64_encode($postMessage)."',IsCMUpdated=1 WHERE CMUser='".$_SESSION["v89forum_logstat"]."' AND CMID=".$particulars[2]." AND Status <> 0;");
						
						if ($updatePost != false && $updateHistory != false) // It worked!
						{
							$redirection .= "-".$particulars[1];
						}
						else { // Updating the post didn't work, so let's refresh the page
							$redirection = "edit-".$particulars[0]."-".$particulars[1]."-".$particulars[2];
						}

						header("location: $redirection.html");
					}
					else {
						$this->runHtmlTemplate($action);
						$this->showEditForm($topic, $comment);
					}
				}
			}
			else {
				header("location: index.html");
			}
		}
		
		private function doReplyProcess($action, $params)
		{
			$redirection = "";

			$particulars = explode("-", $params);
			$topic = $particulars[0]."_".$particulars[1];
			$comment = $particulars[2];
			
			if (!isset($_SESSION["v89forum_logstat"]))
			{
				header("location: index.html");
			}
			else if (!isset($_POST["postTitle"]) && !isset($_POST["postMsg"])) // Replying to a post
			{
				$SQL = $this->fetchRecords("SELECT CMUser FROM `Topic_$topic` WHERE CMID=$comment;");
				
				if (strcmp($_SESSION["v89forum_logstat"],$SQL[0]["CMUser"]) != 0) // You cannot reply to your own comment
				{
					$SQL = $this->fetchRecords("SELECT * FROM `Topic_$topic` WHERE CMID=$comment AND Status=1;");

					if ($SQL != false)
					{
						$this->runHtmlTemplate($action);
						$this->showReplyForm($topic, $comment);
					}
					else {
						header("location: 404.html"); // Cannot reply to a deleted/non-existent post
					}
				}
				else {
					header("location: index.html");
				}
			}
			else if (isset($_POST["postTitle"]) && isset($_POST["postMsg"])) // Submitting a reply - checking first
			{
				$postMessage = $this->readSpecialChars($_POST["postMsg"]); // Read special characters if any
				$postTitle = $_POST["postTitle"];
				$postCheck = true;
				
				if (strlen($postTitle) < 3 || strlen($postTitle) > 40 || strlen(trim($postTitle)) == 0)
				{
					$postCheck = false;
					$this->warning .= "\t Topic title must be between 9 - 41 characters!<br/>\n";
				}
				
				if (strlen($postMessage) < 2 || strlen($postMessage) > 3000 || strlen(trim($postMessage)) == 0)
				{
					$postCheck = false;
					$this->warning .= "\t Content must be between 9 and 3001 characters!<br/><br/>\n\n";
				}
				
				if ($postCheck == true) // Checks are good, post the reply
				{
					$now = date("Y-m-d H:i:s");
					
					$userID = $this->fetchRecords("SELECT UserID FROM UserData WHERE UserName='".$_SESSION["v89forum_logstat"]."';");
					$updatePost = $this->modifyRecords("INSERT INTO `Topic_$topic`(CMUser,CMTitle,CMData,CMDate,IsCMUpdated,ReplyTo,Status) VALUES('".$_SESSION["v89forum_logstat"]."', TRIM('".base64_encode($this->applyFilters($postTitle))."'), TRIM('".base64_encode($this->applyFilters($postMessage))."'), NOW(), 0, 1, 1);");

					$getUserID = $userID != false ? $userID[0]["UserID"] : 0;
					$updatePostHistory = $this->modifyRecords("INSERT INTO `UserPostHistory_User_$getUserID` VALUES(NOW(),'$topic',TRIM('".base64_encode($this->applyFilters($postTitle))."'));");

					if ($updatePost == true && $updatePostHistory == true)
					{						
						$SQL = $this->fetchRecords("SELECT COUNT(*) AS TabRecs FROM `Topic_$topic`;");

						$newReplyNumber = intval($SQL[0]["TabRecs"]); // Get total number of records in this table
						
						$SQL = $this->modifyRecords("UPDATE UserData SET LastActive=NOW() WHERE UserName='".$_SESSION["v89forum_logstat"]."';");
				  
						// Determine which topic is being referenced
						switch (strtoupper($particulars[0]))
						{
							case "A": $redirection = "astronomy"; 		break;
							case "B": $redirection = "3d_modelling"; 	break;
							case "C": $redirection = "cs_php"; 			break;
							case "D": $redirection = "aliens_ufo"; 	break;
						}

						header("location: ".$redirection."-".$particulars[1].".html");
					}
					else { // Inserting the reply didn't work, let's refresh the page
						header("location: reply-".$particulars[0]."-".$particulars[1]."-".$particulars[2].".html");
					}
				}
				else {
					$this->runHtmlTemplate($action);
					$this->showReplyForm($topic, $comment);
				}
			}
			else
			{
				header("location: index.html");
			}
		}
		
		public function __construct($action, $params = null) // TopicControl("edit", "A-9-1"); // reply-A-1-1 // edit-A-9-1
		{
			switch ($action)
			{
				case "delete":
					if (isset($_SESSION["v89forum_logstat"]))
					{
						$redirection = "";

						$particulars = explode("-", $params);
						$topic = $particulars[0]."_".$particulars[1];
						$comment = $particulars[2];
						
						// Determine which topic is being referenced
						switch (strtoupper($particulars[0]))
						{
							case "A": $redirection = "astronomy"; 		break;
							case "B": $redirection = "3d_modelling"; 	break;
							case "C": $redirection = "cs_php"; 			break;
							case "D": $redirection = "aliens_ufo";	 	break;
						}

						// Remove from user post history
						$commentDate = $this->fetchRecords("SELECT CMDate FROM `Topic_$topic` WHERE CMID=$comment;");
						$userID = $this->fetchRecords("SELECT UserID FROM UserData WHERE UserName='".$_SESSION["v89forum_logstat"]."';");
						$this->modifyRecords("DELETE FROM `UserPostHistory_User_".$userID[0]["UserID"]."` WHERE CategoryIndex='$topic' AND CommentDate='".$commentDate[0]["CMDate"]."';");

						// Multi query
						$SQL_C = new mysqli("","","","") or die("Could not connect to the database due to ".$SQL_C->connect_error);
						
						// Verify that the user is deleting his/her own post
						$SQL_GO = $SQL_C->query("SELECT CMUser FROM `Topic_$topic` WHERE CMID=$comment;");

						if (strcmp($_SESSION["v89forum_logstat"], $SQL_GO->fetch_assoc()["CMUser"]) == 0)
						{
							// Yes, this user wants to delete his/her post
							// You cannot delete a post that belongs to someone else!
							
							//--- If CommentID is 1 then delete everything in the table, mark it as DEL and delete all this table's comments from TopicList
							if ($comment == 1)
							{
								$SQL_C->query("DELETE FROM `Topic_$topic`;");
								$SQL_C->query("RENAME TABLE `Topic_$topic` TO `TB_DEL".$this->markTableForDeletion()."`;");
								$SQL_C->query("DELETE FROM `TopicList` WHERE Category='".$particulars[0]."' AND TopicID=".$particulars[1].";");
							}
							else {
								$SQL_C->query("UPDATE `Topic_$topic` SET CMData='".base64_encode("This message has been deleted by the poster")."', CMTitle='".base64_encode("[Deleted]")."', Status=0 WHERE CMUser='".$_SESSION["v89forum_logstat"]."' AND CMID=$comment;");
								$SQL_C->query("DELETE FROM TopicList WHERE ByUser='".$_SESSION["v89forum_logstat"]."' AND CommentID=$comment AND Category=UPPER('".$particulars[0]."') AND TopicID=".$particulars[1].";");
							}
						}
						
						$SQL_C->close();
						
						header("location: $redirection.html");
					}
					else
					{
						header("location: index.html");
					}
				break;
				case "delete_acc":
					if (!isset($_SESSION["v89forum_logstat"]))
					{
						header("location: index.html");
					}
					else
					{
						if (isset($_POST["confirmDelete"])) // Confirmation flag posted from Profile
						{
							$topic_C = [];
							$topicID = [];
							$topic_T = [];

							// Fetch settings file
							$SQL = $this->fetchRecords("SELECT UserSettings FROM UserData WHERE UserName='".$_SESSION["v89forum_logstat"]."';");
							$profileData = [];

							$newXmlControl = new XML_Controller();
							$userData = $SQL[0]["UserSettings"];
							//$profileData = $newXmlControl->readDoc("user-settings/$userData","<profile>",["<username>","<picture>","<description>","<signature>"]);
							$userProImg = $profileData[1];
							$userSettingsFile = "user-settings/$userData";
							
							if (strlen($userProImg) > 3 && file_exists($userProImg))
							{
								unlink("user-pics/$userProImg"); // Delete the profile picture
							}
							
							// Destroy profileData variable
							unset($profileData);
							
							// Delete the settings file
							if (file_exists($userSettingsFile))
							{
								unlink($userSettingsFile);
							}

							// Remove user post history
							$userID = $this->fetchRecords("SELECT UserID FROM UserData WHERE UserName='".$_SESSION["v89forum_logstat"]."';");
							
							$tableList = $this->fetchRecords("SELECT CategoryIndex FROM `UserPostHistory_User_".$userID[0]["UserID"]."`;");
							
							if ($tableList != false)
								for ($i = 0; $i < sizeof($tableList); ++$i)
									if (in_array($tableList[$i]["CategoryIndex"], $this->farewellList) == false)
										array_push($this->farewellList, $tableList[$i]["CategoryIndex"]);

							for ($j = 0; $j < sizeof($this->farewellList); ++$j)
							{
								$this->modifyRecords("UPDATE `Topic_".$this->farewellList[$j]."` SET CMTitle=TO_BASE64('[Deleted]'), CMData=TO_BASE64('This message has been deleted by an administrator'), Status=0 WHERE CMUser='".$_SESSION["v89forum_logstat"]."';");
							}
							
							$this->modifyRecords("DROP TABLE `UserPostHistory_User_".$userID[0]["UserID"]."`;");

							// Remove user from UserData
							$SQL = $this->modifyRecords("UPDATE UserData SET FirstName='[Deleted]', LastName='[Deleted]', UserMail='[Deleted]', UserPWD='[Deleted]', UserGender='0', UserSettings='[Deleted]', UserStatus=0 WHERE UserName='".$_SESSION["v89forum_logstat"]."';");

							//  Category  |  TopicID  |  TopicTitle  |  ByUser  
							$SQL = $this->fetchRecords("SELECT Category AS Cat,TopicID AS TopID,TopicTitle AS Top_T FROM TopicList WHERE ByUser='".$_SESSION["v89forum_logstat"]."';");
							
							if ($SQL != false)
							{
								for ($i = 0; $i < sizeof($SQL); ++$i)
								{
									$SQL_RS = $SQL[$i];

									array_push($topic_C, $SQL_RS["Cat"]);
									array_push($topicID, $SQL_RS["TopID"]);
									array_push($topic_T, $SQL_RS["Top_T"]);
								}
							}

							for ($j = 0; $j < sizeof($topic_C); ++$j)
								$SQL = $this->modifyRecords("UPDATE Topic_".$topic_C[$j]."_".$topicID[$j]." SET CMUser='[Deleted]', CMTitle='".base64_encode("[Deleted]")."', CMData='".base64_encode("[Deleted]")."', CMDate='[Deleted]', Status=0 WHERE CMUser='".$_SESSION["v89forum_logstat"]."';");

							$SQL = $this->modifyRecords("DELETE FROM TopicList WHERE ByUser='".$_SESSION["v89forum_logstat"]."';");
							
							session_unset();
							session_destroy();
							
							header("location: index.html");
						}
					}
				break;
				case "edit":
					$this->doEditProcess($action, $params);
				break;
				case "reply":
					$this->doReplyProcess($action, $params);
				break;
			}
		}
	}
?>