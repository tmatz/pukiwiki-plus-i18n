<?php
// PukiWiki Plus! - Yet another WikiWikiWeb clone.
// $Id: init.php,v 1.54.35 2009/05/09 21:26:00 upk Exp $
// Copyright (C)
//   2005-2009 PukiWiki Plus! Team
//   2002-2007 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// Init PukiWiki here
// Plus!I18N:(policy)not merge official cvs(1.44->1.45)
// Plus!NOTE:(policy)not merge official cvs(1.51->1.52) See Question/181

// PukiWiki version / Copyright / License

define('S_VERSION', '1.4.7plus-u2-i18n');
define('S_COPYRIGHT',
	'<strong>PukiWiki Plus! ' . S_VERSION . '</strong>' .
	' Copyright &copy; 2001-2009' .
	' <a href="http://pukiwiki.cafelounge.net/plus/">PukiWiki Plus! Team</a>.' .
	' License is <a href="http://www.gnu.org/licenses/old-licenses/gpl-2.0.html">GPLv2</a>.<br />' .
	' Based on <a href="http://pukiwiki.sourceforge.jp/">"PukiWiki"</a>'
);

/////////////////////////////////////////////////
// Init server variables

// Compat and suppress notices
if (!isset($HTTP_SERVER_VARS)) $HTTP_SERVER_VARS = array();

foreach (array('SCRIPT_NAME', 'SERVER_ADMIN', 'SERVER_NAME',
	'SERVER_PORT', 'SERVER_SOFTWARE') as $key) {
	define($key, isset($_SERVER[$key]) ? $_SERVER[$key] : '');
	unset(${$key}, $_SERVER[$key], $HTTP_SERVER_VARS[$key]);
}

/////////////////////////////////////////////////
// Init grobal variables

$foot_explain = array();	// Footnotes
$related      = array();	// Related pages
$head_tags    = array();	// XHTML tags in <head></head>
$foot_tags    = array();

/////////////////////////////////////////////////
// Require INI_FILE

define('USR_INI_FILE', add_homedir('pukiwiki.usr.ini.php'));
$read_usr_ini_file = false;
if (file_exists(USR_INI_FILE) && is_readable(USR_INI_FILE)) {
	require(USR_INI_FILE);
	$read_usr_ini_file = true;
}

define('INI_FILE',  add_homedir('pukiwiki.ini.php'));
$die = '';
if (! file_exists(INI_FILE) || ! is_readable(INI_FILE)) {
	$die .= 'File is not found. (INI_FILE)' . "\n";
} else {
	require(INI_FILE);
}
if ($die) die_message(nl2br("\n\n" . $die));

if ($read_usr_ini_file) {
	require(USR_INI_FILE);
	unset($read_usr_ini_file);
}

/////////////////////////////////////////////////
// I18N
set_language();
set_time();
require(LIB_DIR . 'public_holiday.php');

// Init Resource(for gettext)
if (! ini_get('safe_mode')) putenv('LC_ALL=' . PO_LANG);
setlocale(LC_ALL, PO_LANG);
bindtextdomain(DOMAIN, LANG_DIR);
bind_textdomain_codeset(DOMAIN, SOURCE_ENCODING);
textdomain(DOMAIN);

/////////////////////////////////////////////////
// リソースファイルの読み込み
require(LIB_DIR . 'resource.php');
// Init encoding hint
// define('PKWK_ENCODING_HINT', isset($_LANG['encode_hint']) ? $_LANG['encode_hint'] : '');
define('PKWK_ENCODING_HINT', (isset($_LANG['encode_hint']) && $_LANG['encode_hint'] != 'encode_hint') ? $_LANG['encode_hint'] : '');
// unset($_LANG['encode_hint']);

/////////////////////////////////////////////////
// INI_FILE: Init $script

if (isset($script)) {
	init_script_uri($script); // Init manually
} else {
	$script = init_script_uri(); // Init automatically
}

/////////////////////////////////////////////////
// INI_FILE: $agents:  UserAgentの識別

$ua = 'HTTP_USER_AGENT';
$user_agent = $matches = array();

