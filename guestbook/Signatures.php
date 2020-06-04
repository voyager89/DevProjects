<?php
header("Access-Control-Allow-Origin: projects.voyager89.net");
header("Content-Type: application/json; charset=UTF-8");

$SQL;

function sanitize($text)
{
	return htmlspecialchars(stripslashes($text));
}

function V89DB()
{
	// this has been intentionally left blank; security measures I trust you'll agree
	$SQL_C = new mysqli("","","","") or die("Cannot connect to database! Please try again later.");
	
	return $SQL_C;
}

function isProfane($str)
{
    $result = false;
    $restrictedList = [
	// you can fill this out yourself
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

$input = file_get_contents("php://input");

if (isset($_GET["records"]))
{
    $recs = "";

	global $SQL;
	$SQL = V89DB();

    $SQL_GO = $SQL->query("SELECT UserMail,DATE_FORMAT(UserDate,'%d/%m/%Y') AS UserDate,UserName,UserComments FROM `guestbook`;");

    while ($SQL_RS = $SQL_GO->fetch_assoc())
    {
        $recs .= (strlen($recs) > 1 ? "," : "");
        $recs .= '{"Date":"'.$SQL_RS["UserDate"].'","Name":"'.$SQL_RS["UserName"].'","Email":"'.$SQL_RS["UserMail"].'","Comment":"'.base64_decode($SQL_RS["UserComments"]).'"}';
    }

    $SQL->close();

    echo '{"records":['.$recs.']}';
}
else if (strlen($input) > 1)
{
    $request = json_decode($input);
    $name = $request->name;
    $email = $request->email;
    $comm = $request->comments;
    //---
    $errors = false;
    $name_TS = $name;
    $email_TS = $email;
    $comm_TS = $comm;

    if (strlen(trim($name_TS)) > 30 || strlen(trim($name_TS)) < 3 || !ctype_alpha(str_replace(' ', '', $name_TS)) || isProfane($name_TS))
    {
        $errors = true;
        $name_TS = "<em>Name must be between 3 and 30 [Aa-Zz] characters, and no profanity allowed!</em>";
    }

    if (!filter_var($email_TS, FILTER_VALIDATE_EMAIL) || duplicateMail($email_TS) || isProfane($email_TS))
    {
        $errors = true;
        $email_TS = "<em>E-mail address must be between 10 and 30 characters, or it already exists, or it's using profanity!</em>";
    }

    if (strlen(trim($comm_TS)) > 40 || strlen(trim($comm_TS)) < 5 || isProfane($comm_TS))
    {
        $errors = true;
        $comm_TS = "<em>Comments must be between 5 and 40 characters, and be civil!</em>";
    }

    //-----------------
    if ($errors == true)
    {
        echo '{"res":[{"Errors":"'.$errors.'"},{"Name":"'.$name_TS.'"},{"Email":"'.$email_TS.'"},{"Comments":"'.$comm_TS.'"},{"Date":"'.date("Y-m-d").'"}]}';
    }
    else
	{
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