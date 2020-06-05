<?php
header("Access-Control-Allow-Origin: projects.voyager89.net");
header("Content-Type: application/json; charset=UTF-8");

$SQL;

// Sanitizer - no tags allowed, and no slashes
function sanitize($text)
{
	return htmlspecialchars(stripslashes($text));
}

function V89DB()
{
	// database details left out
	$SQL_C = new mysqli("","","","") or die("Cannot connect to database! Please try again later.");
	
	return $SQL_C;
}

// Profanity list - checks if string contains swearing (it's been omitted; you can come up with your own)
function isProfane($str)
{
    $result = false;
    $restrictedList = [
		"...",
		"...",
		"..."
	];

    for ($yt = 0; $yt < sizeof($restrictedList); ++$yt)
    {
        if (strpos(strtolower($str), $restrictedList[$yt]) !== false)
        {
            $result = true;
        }
    }

    return $result;
}

// Check if the e-mail of this signature already exists
function duplicateMail($ML)
{
	global $SQL;
	$SQL = V89DB();
    
	$mailExists = false;
    
    $SQL_GO = $SQL->query("SELECT UserMail FROM `guestbook` WHERE UserMail LIKE '$ML';");

    if ($SQL_RS = $SQL_GO->num_rows > 0)
    {
        $mailExists = true;
    }

    $SQL->close();

    return $mailExists;
}

// More user-friendly date format
function rearrangeDate($date)
{
	return date("d/m/Y", strtotime($date));
}

// Get posted input
$input = file_get_contents("php://input");

// Fetch all signatures
if (isset($_GET["records"]))
{
    $recs = "";

	global $SQL;
	$SQL = V89DB();

    $SQL_GO = $SQL->query("SELECT UserMail,UserDate,UserName,UserComments FROM `guestbook` ORDER BY UserDate DESC;");

    while ($SQL_RS = $SQL_GO->fetch_assoc())
    {
        $recs .= (strlen($recs) > 1 ? "," : "");
        $recs .= '{"Date":"'.rearrangeDate($SQL_RS["UserDate"]).'","Name":"'.$SQL_RS["UserName"].'","Email":"'.$SQL_RS["UserMail"].'","Comment":"'.base64_decode($SQL_RS["UserComments"]).'"}';
    }

    $SQL->close();

    echo '{"records":['.$recs.']}';
}
else if (strlen($input) > 1)
{
	// A new signature?
	
    $request = json_decode($input);
    $name = $request->name;
    $email = $request->email;
    $comm = $request->comments;

    $errors = false;
    $name_TS = $name;
    $email_TS = $email;
    $comm_TS = $comm;

	// Check the name
    if (strlen(trim($name_TS)) > 30 || strlen(trim($name_TS)) < 3 || !ctype_alpha(str_replace(' ', '', $name_TS)) || isProfane($name_TS))
    {
        $errors = true;
        $name_TS = "<em>Name must be between 3 and 30 [Aa-Zz] characters, and no profanity allowed!</em>";
    }

	// Check the e-mail
    if (!filter_var($email_TS, FILTER_VALIDATE_EMAIL) || duplicateMail($email_TS) || isProfane($email_TS))
    {
        $errors = true;
        $email_TS = "<em>E-mail address must be between 10 and 30 characters, or it already exists, or it's using profanity!</em>";
    }

	// Check the comments
    if (strlen(trim($comm_TS)) > 40 || strlen(trim($comm_TS)) < 5 || isProfane($comm_TS))
    {
        $errors = true;
        $comm_TS = "<em>Comments must be between 5 and 40 characters, and be civil!</em>";
    }

    // If the checks fail send back the reason
    if ($errors == true)
    {
        echo '{"res":[{"Errors":"'.$errors.'"},{"Name":"'.$name_TS.'"},{"Email":"'.$email_TS.'"},{"Comments":"'.$comm_TS.'"},{"Date":"'.date("Y-m-d").'"}]}';
    }
    else
	{
		// Checks have passed - all good to sign the guestbook!

		global $SQL;
		$SQL = V89DB();

		$email_TS = sanitize($email_TS);
		$name_TS = sanitize($name_TS);
		$comm_TS = sanitize($comm_TS);
		
		$SQL_GO = $SQL->query("INSERT INTO `guestbook`(UserMail,UserName,UserComments) VALUES('$email_TS','$name_TS','".base64_encode($comm_TS)."');");

		$SQL->close();
    }
}
?>