$user_agent['agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
// unset(${$ua}, $_SERVER[$ua], $HTTP_SERVER_VARS[$ua], $ua);	// safety

foreach ($agents as $agent) {
	if (preg_match($agent['pattern'], $user_agent['agent'], $matches)) {
		$user_agent['profile'] = isset($agent['profile']) ? $agent['profile'] : '';
		$user_agent['name']    = isset($matches[1]) ? $matches[1] : '';	// device or browser name
		$user_agent['vers']    = isset($matches[2]) ? $matches[2] : ''; // 's version
		break;
	}
}
unset($agents, $matches);

// Profile-related init and setting
define('UA_PROFILE', isset($user_agent['profile']) ? $user_agent['profile'] : '');

define('UA_INI_FILE', add_homedir(UA_PROFILE . '.ini.php'));
if (! file_exists(UA_INI_FILE) || ! is_readable(UA_INI_FILE)) {
	die_message('UA_INI_FILE for "' . UA_PROFILE . '" not found.');
} else {
	require(UA_INI_FILE); // Also manually
}

define('UA_NAME', isset($user_agent['name']) ? $user_agent['name'] : '');
define('UA_VERS', isset($user_agent['vers']) ? $user_agent['vers'] : '');
unset($user_agent);	// Unset after reading UA_INI_FILE

/////////////////////////////////////////////////
// ディレクトリのチェック

$die = '';
foreach(array('DATA_DIR', 'DIFF_DIR', 'BACKUP_DIR', 'CACHE_DIR') as $dir){
	if (! is_writable(constant($dir)))
		$die .= _( 'Directory is not found or not writable' ) . ' (' . $dir . ')' . "\n";
}

// 設定ファイルの変数チェック
$temp = '';
foreach(array('rss_max', 'page_title', 'note_hr', 'related_link', 'show_passage',
	'rule_related_str', 'load_template_func') as $var){
	if (! isset(${$var})) $temp .= '$' . $var . "\n";
}
if ($temp) {
	if ($die) $die .= "\n";	// A breath
	$die .= _( 'Variable(s) not found: (Maybe the old *.ini.php?)' ) . "\n" . $temp;
}

$temp = '';
foreach(array('LANG', 'PLUGIN_DIR') as $def){
	if (! defined($def)) $temp .= $def . "\n";
}
if ($temp) {
	if ($die) $die .= "\n";	// A breath
	$die .= _( 'Define(s) not found: (Maybe the old *.ini.php?)' ) . "\n" . $temp;
}

if($die) die_message(nl2br("\n\n" . $die));
unset($die, $temp);

/////////////////////////////////////////////////
// 必須のページが存在しなければ、空のファイルを作成する

foreach(array($defaultpage, $whatsnew, $interwiki) as $page){
	if (! is_page($page)) pkwk_touch_file(get_filename($page));
}

/////////////////////////////////////////////////
// 外部からくる変数のチェック

// Prohibit $_GET attack
foreach (array('msg', 'pass') as $key) {
	if (isset($_GET[$key])) die_message('Sorry, already reserved: ' . $key . '=');
}

// Expire risk
unset($HTTP_GET_VARS, $HTTP_POST_VARS);	//, 'SERVER', 'ENV', 'SESSION', ...
unset($_REQUEST);	// Considered harmful

// Remove null character etc.
$_GET    = input_filter($_GET);
$_POST   = input_filter($_POST);
$_COOKIE = input_filter($_COOKIE);

// 文字コード変換 ($_POST)
// <form> で送信された文字 (ブラウザがエンコードしたデータ) のコードを変換
// POST method は常に form 経由なので、必ず変換する
//
if (isset($_POST['encode_hint']) && $_POST['encode_hint'] != '') {
	// do_plugin_xxx() の中で、<form> に encode_hint を仕込んでいるので、
	// encode_hint を用いてコード検出する。
	// 全体を見てコード検出すると、機種依存文字や、妙なバイナリ
	// コードが混入した場合に、コード検出に失敗する恐れがある。
	$encode = mb_detect_encoding($_POST['encode_hint']);
	mb_convert_variables(SOURCE_ENCODING, $encode, $_POST);

} else if (isset($_POST['charset']) && $_POST['charset'] != '') {
	// TrackBack Ping で指定されていることがある
	// うまくいかない場合は自動検出に切り替え
	if (mb_convert_variables(SOURCE_ENCODING,
	    $_POST['charset'], $_POST) !== $_POST['charset']) {
		mb_convert_variables(SOURCE_ENCODING, 'auto', $_POST);
	}

} else if (! empty($_POST)) {
	// 全部まとめて、自動検出／変換
	mb_convert_variables(SOURCE_ENCODING, 'auto', $_POST);
}

// 文字コード変換 ($_GET)
// GET method は form からの場合と、<a href="http://script/?key=value> の場合がある
// <a href...> の場合は、サーバーが rawurlencode しているので、コード変換は不要
if (isset($_GET['encode_hint']) && $_GET['encode_hint'] != '')
{
	// form 経由の場合は、ブラウザがエンコードしているので、コード検出・変換が必要。
	// encode_hint が含まれているはずなので、それを見て、コード検出した後、変換する。
	// 理由は、post と同様
	$encode = mb_detect_encoding($_GET['encode_hint']);
	mb_convert_variables(SOURCE_ENCODING, $encode, $_GET);
}


/////////////////////////////////////////////////
// QUERY_STRINGを取得

$arg = '';
if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
        $arg = & $_SERVER['QUERY_STRING'];
} else if (isset($_SERVER['argv']) && ! empty($_SERVER['argv'])) {
        $arg = & $_SERVER['argv'][0];
}
if (PKWK_QUERY_STRING_MAX && strlen($arg) > PKWK_QUERY_STRING_MAX) {
        // Something nasty attack?
        pkwk_common_headers();
        echo( _('Query string too long') );
        exit;
}
$arg = input_filter($arg); // \0 除去
// for QA/250
$arg = str_replace('+','%20',$arg);

