<?php
	require_once "core.php";
	require_once "logger.php";
	require_once "user-settings/Ctrl-XML.php";

	class TopicsList extends Forum89
	{
		private $topicTitle = "";

		private $currentFile = "";
		
		private $isLoggedOn = null;

		private function goToIndexPage($index)
		{
			$message = "";
			$to_location = "";
			
			if (gettype($index) == "integer")
			{
				$to_location = "index.html";
				$message = !$index ? 'Page not found</h1><h3>Returning to index</a...' : 'You\'re not logged in</h1><h3>Redirecting you back to <a href="index.html">index</a> page...';
			}
			else {
				$message = 'Message successfully posted!</h1><h3>';
				$to_location = $index;
			}
			
			echo "<h1 class='output'>$message</h3>\n\n";
			echo '<script type="text/javascript"> window.setTimeout("window.location.href=\''.$to_location.'\';",3000); </script>';
			exit;
		}

		private function showMessagePoster($postData)
		{
			$current_file = $this->currentFile.".html";
			
			$SQL = $this->fetchRecords("SELECT UserSign FROM UserData WHERE UserName='".$this->isLoggedOn."';");

			$userSign = base64_decode($SQL[0]["UserSign"]);
			
			print <<<postBox
<form action="$current_file" class="poster" method="post" style="margin:auto;">
	<em style="color:#f00;">
		$postData
	</em><br/>
	<label for="topictitle">Topic title <em>(max 40 characters)</em>:</label>
	<br/><br/>

	<input id="topictitle" name="topictitle" type="text" value="">
	<br/><br/><br/>

	<label for="topiccontent">Your message <em>(max 3000 characters)</em>:</label>
	<br/><br/>

	<textarea id="topiccontent" name="topiccontent" rows="10">

	$userSign </textarea>
	<br/><br/>

	<input style="padding:10px;" type="submit" value="Post">
</form>
postBox;
		}

		private function readMessageSpecialCharacters($data) // Convert any [icons] or [formatting] to HTML
		{
			$output = "";
			
			for ($x = 0; $x < strlen($data); $x++) // Convert newline \n characters to <br/> (HTML)
				$output .= $data[$x] == "\n" ? "<br/>\n" : $data[$x];
			
			return $output;
		}

		private function showTopicReplies($TopicID, $topicCategory, $ReplyTo, $SQL_Query) // Display replies (if any) of topic comments
		{
			$commentID = 0;

			$topicTitle = $this->topicTitle;

			$SQL = $this->fetchRecords("SELECT $SQL_Query,DATE_FORMAT(CMDate,'%a %d %M %Y %H:%i:%s') AS CMDate,CMUser FROM Topic_$TopicID"."_"."$topicCategory WHERE ReplyTo=$ReplyTo ORDER BY CMDate DESC;");
			
			if ($SQL != false)
			{
				for ($i = 0; $i < sizeof($SQL); ++$i)
				{
					$row = $SQL[$i];

					$commentID = $row["CMID"];
					
					echo "<div class=\"post_title\" style=\"margin-left:43%; width:50%;\">\n";
					echo '      <div class="post_title title" style="font-weight:bold; width:98.5%!important;">'. base64_decode($row["CMTitle"]) .'</div>';
					echo "\n";
					echo '      <div class="post_title user"><img alt="Profile picture" src="'. $this->showTabData(4, $row["CMUser"]) .'"/>  <div class="uName">by <a href="profile-user-'. $this->showTabData(3, $row["CMUser"]) .'.html" id="post'. $row["CMID"] .'">'. $row["CMUser"] .'</a> on '. $row["CMDate"] .'</div></div>';
					echo "\n";

					if (strcasecmp($row["CMUser"], $this->isLoggedOn) == 0) // It's a match! (Display Edit button for user's own posts)
					{
						echo "\n\t\t";
						echo '      <div class="post_title actions"><a href="delete-'.$TopicID.'-'.$topicTitle.'-'.$row["CMID"].'.html" onclick="window.delPost(this); return false;">Delete</a></div>';
						echo "\n\t\t";
						echo '      <div class="post_title actions"><a href="edit-'.$TopicID.'-'.$topicTitle.'-'.$row["CMID"].'.html">Edit</a></div>';
						echo "\t\t";
						//echo '      <div class="post_title actions"><a href="reply-'.$TopicID.'-'.$topicTitle.'-'.$row["CMID"].'.html">Reply</a></div>';
					}
					else {
						echo "\t\t";
						echo '      <div class="post_title actions"><a href="reply-'.$TopicID.'-'.$topicTitle.'-'.$row["CMID"].'.html">Reply</a></div>';
					}
					
					echo "\n\t\t      ";
					
					if ($row["IsCMUpdated"] == 1)
						echo '<div class="post_title" style="border-width:0px; margin:0px; padding-left:10px;">(post updated)</div>';
				
					echo "\n\n";
					echo "<hr/>\n";
					echo '      <div class="post_title msg">'.$this->readMessageSpecialCharacters(base64_decode($row["CMData"])).'</div>';
					echo "\n    </div>";
				}
				
				showTopicReplies($TopicID, $topicCategory, $commentID, $SQL_Query); // This will continue displaying all replies to this post
			}
		}

		private function showTabData($data_required, $tab_index)
		{
			switch ($data_required)
			{
				case 1:
					$SQL = $this->fetchRecords("SELECT COUNT(*) AS tot_rec FROM `Topic_$tab_index` WHERE NOT ReplyTo=0 AND Status=1;");
					return $SQL[0]["tot_rec"];
				case 2:
				  $SQL = $this->fetchRecords("SELECT CMDate AS LastPosted FROM `Topic_$tab_index` ORDER BY CMDate DESC LIMIT 1;");

				  return $this->isPostRecent($SQL[0]["LastPosted"]);
				case 3:
				  $SQL = $this->fetchRecords("SELECT UserID FROM ExistingUserNames WHERE UserName='$tab_index';");

				  return $SQL[0]["UserID"];
				case 4: // Getting profile picture
					$SQL = $this->fetchRecords("SELECT UserSettings,UserGender FROM UserData WHERE UserName='$tab_index';");

					$userData = $SQL[0];
					
					if (strpos($userData["UserSettings"],".xml") !== false)
					{
						$NX = new XML_Controller();
						$userSettings = $NX->readDoc("user-settings/".$userData["UserSettings"], "<profile>", ["<username>", "<picture>", "<description>", "<signature>"]);
						
						if (strlen($userSettings[1]) > 2)
							return $userSettings[1];
						else
							return $userData["UserGender"] == "M" ? "pro-male.jpg" : "pro-female.jpg";
					}
					else
					{
						return $userData["UserGender"] == "M" ? "pro-male.jpg" : "pro-female.jpg";
					}
				break;
			}
		}
		
		private function fetchTopicTitle($title, $topicID)
		{
			if (is_numeric($title))
			{			
				$SQL = $this->fetchRecords("SELECT TopicTitle FROM TopicList WHERE Category='$topicID' AND TopicID=$title;");
				
				if ($SQL != false)
					return ">> ".base64_decode($SQL[0]["TopicTitle"]);
			}
		}
		
		public function __construct($topicID, $currentTopic, $currentFile, $params = null) // also get log data from navLogger
		{
			$this->currentFile = $currentFile;

			$this->isLoggedOn = $_SESSION["v89forum_logstat"] ?? false;
			$this->topicTitle = $topicTitle = ($params != "post" ? $params : false);

			$TP_css = !$topicTitle ? "75" : "86";
		
			$cssFile = $currentFile.".css";

			$postButton = ($this->isLoggedOn && !$topicTitle ? '<a class="nv" href="'.$currentFile.'-post.html">NEW POST</a>' : "");
			$topicData = $topicTitle ? $this->fetchTopicTitle($topicTitle, $topicID) : "";

			$menuLogStatus_0 = UserLogger::navLogger(0);
			$menuLogStatus_1 = UserLogger::navLogger(1);

			$thisFile = $currentFile.".html";

			print <<<DOC_TOP
<!DOCTYPE html>
<html lang="en">
	<head>
		<link href="$cssFile" rel="stylesheet" type="text/css"/>
		
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		
		<script type="text/javascript">
			function delPost(element)
			{
				if (window.confirm("Are you sure you want to delete this post?"))
					window.location.href = element.getAttribute("href");
				else
					return false;
			}
		</script>

		<style type="text/css">
			h1.output, h3 {text-align:center; text-decoration:none;}
		</style>

		<title>$currentTopic - V89 Forum</title>
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
		<h2 style="font-size:20px; width:100%;"><a href="/en/forum/$thisFile">$currentTopic</a> $topicData</h2>
		
		<hr class='doc_title'/>
		
		$postButton
DOC_TOP;

			// Post checking starts here
			if (!is_null($params) && $params == "post" && !$topicTitle) // Requesting to post a message
			{
				if ($this->isLoggedOn) // Are you logged in?
				{
					$this->showMessagePoster(null);
				}
				else // No? Then go back and sign in, or register
				{
					$this->goToIndexPage(1);
				}
			}
			else if ($topicTitle) // Viewing a topic
			{
				if (is_numeric($topicTitle) && $topicTitle > 0)
				{
					// First, check to see if this topic exists
					$SQL = $this->fetchRecords("SELECT TABLE_NAME AS TabName FROM information_schema.TABLES WHERE TABLE_NAME='Topic_".$topicID."_".$topicTitle."';");
					
					if ($SQL != false) // Found something!
					{
						// Topic title is here TPID
						$subSQL = $this->fetchRecords("SELECT CMID,CMUser,CMTitle,CMData,DATE_FORMAT(CMDate,'%a %d %M %Y %H:%i:%s') AS CMDate,IsCMUpdated FROM Topic_".$topicID."_$topicTitle WHERE CMID=1;");

						if ($subSQL != false)
						{
							$row = $subSQL[0];

							echo "<div class=\"post_title\" id=\"c" .$row["CMID"]. "\" style=\"width:86%;\">\n";
							echo '      <div class="post_title title" style="font-weight:bold;">'. base64_decode($row["CMTitle"]). '</div>';

							// Check if this post is marked as deleted
							if (strpos(strtolower(base64_decode($row["CMTitle"])), "deleted]") === false)
							{
								echo "\n";
								echo '      <div class="post_title user"><img alt="Profile picture" src="' .$this->showTabData(4, $row["CMUser"]). '"/> <div class="uName">by <a href="profile-user-' .$this->showTabData(3, $row["CMUser"]). '.html" id="post' .$row["CMID"]. '">' .$row["CMUser"]. '</a> on ' .$row["CMDate"]. '</div></div>';
								echo "\n";
							  
								if ($this->isLoggedOn && strcasecmp($row["CMUser"], $this->isLoggedOn) == 0) // It's a match! (Display Edit button for user's own posts)
								{
									echo "\n\t\t";
									echo '      <div class="post_title actions"><a href="delete-'.$topicID.'-'.$topicTitle.'-'.$row["CMID"].'.html" onclick="window.delPost(this); return false;">';
									echo 'Delete</a></div>';
									echo "\n\t\t";
									echo '      <div class="post_title actions"><a href="edit-'.$topicID.'-'.$topicTitle.'-'.$row["CMID"].'.html">Edit</a></div>';
									echo "\t\t";
									//echo '      <div class="post_title actions"><a href="reply-'.$topicID.'-'.$topicTitle.'-'.$row["CMID"].'.html">Reply</a></div>';
								}
								else {
									echo "\t\t";
									echo '      <div class="post_title actions"><a href="reply-'.$topicID.'-'.$topicTitle.'-'.$row["CMID"].'.html">Reply</a></div>';
								}
							  
								echo "\n\t\t      ";
							  
								if ($row["IsCMUpdated"] == 1)
									echo '<div class="post_title" style="border-width:0px; margin:0px; padding-left:10px;">(post updated)</div>';
							}

							echo "\n\n";
							echo "<hr/>\n";
							echo '      <div class="post_title msg">'. $this->readMessageSpecialCharacters(base64_decode($row["CMData"])) .'</div>';
							echo "\n    </div>";

							// The discussion starts here (every post will be checked to see if it contains responses to it)
							$subSQL2 = $this->fetchRecords("SELECT CMID,CMUser,CMTitle,CMData,DATE_FORMAT(CMDate,'%a %d %M %Y %H:%i:%s') AS CMDate,IsCMUpdated,Status FROM Topic_".$topicID."_$topicTitle WHERE CMID>=2 AND ReplyTo=1 ORDER BY CMDate DESC;");

							if ($subSQL2 != false)
							{
								for ($x = 0; $x < sizeof($subSQL2); ++$x)
								{
									$subRow = $subSQL2[$x];
									
									echo "<div class=\"post_title\" id=\"c". $subRow["CMID"] ."\" style=\"margin-left:18%;\">\n";

									//if(strpos(strtolower(base64_decode($subRow["CMTitle"])),"deleted]")==false)
									if ($subRow["Status"] == 1)
									{
										echo '      <div class="post_title title" style="font-weight:bold;">'.base64_decode($subRow["CMTitle"]).'</div>';
										echo "\n";
										echo '      <div class="post_title user"><img alt="Profile picture" src="'.$this->showTabData(4,$subRow["CMUser"]).'"/>  <div class="uName">by <a href="profile-user-'.$this->showTabData(3,$subRow["CMUser"]).'.html" id="post'.$subRow["CMID"].'">'.$subRow["CMUser"].'</a> on '.$subRow["CMDate"].'</div></div>';
										echo "\n";
										  
										if ($this->isLoggedOn && strcasecmp($subRow["CMUser"], $this->isLoggedOn) == 0) // It's a match! (Display Edit button for user's own posts)
										{
											echo "\n\t\t";
											echo '      <div class="post_title actions"><a href="delete-'.$topicID.'-'.$topicTitle.'-'.$subRow["CMID"].'.html" onclick="window.delPost';
											echo '(this); return false;">Delete</a></div>';
											echo "\n\t\t";
											echo '      <div class="post_title actions"><a href="edit-'.$topicID.'-'.$topicTitle.'-'.$subRow["CMID"].'.html">Edit</a></div>';
											echo "\t\t";
											//echo '      <div class="post_title actions"><a href="reply-'.$topicID.'-'.$topicTitle.'-'.$subRow["CMID"].'.html">Reply</a></div>';
										}
										else
										{
											echo "\t\t";
											echo '      <div class="post_title actions"><a href="reply-'.$topicID.'-'.$topicTitle.'-'.$subRow["CMID"].'.html">Reply</a></div>';
										}

										echo "\n\t\t      ";
										  
										if ($subRow["IsCMUpdated"] == 1)
											echo '<div class="post_title" style="border-width:0px; margin:0px; padding-left:10px;">(post updated)</div>';
									}
									else
									{
										echo '      <div class="post_title title" style="font-weight:bold;">[Post Deleted]</div>';
									}

									echo "\n\n";
									echo "<hr/>\n";
									echo '      <div class="post_title msg">'.$this->readMessageSpecialCharacters(base64_decode($subRow["CMData"])).'</div>';
									echo "\n    </div>";

									$this->showTopicReplies($topicID, $topicTitle, $subRow["CMID"], "CMID,CMUser,CMTitle,CMData,IsCMUpdated");
								}
							}
						}
						else
						{
							$this->goToIndexPage(0);
						}
					}
					else
					{
						$this->goToIndexPage(0); // Topic not found!
					}
				}
				else
				{
					$this->goToIndexPage(0);
				}
			}
			else if (isset($_POST["topictitle"]) && isset($_POST["topiccontent"]) && !$topicTitle /*&& !isset($_GET["reply"]) && !isset($_GET["to"])*/) // Posting a message begins here
			{
				$post_result = "";
				$post_check = true;
				
				if (strlen($_POST["topictitle"]) < 3 || strlen($_POST["topictitle"]) > 40 || strlen(trim($_POST["topictitle"])) == 0)
				{
					$post_check = false;
					$post_result .= "Topic title must be between 9 - 41 characters!<br/>\n";
				}
				
				if (strlen($_POST["topiccontent"]) < 2 || strlen($_POST["topiccontent"]) > 3000 || strlen(trim($_POST["topiccontent"])) == 0)
				{
					$post_check = false;
					$post_result .= "\t\t\t\tContent must be between 9 and 3001 characters!";
				}
				
				if ($post_check == true) // All good to post the message!
				{
					// $t_day=date("Y-m-d H:i:s");
					$total_records = 0;

					$SQL = $this->fetchRecords("SELECT MAX(TopicID) AS BiggestTopicID FROM `TopicList` WHERE Category='$topicID';");
					
					if ($SQL != false)
						$total_records = intval($SQL[0]["BiggestTopicID"]); // Convert result to integer with intval() if necessary

					$total_records++; // echo gettype($total_records);

					$SQL = $this->fetchRecords("CREATE TABLE Topic_".$topicID."_".$total_records."(CMID INT AUTO_INCREMENT PRIMARY KEY NOT NULL,CMUser VARCHAR(15) NOT NULL,CMTitle VARCHAR(50) NOT NULL,CMData VARCHAR(4000) NOT NULL,CMDate DATETIME NOT NULL,IsCMUpdated INT NOT NULL,ReplyTo INT NOT NULL,Status INT NOT NULL);"); // Create the table (new post)

					// Insert the Original Post
					$SQL = $this->modifyRecords("INSERT INTO Topic_".$topicID."_".$total_records."(CMUser,CMTitle,CMData,CMDate,IsCMUpdated,ReplyTo,Status) VALUES ('".$this->isLoggedOn."',TRIM('".base64_encode($this->applyFilters($_POST["topictitle"]))."'),TRIM('".base64_encode($this->applyFilters($_POST["topiccontent"]))."'),NOW(),0,0,1);");

					// Insert a record of this in the TopicList table
					$SQL = $this->modifyRecords("INSERT INTO TopicList (Category,TopicID,TopicTitle,ByUser,CommentID) VALUES ('$topicID','$total_records',TRIM('".base64_encode($this->applyFilters($_POST["topictitle"]))."'),'".$this->isLoggedOn."',1);");
					
					// Update User's post history
					$UD = $this->fetchRecords("SELECT UserID FROM UserData WHERE UserName='".$this->isLoggedOn."';");
					$SQL = $this->modifyRecords("INSERT INTO `UserPostHistory_User_".$UD[0]["UserID"]."` VALUES(NOW(), '".$topicID."_".$total_records."', '".base64_encode($this->applyFilters($_POST["topictitle"]))."');");

					// Update the user's date of latest activity
					$SQL = $this->modifyRecords("UPDATE UserData SET LastActive=NOW() WHERE UserName='".$this->isLoggedOn."';");
					
					// If any of the above fail, you cannot proceed
					if ($SQL != false)
					{
						// $post_result='<span style="color:#008800;">Message successfully posted.</span>';
						$this->goToIndexPage($thisFile);
					}
					else {
						$this->showMessagePoster("Internal error: update could not happen.");
					}
				}
				else
				{
					$this->showMessagePoster($post_result); // Correct errors and try again!
				}
			}
			else // Show all published topics
			{
				print <<<BLOCK

			<table style="border:1px #000 solid; border-spacing:5px; width:100%;">
				<tr>
					<td><strong>Topic</strong></td>
					<td><strong>User</strong></td>
					<td><strong># Replies</strong></td>
					<td><strong>Last Activity</strong></td>
				</tr>
BLOCK;

				$no_posts_yet = 0; // No posts yet counter

				$SQL = $this->fetchRecords("SELECT TopicID,TopicTitle,ByUser FROM `TopicList` WHERE Category='$topicID' ORDER BY TopicID DESC;");
				//$SQL = $this->fetchRecords("SELECT table_name AS TotTabs FROM information_schema.tables WHERE table_name LIKE 'Topic_".$topicID."_%' AND table_name NOT LIKE '%_DEL';");
				
				if ($SQL != false)
				{
					/*
					// Old sorting method, no longer needed
					for ($i = 0; $i < sizeof($SQL); ++$i)
					{
						$row_data = $SQL[$i];
						
						$subSQL = $this->fetchRecords("SELECT CMUser,CMTitle FROM `".$row_data["TotTabs"]."` WHERE ReplyTo=0 AND Status=1;");
						
						if ($subSQL != false)
						{
							for ($j = 0; $j < sizeof($subSQL); ++$j)
							{
								$sub_row_data = $subSQL[$j];

								$index = explode("_", $row_data["TotTabs"]);
								
								echo "\n\t\t      <tr>\n\t\t";
								echo '        <td><a href="'.$this->currentFile.'-'.$index[2].'.html">'.base64_decode($sub_row_data['CMTitle']).'</a></td>';
								echo "\n\t\t";
								echo '        <td>'.$sub_row_data["CMUser"].'</td>';
								echo "\n\t\t";
								echo '        <td><!-- # of replies -->'.$this->showTabData(1, $row_data["TotTabs"]).'</td>'; // Show number of posts
								echo "\n\t\t";
								echo '        <td>'.$this->showTabData(2, $row_data["TotTabs"]).'</td>'; // Show date of latest post
								echo "\n      </tr>\n";
							}
						}
					}
					*/
					for ($j = 0; $j < sizeof($SQL); ++$j)
					{
						$row = $SQL[$j];
						
						echo "\n\t\t      <tr>\n\t\t";
						echo '        <td><a href="'.$this->currentFile.'-'.$row["TopicID"].'.html">'.base64_decode($row['TopicTitle']).'</a></td>';
						echo "\n\t\t";
						echo '        <td>'.$row["ByUser"].'</td>';
						echo "\n\t\t";
						echo '        <td><!-- # of replies -->'.$this->showTabData(1, $topicID."_".$row["TopicID"]).'</td>'; // Show number of posts
						echo "\n\t\t";
						echo '        <td>'.$this->showTabData(2, $topicID."_".$row["TopicID"]).'</td>'; // Show date of latest post
						echo "\n      </tr>\n";
					}
				}
				else // No posts yet...
				{
					if ($no_posts_yet == 0)
					{
						$no_posts_yet++;
				
						print <<<BLOCK
					<tr>
						<td colspan="4">No posts yet. Be the first to create one!</td>
					</tr>
BLOCK;
					}
				}

				echo "		</table>\n";
			}
		
		print <<<DOC_BOTTOM
	</body>
</html>
DOC_BOTTOM;
		}
	}
?>