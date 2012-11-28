<div class="container" style="text-align: center;">
	<div class="span11">
		<h1>形態素解析</h1>
		<h2>リアルタイム辞書</h2>
		<p><br></p>
		<table border="1" cellpadding="5" align="center">
			<tr><th>単語</th><th>読み</th><th>品詞</th></tr>
<?php
$dicCounter = 0;
foreach ($_SESSION['realtimeDic'] as $val) {
	$dicCounter++;
	print("<tr><td>{$val['word']}</td><td>{$val['yomi']}</td><td>{$val['hinshi']}</td></tr>\n");
}
?>
		</table>
		<p><?php print("蒐集単語数は{$dicCounter}件です。"); ?></p>
	</div><!-- span11 -->
</div><!-- container -->