<?php
    class YellowNotes
    {
        public $notesArray = [];

		private function sanitize($text)
		{
			return htmlspecialchars(stripslashes($text));
		}

        private function fileSort($fileData, $noteDateID)
        {
            $output = "";
			
            if ($fileData === "None")
                $output = "<input id='$noteDateID' name='attachFile' onchange='window.actionNote(5, this.id, this.files[0]);' type='file'/>";
            else
                $output = '<a href="file_storage/'.$fileData.'" onclick="window.open(this.href); return false;">'.$fileData.'</a> | <a href="#" onclick="window.deleteFile(\''.$noteDateID.'\'); return false;">Delete file</a>';

            return $output;
        }
		
		// Strict file checks
		private function isFileSafeToUpload($tmpFile, $realFile)
		{
			$isSafe = true;

			// Get file type
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$getFileType = finfo_file($finfo, $tmpFile);
			finfo_close($finfo);

			// No executable or other application-type files allowed!
			if (strripos($getFileType, "application/") === false && strripos($getFileType, "x-dosexec") === false)
			{
				$fileData = file_get_contents($tmpFile);

				// File checks happen here; to disable a certain file check simply comment it out
				if (
					strripos($realFile, ".phtml") !== false		||	// No PHP files allowed!
					strripos($realFile, ".php") !== false 		|| 	// No PHP files allowed!
					strripos($realFile, ".htaccess") !== false	||	// No Apache server configuration files
					strripos($fileData, "<?php") !== false	||	// No files containing PHP tags allowed!
					strripos($fileData, "<?=") !== false	||	// (as above)
					strripos($fileData, "<script") !== false	// No files containg HTML script tags allowed!
				)
				{
					$isSafe = false;
				}
				
				$fileData = null;
				unset($fileData);
			}
			else {
				$isSafe = false;
			}

			return $isSafe;
		}

        public function runSql($noteData, $noteID, $task)
        {
			$SQL_C = new mysqli("","","",""); // for live config

			switch ($task)
			{
				case 0: // Deleting note
					$noteDate = explode("_", $noteID);
					$noteDateRef = $noteDate[1]." ".$noteDate[2];   
					$SQL_run = $SQL_C->query("SELECT NoteFile FROM `yellownotes2` WHERE NoteDate='$noteDateRef';");
					$noteFileFlag = dirname(__FILE__)."/file_storage/".$SQL_run->fetch_assoc()["NoteFile"];

					// If note has a file attached to it, that file must be deleted
					if ($noteFileFlag !== "None" && file_exists($noteFileFlag))
						unlink($noteFileFlag);
					
					$SQL_run = $SQL_C->query("DELETE FROM `yellownotes2` WHERE NoteDate='$noteDateRef';");

					if ($SQL_C->affected_rows > 0)
						$this->runSql(null, null, 3);
					else
						exit("Deleting note failed. Please try again.");

				break;
				case 1: // Updating note
					$noteDate = explode("_", $noteID);
					$noteDateRef = $noteDate[1]." ".$noteDate[2];
					
					if (strlen($noteData) > 110) // just in case the JS limit has been bypassed...
						exit("<strong>You cannot post a yellow note consisting more than 110 characters!</strong>");

					$SQL_run = $SQL_C->query("UPDATE `yellownotes2` SET NoteMessage='".base64_encode($this->sanitize($noteData))."' WHERE NoteDate='$noteDateRef';");
					
					if ($SQL_C->affected_rows > 0)
						$this->runSql(null, null, 3);
					else
						exit("Editing note failed. Please try again.");

				break;
				case 2: // Writing a new note
					if (strlen($noteData) > 110) // just in case the JS limit has been bypassed...
						exit("<strong>You cannot post a yellow note consisting more than 110 characters!</strong>");

					$SQL_run = $SQL_C->query("INSERT INTO `yellownotes2`(NoteDate,NoteMessage,NoteFile) VALUES(NOW(),'".base64_encode($this->sanitize($noteData))."','None');");
					
					if ($SQL_C->affected_rows > 0)
						$this->runSql(null, null, 3);
					else
						exit("Inserting new note failed. Please try again.");

				break;
				case 3: // List all notes
					$SQL_run = $SQL_C->query("SELECT DATE_FORMAT(NoteDate, 'YN_%Y-%m-%d_%H:%i:%s') AS NoteDate,DATE_FORMAT(NoteDate, '%d/%m/%Y - %h:%i') AS NoteDateFormatted,NoteMessage,NoteFile FROM `yellownotes2` ORDER BY NoteDate DESC;");
					
					if ($SQL_run->num_rows > 0)
						for ($inc = 0; $SQL_res = $SQL_run->fetch_assoc(); $inc++)
							$this->notesArray[$inc] = Array($SQL_res["NoteDate"], $SQL_res["NoteDateFormatted"], $SQL_res["NoteMessage"], $SQL_res["NoteFile"]);

					if (sizeof($this->notesArray) > 0)
					{
						for ($inc = 0; $inc < sizeof($this->notesArray); $inc++)
						{
							$noteDateID = $this->notesArray[$inc][0];
							$noteDate = $this->notesArray[$inc][1];
							$noteMsg = $this->sanitize(base64_decode($this->notesArray[$inc][2]));
							$noteFile = $this->fileSort($this->notesArray[$inc][3], $noteDateID);
	
							print <<<YellowNote
	<div class="note" id="$noteDateID">
			<div class="title">$noteDate</div>
			<div class="message">
				$noteMsg
			</div>
			<div class="file">
				<hr/>
				$noteFile
			</div>
			<div class="links">
				<hr/>
				<a href="#" onclick="window.showMessageBox(2, '$noteDateID'); return false;">Edit Note</a>
				<a href="#" onclick="window.showMessageBox(0, '$noteDateID'); return false;">Delete Note</a>
			</div>
		</div>
YellowNote;
						}
					}
					else {
						echo "There are no yellow notes posted as yet.";
					}
				break;
				case 4: // Deleting file attached to note, but note stays
					$noteDate = explode("_", $noteID);
					$noteDateRef = $noteDate[1]." ".$noteDate[2];
					$SQL_run = $SQL_C->query("SELECT NoteFile FROM `yellownotes2` WHERE NoteDate='$noteDateRef';");

					if ($SQL_run->num_rows > 0)
					{
						$fileToDelete = dirname(__FILE__)."/file_storage/".$SQL_run->fetch_assoc()["NoteFile"];
						$SQL_run = $SQL_C->query("UPDATE `yellownotes2` SET NoteFile='None' WHERE NoteDate='$noteDateRef';");
					
						if ($SQL_C->affected_rows > 0 && file_exists($fileToDelete))
						{
							unlink($fileToDelete);
							$this->runSql(null, null, 3);
						}
						else {
							exit("Deleting the file has failed. It may already have been deleted.");
						}
					}
					else {
						exit("Deleting the file has failed. Please try again.");
					}
				break;
				case 5: // Uploading file to note
					$noteDate = explode("_", $noteID);
					$noteDateRef = $noteDate[1]." ".$noteDate[2];

					$submittedFile = $_FILES["attachFile"]["tmp_name"] ?? $noteData;

					if ($submittedFile)
					{
						$uploadedFile = $_FILES["attachFile"]["name"];
						
						// Uploaded file must not exceed 250 kilobytes
						if (
							$_FILES["attachFile"]["size"] <= 250000 &&
							$this->isFileSafeToUpload($submittedFile, $uploadedFile) &&
							move_uploaded_file($submittedFile, dirname(__FILE__)."/file_storage/".$uploadedFile) === true
						)
						{
							$SQL_run = $SQL_C->query("UPDATE `yellownotes2` SET NoteFile='$uploadedFile' WHERE NoteDate='$noteDateRef';");

							if ($SQL_C->affected_rows > 0)
							{
								$this->runSql(null, null, 3);
							}
							else {
								exit("Updating the database has failed. Please try again.");
							}
						}
						else {
							$failed = "$uploadedFile could not be uploaded. This could be due to: it being a restricted file type, it containing restricted data, ";
							$failed .= "or it may exceed 250 Kilobytes. Please try again. <a href='yellow-notes.html'>Return</a>";

							exit($failed);
						}
					}                        
				break;
			}

			$SQL_C->close();
        }

        public function __construct($noteData, $noteID, $task)
        {
            if (is_null($task))
                exit("You have no business here.");

            $this->runSql($noteData, $noteID, $task);
        }
    }

    $noteID = $_POST["noteID"] ?? null;
    $noteData = $_POST["noteData"] ?? null;
    $task = $_POST["task"] ?? null;
    
    new YellowNotes($noteData, $noteID, $task);
?>