// unset QUERY_STRINGs
//foreach (array('QUERY_STRING', 'argv', 'argc') as $key) {
// For OpenID Lib (use QUERY_STRING).
foreach (array('argv', 'argc') as $key) {
	unset(${$key}, $_SERVER[$key], $HTTP_SERVER_VARS[$key]);
}
// $_SERVER['REQUEST_URI'] is used at func.php NOW
unset($REQUEST_URI, $HTTP_SERVER_VARS['REQUEST_URI']);

// mb_convert_variablesのバグ(?)対策: 配列で渡さないと落ちる
$arg = array($arg);
mb_convert_variables(SOURCE_ENCODING, 'auto', $arg);
$arg = $arg[0];

/////////////////////////////////////////////////
// QUERY_STRINGを分解してコード変換し、$_GET に上書き

// URI を urlencode せずに入力した場合に対処する
$matches = array();
foreach (explode('&', $arg) as $key_and_value) {
	if (preg_match('/^([^=]+)=(.+)/', $key_and_value, $matches) &&
	    (mb_detect_encoding($matches[2]) != 'ASCII' || $matches[1] == 'pukiwiki')) {
		$_GET[$matches[1]] = $matches[2];
	}
}
unset($matches);

/////////////////////////////////////////////////
// GET, POST, COOKIE

$get    = & $_GET;
$post   = & $_POST;
$cookie = & $_COOKIE;

// GET + POST = $vars
if (empty($_POST)) {
	$method = 'GET';
	$vars = & $_GET;  // Major pattern: Read-only access via GET
} else if (empty($_GET)) {
	$method = 'POST';
	$vars = & $_POST; // Minor pattern: Write access via POST etc.
} else {
	$method = 'GET and POST';
	$vars = array_merge($_GET, $_POST); // Considered reliable than $_REQUEST
}

// 入力チェック: 'cmd=' prohibits nasty 'plugin='
if (isset($vars['cmd']) && isset($vars['plugin']))
	die( _( 'Using both cmd= and plugin= is not allowed' ) );

