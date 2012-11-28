<!-- Header -->
<div class="container" style="text-align: center;">
	<div class="span11" id="Alpha">
		<h1>しりとりで勝負です！</h1>
		<p>twitterのリアルタイム検索APIから辞書を一から構築します。<br>その辞書から無作為にしりとりになる単語を決定します。<br>※厳密な品詞解析は未実装なので怪しい処理もします。</p>

<?php
if (isset($display)) print("<h2>あなた:</h2><h3>{$POST_Word}</h3><br><h2>CPU:</h2><h3>{$display}</h3>\n");
if (isset($_SESSION['isGame'][0])) print("<h2>戦績の確認</h2><a href=\"./?dic\" title=\"辞書展開\" target=\"_self\">リアルタイム辞書</a>&nbsp;/&nbsp;<a href=\"./?history\" title=\"棋譜\" target=\"_self\">棋譜確認</a>\n");
?>
		<p><br></p>
		<form action="./" method="post" enctype="multipart/form-data">
			<fieldset>
				<div class="input-append">
					<input class="span3" id="appendedInput" placeholder="名詞を入力" type="text" name="word" maxlength="32"><span class="add-on">名詞</span><br>
				</div>
			</fieldset>
			<button type="submit" class="btn btn-primary btn-large"><i class="icon-edit icon-white"></i>送信</button>&nbsp;&nbsp;&nbsp;&nbsp;<button class="btn btn-danger btn-large" type="reset"><i class="icon-trash icon-white"></i>クリア</button>
		</form>
		<?php if (!empty($_SESSION['Timer'])) { $limit = $_SESSION['Timer'][0] - time(); print("<h3>残り約{$limit}秒</h3>\n"); } ?>
		<h3>※制限時間は5分です。</h3>
	</div><!-- span11 -->
</div><!-- container -->
<!-- End of Header -->
