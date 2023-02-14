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

	?>
	
	<h4><?php echo $name;?></h4>
	<textarea readonly style="width: 50vw; height: 400px"><?php
		echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT));
	?></textarea>

	<?php
}

function getAllH5PContent() {
	global $wpdb;
    return $wpdb->get_results("SELECT id, title, slug, parameters FROM {$wpdb->prefix}h5p_contents LIMIT 50", ARRAY_A);
}
