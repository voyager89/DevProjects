<?php
	require_once "general-navigation.php";

	new GeneralNavigation("404",$_GET["doc"] ?? null);
?>