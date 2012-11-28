<?php

/* main */

main();

/* function */

function main() {
$POST_Word = $_POST['word']; // ユーザからの入力単語

/* セッション管理 */
if (isset($_GET['reset'])) {
	gameReset();
} else {
	session_start();
	if (!isset($_SESSION['postWordHistory'])) gameStart();
}

if (isset($POST_Word) and !isset($_SESSION['isGame'][0]) and $POST_Word != "") $_SESSION['postWordHistory'][] = $POST_Word; // POST履歴へpush
if (isset($POST_Word)) { // ゲームトリガー
	if (empty($_SESSION['Timer'][0])) $_SESSION['Timer'][] = time() + 300; // 300 is 5min.
	$return = gamePlay($POST_Word);
	if (!empty($return)) $display = $return;
}

require_once('html_header.inc');
require_once('html_body.inc');
if (!isset($_SESSION['isGame'][0]) and isset($_GET['dic']) || isset($_GET['history'])) {
	gameLose();
	$display = "不正行為を検出しました。あなたは強制的に負けです。";
	require_once('html_form.inc');
} else if (isset($_GET['dic'])) {
	require_once('html_result_dic.inc');
} else if (isset($_GET['history'])) {
	require_once('html_result_history.inc');
} else {
	require_once('html_form.inc');
}
require_once('html_footer.inc');
}

function gameStart() {
	$_SESSION['isGame'] = $_SESSION['Timer'] = $_SESSION['postWordHistory'] = $_SESSION['contentWordHistory'] = $_SESSION['realtimeDic'] = array();
}

function gameReset() {
	session_start();
	$_SESSION = array();
	session_destroy();
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: ./");
}

function gameWin() {
	$_SESSION['isGame'][0] = TRUE;
}

function gameLose() {
	$_SESSION['isGame'][0] = FALSE;
}

function gamePlay($POST_Word) {
	/* ゲーム状況 */
	if ($_SESSION['isGame'][0] === TRUE) return "あなたは既にこのゲームに勝利しています。";
	if ($_SESSION['isGame'][0] === FALSE) return "あなたは既にこのゲームに負けています。";
	/* 経過時間確認 */
	if (time() >= $_SESSION['Timer'][0] and !isset($_SESSION['isGame'][0])) {
		gameLose();
		return "時間切れです。あなたの負けです。";
	}
	/* 日本語判定 */
	if ($POST_Word == "") return "入力文字列が空です。";
	if (preg_match("/[a-zA-Z0-9]/iu", $POST_Word)) return "日本語ではありません。";
	$POST_Word = normalizeString($POST_Word); // 文字列の正規化
	/* 単語かどうか */
	$uuid = uniqid();
	$postTempFile = makeTempFile("post_{$uuid}", $POST_Word);
	$postMeCabResult = parseMeCab($postTempFile);
	if (isset($postMeCabResult[2])) return "{$POST_Word}は分解可能です。単語を入力してください。";
	/* 名詞かどうか */
	$postWord = parseMeCabResult($postMeCabResult[0]);
	if (preg_match("/^(名詞)+$/u", $postWord[1]) == 0 or empty($postWord[9])) return "{$POST_Word}は名詞ではありません。";
	/* 既に使った単語か */
	for ($i = 0; $i < count($_SESSION['postWordHistory']) - 1; $i++) {
		if (preg_match("/^({$POST_Word})+$/iu", $_SESSION['postWordHistory'][$i]) == 1) {
			gameLose();
			return "{$POST_Word}は既に使った単語です。あなたの負けです。";
		}
	}
	for ($i = 0; $i < count($_SESSION['contentWordHistory']); $i++) {
		if (preg_match("/^({$POST_Word})+$/iu", $_SESSION['contentWordHistory'][$i]) == 1) {
			gameLose();
			return "{$POST_Word}は既に使った単語です。あなたの負けです。";
		}
	}
	/* ん, で終わらないか */
	$postLastChar = getLastChar($postWord[9]);
	if (preg_match("/(ん)+$/iu", $postLastChar) == 1) {
		gameLose();
		return "ん, で終わっています。あなたの負けです。";
	}
	/* 促音, 拗音対策 */
	if (preg_match("/(ぁ|ぃ|ぅ|ぇ|ぉ|っ|ゃ|ゅ|ょ)+$/iu", $postLastChar) == 1) return "最後の読みが促音や拗音の単語は使えません。別の単語を入力してください。";
	/* しりとりになっているか */
	if (!empty($_SESSION['contentWordHistory'])) {
		$postBeginChar = getBeginChar($postWord[9]);
		$arCount = count($_SESSION['contentWordHistory']) -1;
		$recentContentWord = $_SESSION['contentWordHistory'][$arCount];
		$recentContentLastChar = getLastChar($_SESSION['realtimeDic'][$recentContentWord]['yomi']);
		if (preg_match("/^({$recentContentLastChar})+$/iu", $postBeginChar) == 0) {
			gameLose();
			return "{$POST_Word}はしりとりになっていません。あなたの負けです。";
		}
	}

	$twResult = twSearchAPI($postLastChar); // 戻り値はオブジェクト
	$lastCharWordList = Sammlung($twResult); // 単語蒐集
	$twResult = twSearchAPI($postWord[0]);
	$postKeywordWordList = Sammlung($twResult);
	$wordList = array_merge($lastCharWordList, $postKeywordWordList);
	$_SESSION['realtimeDic'] = array_merge($_SESSION['realtimeDic'], $wordList);
	$nextWord = searchNextWord($postLastChar);
	if (empty($nextWord)) { // CPUの負け
		$nextWord = "返す言葉が見つかりません。あなたの勝ちです。";
		gameWin();
	} else {
		$_SESSION['contentWordHistory'][] = $nextWord;
	}
	return $nextWord;
}

