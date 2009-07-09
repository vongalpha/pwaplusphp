<?PHP

#==============================================================================================
# Copyright 2009 Scott McCandless (smccandl@gmail.com)
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
# http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#==============================================================================================

#----------------------------------------------------------------------------
# CONFIGURATION
#----------------------------------------------------------------------------
require_once("config.php");
$action = "gallery.php";	   # Name of the page that displays galleries

#----------------------------------------------------------------------------
# Check for required variables from config file
#----------------------------------------------------------------------------
if ( (!isset($GDATA_TOKEN)) || (!isset($PICASAWEB_USER)) || (!isset($IMGMAX)) || (!isset($THUMBSIZE)) || (!isset($USE_LIGHTBOX)) || (!isset($REQUIRE_FILTER)) || (!isset($STANDALONE_MODE)) || (!isset($IMAGES_PER_PAGE)) ) {

        echo "<h1>Error: One or more required variables is missing from config.php!</h1><h3>Please re-run the install.php configuration script.</h3>";
        exit;
}

#----------------------------------------------------------------------------
# VARIABLES
#----------------------------------------------------------------------------
$FILTER = $_REQUEST['filter'];
if ($REQUIRE_FILTER != "FALSE") {
	if ((!isset($FILTER)) || ($FILTER == "")) {
		die("Permission Denied.  Filter is required.");
	}
}

$ALL = $_REQUEST['all'];
if ($ALL == "") { $ALL = 0; }


$file = "http://picasaweb.google.com/data/feed/api/user/" . $PICASAWEB_USER . "?kind=album";

#----------------------------------------------------------------------------
# Curl code to store XML data from PWA in a variable
#----------------------------------------------------------------------------
$ch = curl_init();
$timeout = 0; // set to zero for no timeout
curl_setopt($ch, CURLOPT_URL, $file);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

# Display only public albums if PUBLIC_ONLY=TRUE in config.php
if ($PUBLIC_ONLY == "FALSE") {
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    		'Authorization: AuthSub token="' . $GDATA_TOKEN . '"'
  	));
}

$addressData = curl_exec($ch);
curl_close($ch);

#----------------------------------------------------------------------------
# Parse the XML data into an array
#----------------------------------------------------------------------------
$p = xml_parser_create();
xml_parse_into_struct($p, $addressData, $vals, $index);
xml_parser_free($p);

#----------------------------------------------------------------------------
# Output headers if required
#----------------------------------------------------------------------------
if ($STANDALONE_MODE == "TRUE") {

	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
        echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"en\" xml:lang=\"en\">\n";
        echo "<head>" . "\n";
	echo "<title>" . $PICASAWEB_USER . "'s Picasa Galleries</title>" . "\n";
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"style.css\" />" . "\n";
	echo "</head>" . "\n";
	echo "<body>" . "\n";
}

#----------------------------------------------------------------------------
# Iterate over the array and extract the info we want
#----------------------------------------------------------------------------
unset($thumb);
unset($title);
unset($href);
unset($num);
unset($description);
foreach ($vals as $val) {

	switch ($val["tag"]) {

			case "MEDIA:THUMBNAIL":
				$thumb = trim($val["attributes"]["URL"] . "\n");
				break;	
			case "MEDIA:DESCRIPTION":
				$desc = trim($val["value"] . "\n");
				break;
                        case "MEDIA:TITLE":
                                $title = trim($val["value"]);
                                break;
                        case "LINK":
				if ($val["attributes"]["REL"] == "alternate") {
                                	$href = trim($val["attributes"]["HREF"]);
				}
                                break;
                        case "GPHOTO:NUMPHOTOS":
                                $num = trim($val["value"]);
                                break;
			case "GPHOTO:LOCATION":
                                $loc = trim($val["value"]);
                                break;
			case "PUBLISHED":
                                $published = trim($val["value"]);
				$published = substr($published,0,10);	
                                break;
        }

	#----------------------------------------------------------------------------
	# Once we have all the pieces of info we want, dump the output
	#----------------------------------------------------------------------------
	
	if (isset($thumb) && isset($title) && isset($href) && isset($num) && isset($published)) {
		
		if ($ALL == 1) { 
			$pos = 0;
		} else { 
			if ($FILTER != "") {
				$pos = strlen(strpos($title,$FILTER));
				if ($pos > 0) { $pos = 0; }
				else { $pos = 1; }
			} else {
				$pos = strlen(strpos($title,"_hide"));
			}
		}
		
		if ($pos == 0) {

			if ($SHOW_ALBUM_DETAILS=="FALSE") {
				$thumbwidth = $THUMBSIZE + ($THUMBSIZE * .2);
			} else {
				$thumbwidth = $THUMBSIZE + 200;
				$galdatasize= $THUMBSIZE * 1.10;
			}

			list($disp_name,$tags) = split('_',$title);

			echo "<div class='thumbnail' style='width: " . $thumbwidth . "px;'>\n";
			echo "<div class='thumbimage' style='width: " . $THUMBSIZE . "px;'>\n";
				echo "<a href='" . $action . "?album=$title'><img alt='image_from_picasa' src='$thumb'></img></a>\n";
			echo "</div>\n";
			echo "<div class='galdata' style='width: " . $galdatasize . "px;'>\n";

				echo "<p class='titlepg'><a href='" .$action . "?album=$title'>$disp_name</a></p>\n";
				echo "<p class='titlestats'>$published, $num images</p>\n";
				if ($SHOW_ALBUM_DETAILS == "TRUE") {
					if ($desc != "") { 
						echo "<p class='albumdesc'>$desc</p>\n";
					}
					if ($loc != "") {
						echo "<p class='location'>Location: $loc</p>\n";
					}
				
				}

			echo "</div>";
			echo "</div>\n";

		}
			#----------------------------------
			# Reset the variables
			#----------------------------------
			unset($thumb);
			unset($title);
			unset($href);
			unset($num);
			unset($description);
	
	}
}
unset($title);

#----------------------------------------------------------------------------
# Output footer if required
#----------------------------------------------------------------------------
if ($STANDALONE_MODE == "TRUE") {

        echo "</body>" . "\n";
        echo "</html>" . "\n";
}

?>
