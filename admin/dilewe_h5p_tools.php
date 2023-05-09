<?php

function dilewe_h5p_tools_render()
{
?>
<link rel="stylesheet" href="style.css">


<h3>Dilewe H5P Tools:</h3>

<h4>Export:</h4>
<p>wähle ein tag, zu welchem Projekt die H5P Aufgaben exportiert werden sollen</p>

<form method="POST" action="<?php echo admin_url( 'tools.php?page=dilewe_h5p_tools' ); ?>">
	<select id="tags" name="tags">
	<option value="emr">Geo Euregio-Maas-Rhein EMR</option>
	<option value="lasub">Lasub</option>
	<option value="juramuseum">Juramuseum</option>
	<option value="DAZ">DAZ</option>
	</select>
	<input type="submit" name="verb" value="Export" />
</form>

<br/>
<h4>Import</h4>
<p>kopiere die dateien in den "ordner"</p>

<form method="POST" action="<?php echo admin_url( 'tools.php?page=dilewe_h5p_tools' ); ?>" enctype="multipart/form-data">
	<select id="language" name="language">
		<option value="fr">Französisch</option>
		<option value="nl">Niederländisch</option>
	</select>
	<input name="uploadedfiles[]" type="file" multiple="multiple" accept="application/json">
	<input type="submit" name="importsubmit" value="Import" />
</form>


<h4>SystemX Export:</h4>
<p>exportiere eine XML-Datei welche von der SystemX ContentPipeline genutzt werden kann um H5P Elemente in der richtigen Sprache anzuzeigen.</p>

<form method="POST" action="<?php echo admin_url( 'tools.php?page=dilewe_h5p_tools' ); ?>">
	<input type="submit" name="verb" value="ExportSystemX" />
</form>
<?php
getExportRender();
getExportSystemXRender();
getFolder();
?>

<?php
}

function getExportRender() {
	if ($_SERVER["REQUEST_METHOD"] != "POST") { return;}
	if (!$_REQUEST['verb']) { return;}
	if (!$_REQUEST['tags']) { return;}

	$name = $_REQUEST['verb'];
	$tag = $_REQUEST['tags'];
	if ($name != "Export") { return;}

	$zip = new ZipArchive();
	$filename = tempnam("/tmp", "h5pexport_");

	if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
		exit("cannot open <$filename>\n");
	}
	
	$result = getAllTagContent($tag);
	
	foreach($result as &$o){
		
		$o['parameters'] = json_decode($o['parameters'],true);
		$id = $o['id'];
		$zip->addFromString("h5pexport_" . date("Ymd") . "/$id.json" , json_encode($o, JSON_PRETTY_PRINT));
	}

	echo "numfiles: " . $zip->numFiles . "\n";
	echo "status:" . $zip->status . "\n";
	$zip->close();
	$data = file_get_contents($filename);

	?>

	<h4>tag <?php echo $tag ?> gewählt:</h4>
	<a href="data:application/zip;base64,<?php echo base64_encode($data);?>" download="h5pexport.zip">Download</a>

	<?php
}

function getExportSystemXRender() {
	if ($_SERVER["REQUEST_METHOD"] != "POST") { return;}
	if (!$_REQUEST['verb']) { return;}

	$name = $_REQUEST['verb'];
	if ($name != "ExportSystemX") { return;}

	$data = getSystemXH5PContent();

	?>
	<a href="data:application/json;base64,<?php echo base64_encode($data);?>" download="embeddings.json">Download</a>
	<?php
}

function get_h5p_content_by_id($id){
	global $wpdb;
	return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}h5p_contents WHERE id = {$id}", ARRAY_A);
}

function get_h5p_content_by_slug($slug){
	global $wpdb;
	return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}h5p_contents WHERE slug = '{$slug}'", ARRAY_A);
}

function get_h5p_content_tags($id){
	global $wpdb;
	return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}h5p_contents_tags WHERE content_id = '{$id}'", ARRAY_A);
}

function get_h5p_content_tag_name($tagname){
	global $wpdb;
	return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}h5p_tags WHERE name = '{$tagname}'", ARRAY_A);
}

function insert_empty_h5p_translation($original_data, $language) {
	global $wpdb;
	$original_data['slug'] = $original_data['slug']."_".$language;
	$original_data['default_language'] = $language;
	unset($original_data['id']);
	$wpdb->insert("{$wpdb->prefix}h5p_contents", $original_data);
}