function parseMeCab($docmentFile) {
	$mecabExePath = ''; // 実行する mecab.exe のパス
	// $cmdLine = " --unk-feature none";
	if (file_exists($docmentFile)) {
		exec("{$mecabExePath} {$docmentFile}{$cmdLine}", $result);
		mb_convert_variables('UTF-8', $result);
		unlink($docmentFile);
		return $result;
	} else {
		return FALSE;
	}
}

function parseMeCabResult($result) {
	$contentTemp = explode(",", $result);
	$contentTemp2 = explode(",", $result);
	$contentTemp = explode("\t", $contentTemp[0]);
	$contentWord = array_merge($contentTemp, $contentTemp2);
	unset($contentWord[2]);
	$contentWord[9] = mb_convert_kana($contentWord[9], 'c', 'UTF-8');
	return $contentWord;
}

function makeTempFile($fileName, $body) {
	$filePath = ""; // 作業用ファイル名 ex. ./temp/{$$fileName}.txt
	$handle = fopen($filePath, 'w+');
	flock($handle, LOCK_EX);
	fseek($handle, 0);
	fwrite($handle, $body);
	flock($handle, LOCK_UN);
	fclose($handle);
	return $filePath;
}

function normalizeString($String) {
	$String = mb_convert_kana($String, 'KV', 'UTF-8'); // 半角カタカナを全角
	$String = preg_replace("/(ー)+$/iu", "", $String); // 表記揺れ対策
/*	$String = preg_replace("/(ぁ)+$/iu", "あ", $String);
	$String = preg_replace("/(ぃ)+$/iu", "い", $String);
	$String = preg_replace("/(ぅ)+$/iu", "う", $String);
	$String = preg_replace("/(ぇ)+$/iu", "え", $String);
	$String = preg_replace("/(ぉ)+$/iu", "お", $String);
	$String = preg_replace("/(っ)+$/iu", "つ", $String); // 促音対策
	$String = preg_replace("/(ゃ)+$/iu", "や", $String);
	$String = preg_replace("/(ゅ)+$/iu", "ゆ", $String);
	$String = preg_replace("/(ょ)+$/iu", "よ", $String); */
	return $String;
}

function getLastChar($String) {
	mb_language('Japanese');
	mb_internal_encoding('UTF-8');
	$lastChar = mb_substr($String, -1, 2);
	$kanaLastChar = mb_convert_kana($lastChar, 'c', 'UTF-8');
	return $kanaLastChar;
}

function getBeginChar($String) {
	mb_language('Japanese');
	mb_internal_encoding('UTF-8');
	$beginChar = mb_substr($String, 0, 1);
	$kanaBeginChar = mb_convert_kana($beginChar, 'c', 'UTF-8');
	return $kanaBeginChar;
}

function twSearchAPI($keyword) {
	$xml = simplexml_load_file("http://search.twitter.com/search.atom?q={$keyword}&rpp=50&lang=ja");
	return $xml;
}

function Sammlung($twResult) {
	$wordListIndex = $wordList = array();
	foreach ($twResult->entry as $entry) {
		$content = $entry->content;
		$uuid = uniqid();
		$contentTempFile = makeTempFile("content_{$uuid}", $content);
		$contentMeCabResult = parseMeCab($contentTempFile);
		foreach ($contentMeCabResult as $val) {
			$contentWord = parseMeCabResult($val);
			if (!isset($contentWord[1])) $contentWord[1] = "";
			if (!isset($contentWord[9]) or $contentWord[9] == "*") $contentWord[9] = "";
			$wordLastChar = getLastChar($contentWord[9]);
			if (preg_match("/^(名詞)+$/u", $contentWord[1]) == 1 and !empty($contentWord[9]) and preg_match("/^(ん|ー|ぁ|ぃ|ぅ|ぇ|ぉ|っ|ゃ|ゅ|ょ)+$/iu", $wordLastChar) == 0) {
				if (array_search($contentWord[0], $wordListIndex) === FALSE) { // 新規単語
					$wordList["{$contentWord[0]}"] = array('word' => $contentWord[0],'hinshi' => $contentWord[1], 'yomi' => $contentWord[9]);
					$wordListIndex[] = $contentWord[0];
				}
			}
		}
	}
	return $wordList;
}

function searchNextWord($String) {
	$nextWord = "";
	$keys = array_keys($_SESSION['realtimeDic']);
	foreach ($keys as $key) {
		$contentBeginChar = getBeginChar($_SESSION['realtimeDic'][$key]['yomi']);
		if (preg_match("/^({$String})+$/iu", $contentBeginChar) == 1 and array_search($key, $_SESSION['contentWordHistory']) === FALSE and array_search($key, $_SESSION['postWordHistory']) === FALSE) $nextWord = $key;
		if (!empty($nextWord)) break;
	}
	return $nextWord;
}