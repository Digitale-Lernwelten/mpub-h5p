<?php

function dilewe_h5p_tools_render()
{
?>

<h3>Dilewe H5P Tools</h3>

<form method="POST" action="<?php echo admin_url( 'tools.php?page=dilewe_h5p_tools' ); ?>">
    <input type="submit" name="verb" value="Export" />
</form>

<?php
getExportRender();
}

function getExportRender() {
	if ($_SERVER["REQUEST_METHOD"] != "POST") { return;}
	if (!$_REQUEST['verb']) { return;}
	
	$name = $_REQUEST['verb'];
	if ($name != "Export") { return;}
	
	$result = getAllH5PContent();

	foreach($result as &$o){
		$o['parameters'] = json_decode($o['parameters'],true);
	}

	$data = htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT));

	$file = "export.json";
	$txt = fopen($file, "w") or die("Unable to open file");
	fwrite($txt, $data);
	fclose($txt);

	header('Content-Description: File Transfer');
	header('Content-Disposition: attachment; filename='.basename($file));
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($file));
	header("Content-Type: text/plain");
	readfile($file);

	?>

	<h4>datei wird zum herunterladen erstellt, einen Augenblick</h4>

	<?php
}

function getAllH5PContent() {
	global $wpdb;
    return $wpdb->get_results("SELECT id, title, slug, parameters FROM {$wpdb->prefix}h5p_contents", ARRAY_A);
}
