<?php
	class XML_Controller
	{
		private function B64($type, $data) // type: 0 - decode, 1 - encode
		{
			return !$type ? base64_decode($data) : base64_encode($data);
		}
		
		private function endObj($obj)
		{
			return str_replace("<", "</", $obj);
		}
		
		public function __construct()
		{
			/*
			echo "XML Detector (".dirname(__FILE__)."):<hr/>\n";
			foreach (scandir(dirname(__FILE__)) as $locFile)
			{
					$xmlLT=((strpos(strtolower($locFile),".xml")!==false)?'<span class="xmlDoc">&gt;&gt; ':'');
					echo $xmlLT.$locFile.((strlen($xmlLT)>1)?" &lt;&lt;</span>":"")."<br/>\n";
			}
			*/
		}
		
		//("doc2.xml","<profile>",["<username>","<picture>","<description>","<signature>"])
		public function readDoc($xmlFile, $obGroup, $objType) // readDoc returns all values inside array object
		{
			$incr = 0;
			$valueSTR = Array();
			$xmlDoc = simplexml_load_file($xmlFile) or die("Error: Cannot find file $xmlFile or cannot create object.");
			// echo "Result: ".$xmlDoc->getName()."<br/>";

			foreach ($xmlDoc->children() as $xmlObj)
			{
				$subObj = Array();
				$xmlObjLen = $xmlObj->children();
				
				if (sizeof($xmlObjLen) > 1)
					for ($zy = 0; $zy < sizeof($xmlObjLen); $zy++)
						$subObj[$zy] = $xmlObjLen[$zy]->getName()." ~~ ".$this->B64(0,$xmlObjLen[$zy]);

				// echo $xmlObj->getName().": ".((sizeof($xmlObjLen)>1)?"":$this->B64(0,$xmlObj))." ".((sizeof($xmlObjLen)>1)? implode(" | ",$subObj):$this->B64(0,$xmlObjLen))."<br/>\n";
				$valueSTR[$incr] = sizeof($xmlObjLen) > 1 ? implode(" | ",$subObj) : $this->B64(0,$xmlObj);
				$incr++;
			}
			unset($xmlDoc);
			return $valueSTR;
		}
		
		public function writeToDoc($xmlFile, $obGroup, $objType, $objData)
		{
			$xml = fopen($xmlFile,"w") or die("Oh *beep*!");

			fwrite($xml, '<?xml version="1.0" encoding="UTF-8"?>');
			fwrite($xml, "\n\t");
			fwrite($xml, $obGroup."\n");
		
			for ($xt = 0; $xt < sizeof($objType); $xt++)
			{
				fwrite($xml, $objType[$xt]);
				
				if (sizeof($objData[$xt]) > 1) // For 2D arrays being converted into XML file structure
					for ($yt = 0; $yt < sizeof($objData[$xt]); $yt++)
						if (strpos($objData[$xt][$yt], "<") !== false)
							fwrite($xml, "\t\t".$objData[$xt][$yt].$this->B64(1,$objData[$xt][$yt+1]).$this->endObj($objData[$xt][$yt])."\n");
				else
					fwrite($xml, $this->B64(1,$objData[$xt])); // Simple 1 dimensional array converted into XML tree

				fwrite($xml, $this->endObj($objType[$xt])."\n");
			}
			
			fwrite($xml, $this->endObj($obGroup));
			fclose($xml); // echo "Success";
		}
	}
?>