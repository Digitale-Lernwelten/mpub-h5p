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
	
	$result = getAllEMRContent();

	foreach($result as &$o){
		$o['parameters'] = json_decode($o['parameters'],true);
	}

	$data =  gzencode(json_encode($result, JSON_PRETTY_PRINT));

	?>

	<h4>datei download</h4>
	<a href="data:application/json;base64,<?php echo base64_encode($data);?>" download="data.json.gz">Download</a>

	<?php
	

	
}

function getAllH5PContent() {
	global $wpdb;
    return $wpdb->get_results("SELECT id, title, slug, parameters FROM {$wpdb->prefix}h5p_contents", ARRAY_A);
}


function getAllEMRContent() { 
	global $wpdb;
    return $wpdb->get_results(
		"SELECT wp_h5p_contents.id, wp_h5p_contents.title, wp_h5p_contents.slug, wp_h5p_contents.parameters
		FROM wp_h5p_tags
		LEFT JOIN wp_h5p_contents_tags ON wp_h5p_contents_tags.tag_id = wp_h5p_tags.id
		INNER JOIN wp_h5p_contents ON wp_h5p_contents.id = wp_h5p_contents_tags.content_id
		WHERE wp_h5p_tags.name = 'emr'", ARRAY_A);
}
