<? 

function get_content ($ci)
{
  $vals = explode ("\n", $ci->nodeValue);
  $auth_cnt = 0;
  for ($i=0; $i < count($vals); $i++) {
    $index = trim ($vals[$i]);
    switch ($index) {
      case "Type": $ret['type'] = htmlspecialchars (trim ($vals[$i+1])); break;
      case "Author": $ret['author'][$auth_cnt++] = htmlspecialchars (trim ($vals[$i+1])); break;
      case "Date": $ret['date'] = htmlspecialchars (trim ($vals[$i+1])); break;
      case "Abstract": $ret['abstract'] = htmlspecialchars (trim ($vals[$i+1])); break;
      case "# of Pages": $ret['page_count'] = htmlspecialchars (trim ($vals[$i+1])); break;
    }
  }

  return $ret;
}

//$phpinfo(); 

//$url = "https://api.zotero.org/users/702972/items?start=0&limit=25&format=atom&tag=read%20me&v=1";
$url = "https://api.zotero.org/users/702972/items?start=0&limit=250&format=atom&tag=reading%20list&v=1";
//$url = "https://ato.ms/zotero_rss/rss.php";

//$now = "2014-10-20T05:59:00Z";
$now = date("Y-m-d\\TH:i:s\\Z", time());

$str = file_get_contents($url);

//print $str

$xml = new DOMDocument();
//$xml->preserveWhiteSpace = false;
$xml->loadXML ($str);

// feed
$xmlDoc = $xml->documentElement;
foreach ($xmlDoc->childNodes AS $docItem) {
  //print $item->nodeName . " = " . $item->nodeValue . "\n";

  // process <entry> tag
  if ($docItem->nodeName == "entry") {
    $publishedItem = null;
    $updatedItem = null;

    // find and save the nodes for <published>, <updated>, <id> and <content> so that we can change them later
    foreach ($docItem->childNodes AS $elementItem) {
      if ($elementItem->nodeName == "published") $publishedItem = $elementItem;
      if ($elementItem->nodeName == "updated") $updatedItem = $elementItem;
      if ($elementItem->nodeName == "id") $idItem = $elementItem;
      if ($elementItem->nodeName == "content") $contentItem = $elementItem;
      if ($elementItem->nodeName == "title") $titleItem = $elementItem;
    }

    if ($publishedItem != null && $updatedItem != null) {
      //$publishedItem->nodeValue = $updatedItem->nodeValue;
      // published = updated = now
      $publishedItem->nodeValue = $now;
      $updatedItem->nodeValue = $now;

      // reformat the RSS <content> tag to be what we want
      $details = get_content ($contentItem);
      $details['title'] = htmlspecialchars ($titleItem->nodeValue);
      $details['url'] = htmlspecialchars ($idItem->nodeValue);
      $content = $details['title'];
      $content = $content . "<br>\n" . $details['url'];
      $content = $content . "<br>\n" . preg_replace ('/\//', "/\n", substr (trim ($details['url']), 7), 1);
      for ($i = 0; $i < count ($details['author']); $i++) {
	$content = $content . "<br>\nAuthor: " . $details['author'][$i];
      }
      $content = $content . "<br>\nDate: " . $details['date'];
      $content = $content . "<br>\nType: " . $details['type'];
      $content = $content . "<br>\nPages: " . $details['page_count'];
      $content = $content . "<br>\n" . $details['abstract'] . "<br>\n<br>\n<br>\n<br>\n<br>\n<br>\n";
      /*$content = " \n<a href=\"" . trim ($details['url']) .
	  "\">" . trim ($details['url']) . "</a>";*/
      //$content = trim ($details['url']);
      $contentItem->nodeValue = $content;

      // reformat the RSS <title> tag to be what we want
      $title = $details['title'];
      if ($details['date'] != null and $details['date'] != "") $title = $title . " - " . $details['date'];
      if (count ($details['author']) > 0) $title = $title . " - ";
      for ($i = 0; $i < count ($details['author']); $i++) {
	$title = $title . $details['author'][$i];
	if (($i + 1) < count ($details['author'])) $title = $title . ", ";
      }
      $titleItem->nodeValue = $title;
      //print $contentItem->nodeValue;
      //$contentItem->nodeValue = $idItem->nodeValue;
    }
  }

  // set the overall feed update time to "now
  if ($docItem->nodeName == "updated") {
    $docItem->nodeValue = $now;
  }
}

$finalXml = $xml->saveXML();
print $finalXml;

?>
