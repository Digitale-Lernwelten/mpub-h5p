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

<?php
getExportRender();
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
	
	$result = getAllTagContent($tag);

	foreach($result as &$o){
		$o['parameters'] = json_decode($o['parameters'],true);
	}

	$data =  gzencode(json_encode($result, JSON_PRETTY_PRINT));

	?>

	<h4>tag <?php echo $tag ?> gewählt:</h4>
	<a href="data:application/json;base64,<?php echo base64_encode($data);?>" download="data.json.gz">Download</a>

	<?php	
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