// 入力チェック: cmd, plugin の文字列は英数字以外ありえない
foreach(array('cmd', 'plugin') as $var) {
	if (isset($vars[$var]) && ! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $vars[$var]))
		unset($get[$var], $post[$var], $vars[$var]);
}

// 整形: page, strip_bracket()
if (isset($vars['page'])) {
	$get['page'] = $post['page'] = $vars['page']  = strip_bracket($vars['page']);
} else {
	$get['page'] = $post['page'] = $vars['page'] = '';
}

// 整形: msg, 改行を取り除く
if (isset($vars['msg'])) {
	$get['msg'] = $post['msg'] = $vars['msg'] = str_replace("\r", '', $vars['msg']);
}

// 後方互換性 (?md5=...)
if (isset($get['md5']) && $get['md5'] != '' &&
    ! isset($vars['cmd']) && ! isset($vars['plugin'])) {
	$get['cmd'] = $post['cmd'] = $vars['cmd'] = 'md5';
}

// TrackBack Ping
if (isset($vars['tb_id']) && $vars['tb_id'] != '') {
	$get['cmd'] = $post['cmd'] = $vars['cmd'] = 'tb';
}

// cmdもpluginも指定されていない場合は、QUERY_STRINGをページ名かInterWikiNameであるとみなす
if (! isset($vars['cmd']) && ! isset($vars['plugin'])) {

	$get['cmd']  = $post['cmd']  = $vars['cmd']  = 'read';

	$argx = explode('&', $arg);
	$arg = is_array($argx) ? $argx[0]:$argx;
	if ($arg == '') $arg = $defaultpage;
	$arg = rawurldecode($arg);
	$arg = strip_bracket($arg);
	$arg = input_filter($arg);
	$get['page'] = $post['page'] = $vars['page'] = $arg;
}

/////////////////////////////////////////////////
// 初期設定($WikiName,$BracketNameなど)
// $WikiName = '[A-Z][a-z]+(?:[A-Z][a-z]+)+';
// $WikiName = '\b[A-Z][a-z]+(?:[A-Z][a-z]+)+\b';
// $WikiName = '(?<![[:alnum:]])(?:[[:upper:]][[:lower:]]+){2,}(?![[:alnum:]])';
// $WikiName = '(?<!\w)(?:[A-Z][a-z]+){2,}(?!\w)';

// BugTrack/304暫定対処
$WikiName = '(?:[A-Z][a-z]+){2,}(?!\w)';

// $BracketName = ':?[^\s\]#&<>":]+:?';
$BracketName = '(?!\s):?[^\r\n\t\f\[\]<>#&":]+:?(?<!\s)';

// InterWiki
$InterWikiName = '(\[\[)?((?:(?!\s|:|\]\]).)+):(.+)(?(1)\]\])';

// 注釈
$NotePattern = '/\(\(((?:(?>(?:(?!\(\()(?!\)\)(?:[^\)]|$)).)+)|(?R))*)\)\)/ex';

/////////////////////////////////////////////////
// 初期設定(ユーザ定義ルール読み込み)
require(add_homedir('rules.ini.php'));

/////////////////////////////////////////////////
// 初期設定(その他のグローバル変数)

// 現在時刻
$now = format_date(UTIME);

// 日時置換ルールを$line_rulesに加える
if ($usedatetime) $line_rules = array_merge($datetime_rules,$line_rules);
unset($datetime_rules);

// フェイスマークを$line_rulesに加える
if ($usefacemark) $line_rules = array_merge($facemark_rules,$line_rules);
unset($facemark_rules);

// 実体参照パターンおよびシステムで使用するパターンを$line_rulesに加える
//$entity_pattern = '[a-zA-Z0-9]{2,8}';
$entity_pattern = trim(join('', file(CACHE_DIR . PKWK_ENTITIES_REGEX_CACHE)));

$line_rules = array_merge(array(
	'&amp;(#[0-9]+|#x[0-9a-f]+|' . $entity_pattern . ');' => '&$1;',
	"\r"          => '<br />' . "\n",	/* 行末にチルダは改行 */
), $line_rules);

?>
