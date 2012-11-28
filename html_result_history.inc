<div class="container" style="text-align: center;">
	<div class="span11">
		<h1>しりとりゲーム</h1>
		<h2>棋譜</h2>
		<p><br></p>
		<table border="1" cellpadding="5" align="center">
		<tr><th>手数</th><th>棋士</th><th>単語</th></tr>
<?php
$arHistoryCount = count($_SESSION['postWordHistory']) + count($_SESSION['contentWordHistory']);
$postKey = $contentKey = 0;
for ($i = 0; $i < $arHistoryCount; $i++) {
	$Count = $i + 1;
	if (($i + 2) % 2 == 0) { // Zero除算対策
		print("<tr><td>{$Count}</td><td>あなた</td><td>{$_SESSION['postWordHistory'][$postKey]}</td></tr>\n");
		$postKey++;
	} else {
		print("<tr><td>{$Count}</td><td>CPU</td><td>{$_SESSION['contentWordHistory'][$contentKey]}</td></tr>\n");
		$contentKey++;
	}
}
?>
		</table>
		<p>※空白はメッセージです。</p>
	</div><!-- span11 -->
</div><!-- container 