function import_translation($data, $language = "fr") {
	global $wpdb;
	$original_data = get_h5p_content_by_id($data['id']);
	if(!$original_data){
		echo "<h1>Couldn't find original H5P content for " . $data['id'] . "</h1>";
		throw new Exception("Couldn't find original H5P content for " . $data['id']);
	}

	$translated_data = get_h5p_content_by_slug($data['slug']."_".$language);
	if(!$translated_data){
		insert_empty_h5p_translation($original_data, $language);
		$translated_data = get_h5p_content_by_slug($data['slug']."_".$language);
		if(!$translated_data){
			echo "<h1>Couldn't create empty h5p translation for " . $data['id'] . "</h1>";
			throw new Exception("Couldn't create empty h5p translation for " . $data['id']);
		}
	}
	$translated_data['title'] = $data['title'];
	$translated_data['parameters'] = json_encode($data['parameters']);
	$translated_data['filtered'] = $translated_data['parameters'];

	$oldtags = get_h5p_content_tags($data['id']);
	$newtags = get_h5p_content_tags($translated_data['id']);
	$langtag = get_h5p_content_tag_name("lang_" . $language);

	// echo "oldtags: " . json_encode($oldtags, JSON_PRETTY_PRINT) . " --- " . "newtags: " . json_encode($newtags, JSON_PRETTY_PRINT);
	global $checklang;
	$checklang = true;

	foreach ($oldtags as $oldtag) {
		global $checktag;
		$checktag = true;
		foreach ($newtags as $newtag) {
			if ($oldtag["tag_id"] == $newtag["tag_id"]) {
				$checktag = false;
			}
		}

		if ($langtag[0]) {
			if ($langtag[0]["id"] == $oldtag["tag_id"]) {
				$checklang = false;
			}
		}


		if ($checktag) {
			global $wpdb;
			$wpdb->insert("{$wpdb->prefix}h5p_contents_tags", array("content_id" => $translated_data['id'], "tag_id" => $oldtag["tag_id"]));
		}
	}

	if ($checklang) {
		global $wpdb;
		$wpdb->insert("{$wpdb->prefix}h5p_contents_tags", array("content_id" => $translated_data['id'], "tag_id" => $langtag[0]["id"]));
	}


	// also copy the asset folder to the new content path
	$uploadedPath = dirname(__DIR__, 3) . "/uploads/h5p/content/";

	recurse_copy_dir($uploadedPath . $original_data['id'], $uploadedPath . $translated_data['id']);
	/*
	try {
		recurse_copy_dir($uploadedPath . $original_data['id'], $uploadedPath . $translated_data['id']);
	} catch (\Throwable $th) {
		echo "Fehler: " . $th->getMessage() . "\n";
	}*/

	$wpdb->update("{$wpdb->prefix}h5p_contents", $translated_data, ["id" => $translated_data['id']]);
}

function getFolder() {
	if ($_SERVER["REQUEST_METHOD"] != "POST") { return;}
	// if (!$_REQUEST['files']) { return;}
	if (!$_REQUEST['importsubmit']) { return;}
	if (!$_REQUEST['language']) { return;}
	if(isset($_POST['importsubmit'])){

		foreach($_FILES["uploadedfiles"]['tmp_name'] as $file){
			$json = file_get_contents($file);
			$data = json_decode($json, true);
			unlink($file);
			import_translation($data, $_REQUEST['language']);
			// echo "<pre>";
			// print_r(json_encode($data, JSON_PRETTY_PRINT));
			// echo "</pre>";
		}
	}
	?>
	<?php
}

function getFileUploads($path = "/tmp") {
	$target_dir = "/uploads";
	$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
	$files = array_values(array_diff(scandir(__DIR__ .$path), array('.', '..')));
	if (is_dir($path)) {
		if ($dh = opendir($path)) {
			while (($file = readdir($dh)) !== false) {
				echo "filename: .".$file."<br />";
			}
			closedir($dh);
		}
	}
}

function getAllH5PContent() {
	global $wpdb;
	return $wpdb->get_results("SELECT id, title, slug, parameters FROM {$wpdb->prefix}h5p_contents", ARRAY_A);
}

function getAllTagContent( $tag = "emr") {
	global $wpdb;
	return $wpdb->get_results(
		"SELECT wp_h5p_contents.id, wp_h5p_contents.title, wp_h5p_contents.slug, wp_h5p_contents.parameters
		FROM wp_h5p_tags
		LEFT JOIN wp_h5p_contents_tags ON wp_h5p_contents_tags.tag_id = wp_h5p_tags.id
		INNER JOIN wp_h5p_contents ON wp_h5p_contents.id = wp_h5p_contents_tags.content_id
		WHERE wp_h5p_tags.name = '{$tag}'", ARRAY_A);
}

function getSystemXH5PContent() {
	global $wpdb;

	$tagMap = [];
	$tags = $wpdb->get_results("SELECT wp_h5p_contents_tags.content_id, wp_h5p_tags.name
		 FROM wp_h5p_contents_tags
		 LEFT JOIN wp_h5p_tags ON wp_h5p_tags.id = wp_h5p_contents_tags.tag_id;", ARRAY_A);

	foreach($tags as $t){
		$id = strval($t['content_id']);
		if(!isset($map[$id])){
			$tagMap[$id] = [];
		}
		$tagMap[$id][$t['name']] = $t['name'];
	}

	$content = $wpdb->get_results("SELECT id, title, slug FROM wp_h5p_contents", ARRAY_A);
	$map = [];
	foreach($content  as $c){
		$langFound = false;
		if(isset($tagMap[$c['id']])){
			foreach($tagMap[$c['id']] as $t){
				if(substr($t, 0, 5) == "lang_"){
					$lang = substr($t, 5);
					$map[$c['title']][$lang] = $c['id'];
					$langFound = true;
				}
			}
		}
		if(!$langFound){
			$map[$c['title']]['de'] = $c['id'];
		}
	}

	$out = [];
	foreach($map as $title => $m){
		if(count($m) <= 1){
			continue;
		}
		$out[$title] = $m;
	}
	return json_encode($out, JSON_PRETTY_PRINT);
}

function recurse_copy_dir($src,$dst) {
	$dir = opendir($src);
	if (!$dir) {
		return;
	}
	@mkdir($dst, 01774);
	chmod($dst, 01774);
	while(false !== ( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
			if ( is_dir($src . '/' . $file) ) {
				recurse_copy_dir($src . '/' . $file,$dst . '/' . $file);
			}
			else {
				copy($src . '/' . $file,$dst . '/' . $file);
			}
		}
	}
	closedir($dir);
}