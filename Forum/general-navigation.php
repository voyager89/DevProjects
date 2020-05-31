<?php
	require_once "core.php";
	require_once "logger.php";

	class GeneralNavigation extends Forum89
	{
		public function __construct($requestedDocument, $extraArgs = null)
		{
			if (gettype($requestedDocument) == "string")
			{
				switch (strtolower($requestedDocument))
				{
					case "about":
						$stats = $this->getStats();

						$version = $stats["version"];
						$releaseDate = $stats["release_date"];
						$finalTestDate = $stats["final_test_date"];
						
						$menuLogStatus_0 = UserLogger::navLogger(0);
						$menuLogStatus_1 = UserLogger::navLogger(1);
						
						print <<<AboutDocument
<!DOCTYPE html>
<html lang="en">
    <head>
    	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <style type="text/css">
            a {color:#00f; text-decoration:none;}
            a:hover {color:#f00; text-decoration:underline;}
            div.about {font-family:verdana; margin:auto; text-align:center; width:33%;}
            div.about>span {text-decoration:underline;}
            div.about div:nth-child(3) {margin-top:20px; padding:30px; text-align:left;}
            div.about>table {text-align:left; margin:20px auto; width:250px;}
            table td[colspan="2"] {padding-top:30px; text-align:center;}
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
                div.about {margin-top:30px; width:100%;}
                nav ul {padding:0px;}
				nav ul li a {padding:10px;}
				nav ul li a:active {background-color:rgb(2,105,189); border-color:rgb(2,105,189); color:#fff;}
				nav ul li:nth-child(3) a {display:none;}
            }
        </style>
        <title>About - V89 Forum</title>
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
        <div class="about">
            <span>Voyager 89 Forum:</span>
            <table>
                <tr>
                    <td>Release date:</td><td> $releaseDate </td>
                </tr>
                <tr>
                    <td>Final test:</td><td> $finalTestDate </td>
                </tr>
                <tr>
                    <td>Version:</td><td> $version </td>
                </tr>
                <tr>
                    <td colspan="2">Built using:</td>
                </tr>
                <tr>
                    <td colspan="2">
                        <img alt="PHP logo" src="php-logo.png" style="width:150px;"/>
                        <img alt="MySQL logo" src="mysql-logo.jpg" style="width:250px;"/>
                    </td>
                </tr>
            </table>
            <div>
                <hr/><br/>If you come across any errors on this forum please report these using the Contact form in the About page at <a href="https://www.voyager89.net/" target="_blank">www.voyager89.net</a>.
            </div>
        </div>
    </body>
</html>
AboutDocument;
					break;
					case "404":
						if (!is_null($extraArgs))
						{
							print <<<NotFound
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<style type="text/css">
			a {color:#00f; font-size:24px; text-decoration:none;}
			a:active {color:#f00; text-decoration:underline;}
		</style>
		<title>Not found - V89 Forum</title>
	</head>
	<body>
		<h1>The requested page could not be found:</h1>

		<h2><tt> $extraArgs </tt></h2>

		<br/><hr/>
		
		<a href="#" onclick="window.history.back(-1); return false;">Return to previous page</a>
	</body>
</html>
NotFound;
						}
						exit;
					default: exit("You have requested an unknown document: '".$requestedDocument."'");
				}
			}
			else {
				exit("You have requested an invalid document of type: $requestedDocument (".gettype($requestedDocument).")");
			}
		}
	}
?>