<a name="top"></a>
<h1>SquirrelMail IMAP functions</h1>
<?
	include ("sqimap_config.php3");

	echo "<ul>\n";
	for ($i = 0; $i < count($name); $i++) {
		echo "<ul>\n";
		echo "   <a href=\"#$name[$i]\">sqimap_$name[$i]</a> $params[$i]\n";
		echo "</ul>\n";
	}
	echo "</ul>\n";
	
	for ($i = 0; $i < count($name); $i++) {
		?>
			<br>
			<hr>
			<a name="<? echo $name[$i] ?>"></a>
			<h2>sqimap_<? echo $name[$i] ?></h2>
         <a href="#top">go to top</a>
			<ul>
				Implementation:
				<ul>
					<code>
						sqimap_<? echo $name[$i] ?> <? echo $params[$i] ?> 
					</code>
				</ul>
				Explanation:
				<ul>
					<? echo $explain[$i] ?><br>
					<br>
					<?
						for ($p = 0; $p < count($params_desc[$i]); $p++) {
							echo "<li><b>" . $params_desc[$i][$p]["name"] . "</b> - (".$params_desc[$i][$p]["type"].") &nbsp;&nbsp; " . $params_desc[$i][$p]["desc"] . "</li>\n";
						}
					?>
				</ul>
				Example:
				<ul>
					<code>
						<? echo $example[$i] ?>
					</code>
				</ul>
			</ul>
			
			<br>
		<?
	}
?>
