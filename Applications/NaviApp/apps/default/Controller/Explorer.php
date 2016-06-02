<?php

namespace Wide\Controller;

use Workerman\Protocols\Http;
use Navigation\Library\Sendfile;

/**
 * OneExplorer Navi
 *
 * OneExplorer 是一个单文件的 php 文件浏览器，主要用于展示服务器目录/文件列表以方便访问和下载等。
 *
 * 项目地址： http://blog.vgot.net/one-explorer/
 * 更新日志： http://blog.vgot.net/archives/oneexplorer-update-log.html
 *
 * OneExplorer Navi 是基于 Navigation 框架的特殊版本，与常规版本主要的区别在于：
 * 只提供文件查看与下载功能，不负责访问 php 文件
 * 基于 Navigation 提供的面向对象结构代码更清晰易维护。
 * 基于 Navigation 与 Workerman 提供的长驻能力，性能更优。
 *
 * Copyright (c) 2008-2016 www.vgot.net
 *
 * @author Pader (ypnow@163.com)
 */
class Explorer extends \Controller {

	const VERSION = '1.5.2 (20160602)';
	const DEFAULT_VIEWMODE = 'icon'; //默认目录列表类型 list|icon
	const BASE_DIR = 'E:\\Downloads';

	//已知文件的类型对应的内嵌图标
	private $types = array(
		'apk' => 'apk.gif',  'asp' => 'code.gif',
		'bmp' => 'img.gif',  'bz2' => 'zip.gif',
		'cab' => 'rar.gif',  'chm' => 'manual.gif', 'css' => 'css.gif',
		'db'  => 'db.gif',   'dir' => 'dir.gif',  'doc' => 'word.gif',  'docx' => 'word.gif',
		'exe' => 'exe.gif',
		'gif' => 'img.gif',  'gz' => 'zip.gif',
		'htm' => 'html.gif',  'html' => 'html.gif',
		'img' => 'cd_page.gif', 'iso' => 'cd.gif',
		'js' => 'js.gif',    'jpg' => 'img.gif',
		'log' => 'txt.gif',
		'mdb' => 'mdb.gif',  'mp3' => 'mp3.gif',
		'php' => 'php.gif',  'png' => 'img.gif',  'ppt' => 'powerpoint.gif',  'pptx' => 'powerpoint.gif',  'py' => 'py.gif',
		'rar' => 'rar.gif',
		'sql' => 'txt.gif',  'swf' => 'swf.gif',
		'txt' => 'txt.gif',
		'wma' => 'mp3.gif',  'wmv' => 'mp3.gif',
		'xls' => 'excel.gif','xlsx' => 'excel.gif',  'xml' => 'xml.gif',
		'zip' => 'zip.gif'
	);
	
	//检测的文件名编码列表
	private $nameEncodes;
	
	private $time;
	
	public function __construct() {
		parent::__construct();
		$this->nameEncodes = mb_detect_order();

		//Windows 平台下必须加入 GBK 编码检测，CP936=GBK
		if (PHP_OS == 'WINNT' && !in_array('CP936', $this->nameEncodes)) {
			$this->nameEncodes[] = 'CP936';
		}
		
		$_ENV['time'] = time();
	}

	public function index() {
		//浏览模式
		if (!empty($_GET['viewmode'])) {
			$viewMode = $_GET['viewmode'] == 'icon' ? 'icon' : 'list';
			if ($viewMode != self::DEFAULT_VIEWMODE) {
				Http::setcookie('OEviewmode', $viewMode, $this->time + 2592000);
			} else {
				Http::setcookie('OEviewmode', false, $this->time - 3600);
			}
		} else {
			$viewMode = isset($_COOKIE['OEviewmode']) ? $_COOKIE['OEviewmode'] : self::DEFAULT_VIEWMODE;
			if ($viewMode != 'list' && $viewMode != 'icon') $viewMode = self::DEFAULT_VIEWMODE;
		}

		//排序方式
		$sortLimit = '/^(filename|filemtime|type|filesize),(asc|desc)$/';
		$sort = false;

		if (isset($_GET['sort'])) {
			if (preg_match($sortLimit, $_GET['sort'])) {
				$sort = true;
				list($sortField, $sortMethod) = explode(',', $_GET['sort']);
				Http::setcookie('OEsort', $_GET['sort'], $this->time + 2592000);
			} else {
				Http::setcookie('OEsort', false, $this->time - 3600);
			}
		} elseif (isset($_COOKIE['OEsort']) && preg_match($sortLimit, $_COOKIE['OEsort'])) {
			$sort = true;
			list($sortField, $sortMethod) = explode(',', $_COOKIE['OEsort']);
		}

		//获取路径
		$dir = empty($_GET['dir']) ? '' : $this->filterDir($_GET['dir']);
		$path = realpath(self::BASE_DIR.'/'.$dir);

		nvHeader('Content-Type: text/html; charset=gbk');

		if (!is_dir($path)) {
			echo '<h1>目录不存在</h1><a href="javascript:history.go(-1);">点击返回</a> | <a href="?dir=.">到根目录</a>';
			echo $this->footer();
			return;
		}

		$listDir = $listFile = array();

		//读取目录
		if (is_dir($path)) {
			$sortDir = $sortFile = array();
			$isSortDir = $sort && ($sortField == 'filename' || $sortField == 'filemtime');

			$handle = opendir($path);

			while (false !== ($filename = readdir($handle))) {
				if ($filename == '.' || $filename == '..') continue;

				$origName = $filename;
				list($filename, $uri) = $this->getDisplayUri($filename);

				$row = array(
					'filename' => $filename,
					'uri' => $uri,
					'filemtime' => filemtime($path.'/'.$origName)
				);

				if (is_dir($path.'/'.$origName)) {
					$listDir[] = $row;
					$isSortDir && $sortDir[] = $row[$sortField];
				} else {
					$ext = ($sp = strrpos($filename, '.')) !== false ? strtolower(substr($filename, $sp + 1)) : '';

					$row['filesize'] = filesize($path.'/'.$origName);
					$row['filesizeh'] = $this->convertFileSize($row['filesize']);
					$row['type'] = $ext;
					$row['icon'] = isset($this->types[$ext]) ? $this->types[$ext] : 'unknow.gif';
					$listFile[] = $row;

					$sort && $sortFile[] = $row[$sortField];
				}
			}

			closedir($handle);

			//排序
			if ($sort) {
				$sortFlag = $sortMethod == 'asc' ? SORT_ASC : SORT_DESC;
				$sortType =  ($sortField == 'filename' || $sortField == 'type') ? SORT_STRING : SORT_NUMERIC;

				if ($sortType == SORT_STRING) {
					$isSortDir && $sortDir = array_map('strtolower', $sortDir);
					$sortFile = array_map('strtolower', $sortFile);
				}

				$isSortDir && array_multisort($sortDir, $sortFlag, $sortType, $listDir);
				array_multisort($sortFile, $sortFlag, $sortType, $listFile);
			}
		}

		$upDir = $this->encodeUrl(trim(dirname($dir),'./'));

		//标题
		$title = trim($dir,'./');
		if ($title) {
			$du = $this->getDisplayUri($title);
			$title = $du[0];
		} else {
			$title = $_SERVER['HTTP_HOST'];
		}

		//统计
		$countDir = count($listDir);
		$countFile = count($listFile);
		$status = ($countDir == 0 && $countFile == 0) ? '[目录是空的]' : '['.$countDir.' 个目录, '.$countFile.' 个文件]';

		//生成导航
		$bc = $breadCrumb = '';

		if ($dir) {
			foreach (explode('/',$dir) as $row) {
				$d = $bc;
				list($filename, $uri) = $this->getDisplayUri($row);
				$bc .= $bc ? '/'.$uri : $uri;
				$link = "<a href=\"?dir=$bc\">$filename</a>";
				$breadCrumb .= $breadCrumb ? "<em data-addr=\"$d\"></em>".$link : $link;
			}
			if ($countDir) {
				$breadCrumb .= "<em data-addr=\"$bc\"></em>";
			}
		}

		$dir = $this->encodeUrl($dir);
		$current = $dir;

		$dir != '' && $dir = $dir.'/';
		$footer = $this->footer();

		$this->load->view('explorer', compact('title', 'breadCrumb', 'current', 'viewMode', 'dir', 'upDir', 'status',
			'countDir', 'listDir', 'countFile', 'listFile', 'sort', 'sortField', 'sortMethod', 'footer'));
	}

	public function subdir() {
		$path = empty($_GET['dir']) ? '' : $this->filterDir($_GET['dir']);
		$path = realpath(self::BASE_DIR.'/'.$path);

		is_dir($path) || nvExit();

		$ls = array();
		$handle = opendir($path);
		while (FALSE !== ($row = readdir($handle))) {
			if ($row == '.' || $row == '..' || !is_dir($path.'/'.$row)) continue;
			$du = $this->getDisplayUri($row);
			$ls[] = '["'.addslashes($du[0]).'","'.addslashes($du[1]).'"]';
		}
		closedir($handle);
		sort($ls);

		nvHeader('Content-Type: application/x-javascript; charset=gbk');
		echo 'subdirs=['.join(',', $ls).'];';
	}

	/**
	 * 下载目标文件
	 *
	 * @throws \ExitException
	 */
	public function download() {
		$file = $this->input->get('file');
		$file = rawurldecode($file);
		$file = trim(preg_replace('/\/{1,}/', '/', $file), './');

		if ($file == '') {
			nv404();
		}

		$file = realpath(self::BASE_DIR.'/'.$file);

		if (!is_file($file)) {
			nv404();
		}

		$pathinfo = pathinfo($file);

		//发送文件
		$sf = new Sendfile();
		$sf->use304status = true;
		$sf->send($file, array(
			'Content-Disposition' => 'filename="'.$pathinfo['basename'].'";',
			'Content-Transfer-Encoding' => 'binary;'
		));
	}

	/**
	 * 显示内置图片
	 *
	 * @param string $file
	 */
	public function image($file='') {
		switch($file) {
		case 'alert.gif': $img = <<<EOF
R0lGODlhEAAQALMPAP/yl//riv/lUv/3q//fTP/rZf/mfP/ka//hW//ldP/jY+m8ZNJ2Hf/eReCfIf///yH5BAEAAA8ALAAAAAAQABAAAARl8Mm5nFsz57XGOJiWOd4AOOJEmgEQoCkZME
zyxiydFAU8DoEZ46DoaRwAl4GmQBQEvgcyYEjQEAQCVJWsEhFY7VYK8Cpo2UZj6wgmvmj1Guowg9Ny9WXxxeblHA8VFoSFFiEpiREAOw==
EOF;
			break;
		case 'apk.gif': $img = <<<EOF
R0lGODlhEAAQALMPAGylAK3LUNnmrrnTaJ/CNPv898vdj5W9IOTsxuzx1I25DIKyAcPYfJnAJpi/JP///yH5BAEAAA8ALAAAAAAQABAAAARy8MlXxHyo3J1ICASybcewLMOhjQ+gmKgCsA
9j3ILNbELzBIOgMPBoWH4AxELBbC4QAOLDlFA0rleHIoGSMBZVh3i87SLDY7ECKhUQHgtxtrF4EI4TwKHBbBxmLAFaHwoHUiwDgAADNBIEim+NCVIBCRsRADs=
EOF;
			break;
		case 'cd.gif': $img = <<<EOF
R0lGODlhEAAQALMPAGKOvHyhx9Xo+/L9/7bQ7ef1/qnN8sfl/cPc99Xj8dzv/ZrA6qW/2Z241f///////yH5BAEAAA8ALAAAAAAQABAAAASL8MmXWgAgtDR7A0SiFAoBNN0TME5hGMLgOE
wwNYw8LLAzyAxUAqBYKBwE46CgA1QIyYVAgGAyfwRLYsElMDA5UiJx2XIRAAmR8Bo6uYuhOvGCZcywL4BxYIc0BAgHBSQKAgd1CFlDSwoHB4p1dhw4BYeRdQdBEysFkIkCNSkfBAeGiicp
EhUXGRsdEQA7
EOF;
			break;
		case 'cd_page.gif': $img = <<<EOF
R0lGODlhEAAQALMPAHiZzZSv1dfm98HW8FqAveTk5O/y9qrG6/j4+Pn5+fr6+vv7+8nJyfz8/MzMzP///yH5BAEAAA8ALAAAAAAQABAAAASH8L3imL22yO3QamCIMNrGLAaSGkbRFKQ5HL
TAumosHIItBAdWqMHYpQaGRgKALLSIA0GUlWj8VEIG74AIBAw9AAJL3BqAhihAkRiXe1jpALBItIm/RG+AJAwUCm4MDwcAPVIEAQmAgEQSXgAAAQMLlZaODwxDmyGDDw5VnCEJDhIwGKgM
DgURADs=
EOF;
			break;
		case 'code.gif': $img = <<<EOF
R0lGODlhEAAQANUAAB8fH+bm5ra2toKCgnyRFV5vEszMzP///3Z2djpDDpauJaOjo/f390FBQcXFxZmZmeDg4HKEFKjAMUBKD4ifG+/v7729vZSUlNXV1aysrE1ZD2x+E7HKOI6lII2NjY
OZGHiLFCYmJp+3K4ylFmV3E63GND1HDoSUGVFeEHmMFQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAAHAP8A
LAAAAAAQABAAAAaewIMQkikaiwGh8uBgVJ4BiACzgAgpo+yHAIpsSBcG1fopcSQiReeTKiAY4kVl06l3KNsIyoNADC4YGhMTJoUJhwkBURYDDkpNcE5Pkw4Wj5FQBgGTApcMEAZiGBYYcJ
1CjhUCTwIQFQsBDKdMBxahDHIMFguyjwcBvAwPcB6ls45MGQEPBwuWB8dKGFQHGErRS9kHGY8NId/g3wANFkEAOw==
EOF;
			break;
		case 'css.gif': $img = <<<EOF
R0lGODlhEAAQALMPAMzMzMTExP///52tzvf399/f36W77I2y6O/v72iV4MnX8Nzn+O/x+YCo5oGg1P///yH5BAEAAA8ALAAAAAAQABAAAARm8L0CqrVFakCE/wIRZFtInAhSCMVYnnBquo
xzHIYSg8ByG7eDKaUSAG4Oj6HBGMoAjcPgY2iaYNDGFOSBnbJJj0Px8QKWCQWjke6aH4aEPGFwvx8ABZnLkwD4fAB+HYBdghMXiRgRADs=
EOF;
			break;
		case 'db.gif': $img = <<<EOF
R0lGODlhEAAQALMPAPv7+8LCwtPT09ra2svLy7KyspiYmOLi4vX19fHx8enp6aioqLq6ut7e3v///////yH5BAEAAA8ALAAAAAAQABAAAAR/8MlJKwVKBMYCUU6FBIKCONgRBAhFHE4sow
JBBcAsYwXFIIoEAgBINAKKhQ/YEDg3SOXklzgcFAfC6iCV/LCNQ4PQ4foSisHgMNgwzJNCdSBQBwpvA0XerGsKBQ16cQ0IdHQeCgGDElwlCJBVDAYHFQkBC5kLBgYsFhMYCgAWEQA7
EOF;
		   break;
		case 'dir.gif': $img = <<<EOF
R0lGODlhEAAQAOYAANmLM/bpyem5hfDLJeaxdvbhq9umOv788Pbge+PDYuC/Vfzz0PHScfLUfvPTQ/XgnufPfNqOOPTYW/767/Xek//9+OrLa+7Jou/ThPLkuvv26NutO/rx3vLQONuUPu
XIbvrsq+/Uk/fjhP////vyzPbca/nom/TXU9u0O9ucO97FY+7PdfHOL+/QefXbZPjmlPvvuPbecfzyyPPYjfPUSNuWOvHQa/Tdp/fWhPnpo/fhgOC8V9ytP9yzQPz22Pjkje/OKenAcujQ
g/fmvd6USvrtsu7Oh////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEHAEcALAAAAAAQABAAAAeYgEeCg4SFhoeI
iYQKCY2NChOIKCOUlBofmB8QQhmCPRU/FA8FEwemphU7gjwTFBQNFhYYGC21Gj2CGxohRkEBp6cjG4IGIxXHFT4vIjEuEj4GxBUyMCAmPwglEicOC9FHKQdFOcvNJzQdLCQpgjUTOT865u
gsAyQ1gkQcNzcUMzMNGjCwYWOIB0EXCHgAwLBhwwgCFEmcGAgAOw==
EOF;
			break;
		case 'excel.gif': $img = <<<EOF
R0lGODlhEAAQALMPAPDw8OHk4GuqTzxxKIOuclOOPKLHkr3Guvj4+Pv7+/r6+snJyfz8/MzMzP///////yH5BAEAAA8ALAAAAAAQABAAAAR+8L3Qlr02yN1QYmCIHNq2JACSAkDABOQjzH
TtIsgSCE7v9wVAiLGYEQSEQaGgVAVcRcPBYCAQCoGCikXkEajJgCO4JWKV1SNioMAhzEomOJlwm6mGxlHAVLTfCwU/P2x+CkQDiYqLCY0fCxILQ5MhkA8dlEMIDRIUGJ8LDQERADs=
EOF;
			break;
		case 'exe.gif': $img = <<<EOF
R0lGODlhEAAQALMPAPX19e3t7fDw8GCqe0J2m+jo6FOFklWbiPn5+fz8/P7+/tPZ4MrMzf///4+Sl////yH5BAEAAA8ALAAAAAAQABAAAARe8MlJq73U6c27XmAYGgtpaEyqrqvWvLCiJA
kCAIJL7IQh0zZCztHYGQyHxAog1B0PSYT0dgi4kNDkSjCwErODge0mEAQKLmhYvAqcXY3ZlOwuoInx2th8tnv+HBgYEQA7
EOF;
			break;
		case 'external.gif': $img = 'R0lGODlhDAAMAIABAHOfqv///yH5BAEAAAEALAAAAAAMAAwAAAIXjI+pCu2QnlvhLclahWjurhmhyFHmeRQAOw=='; break;
		case 'folder.gif': $img = <<<EOF
R0lGODlhMAAwALMPANKLFOCvSf/NBf/SGf/YLf/eRenFaP/kWf/nZP/sc//tff/yjfnxmf/+qf/+tf///yH5BAEAAA8ALAAAAAAwADAAAAT/8MlJq7046827/2AojlUAnGiqAqQWBE4szz
TDthdA77th4Bad40AoGo/HwA9I0SUE0Kh0qmQ2HYipdrrqdjO6w3Ys4JlnhlsQS94e3vC4/M2IVdfitn4f8+WGe4FkCw5+eIKIUwqFSxVhXF6Rkip/eQIASj6am5ydnpwmjoCXMGempzMv
V3kAdag0DbGysQwNtQy1MmoPj5evDQ6zwrcMC8XGxjG7vUJmwrPEuLjIC9XVyldZvrDPtLbSyMfWCwrkhA7LWAMDzd223+Hj1uUK9fbn6QjrQtDv4OLj6NkbmCABvisJivCLZgzgPHID7R
WciGAROoQKYzSIJ89cRAUJ/0BOTICAJIKK2CY4KVBAiEOPH0GKLHiyps0EKSXoUMDSZTWBEUOOLGnT5gEEbxDk5LXxjRCIQYXSNFkU6ZyjS3UsOPpU4kyqVa1eLXCgAAJgyzaefDoUrFGx
csiynHu2QboFBYUoIBr26hu5cwOzPBArLYN6eose9QtYcGAjhO02sVVNCMnFY8s6nkugAJLOhSdTtjyn8WaWn5EUCK2Slo0YVk1vTp163WrJrWUBAIbgNGfaRwYQEL5uwO1ltdDCdgz8c/
HnxQmwlvDCnhDNnYt4bg69uwDh0yWkQRGLSPPh3Z9/1yIdd45YC2inVz9g/ZYE4UsYCNYgbF+rcM2hQB0sCuwShAHuJKjgMwVyYMIkEEpixYQUVmjhhRhGAAA7
EOF;
			break;
		case 'html.gif': $img = <<<EOF
R0lGODlhEAAQALMPAGhrmXaCri6c6i6Mzn6tyczm/iBimZ+/3lmWvISp3MTV/bPO9VdYiPn5+pOTk////yH5BAEAAA8ALAAAAAAQABAAAASK8MlEKw1K6pea/01AHBv3KQSxBAtCagkiy4
0A3IEjHchSCAJE41BoKHIP3m8wAAo9K0dBSDAcGs1BoLgAOKiCZtjp6zoaBKBhzWaUvYeDmlioF4gLxncssDIHbgV5XyMGQAJxVgoKgwcEDQgGDGsMKouDDwsECQpxC4ugmBIMpKWmpDoa
DqusrasRADs=
EOF;
			break;
		case 'img.gif': $img = <<<EOF
R0lGODlhEAAQALMPAIGITsnT5Ka304i8Zpq/96jQg4C1pNKfVLbV/6+GP+7z92memmeaQJmv0cLcv////yH5BAEAAA8ALAAAAAAQABAAAARd8MlJqwyi6c1bkIIijqTYSI2CIEbhFgtBKO
eTsu+gxzSqtrogr3YDBgdDH+6InBFViMUx5lSuAFgAYdWzKRgDRuJwSDDO3ZRj7Sgc2I60Ak6P10KlfA3T6X8sgBMRADs=
EOF;
			break;
		case 'js.gif': $img = <<<EOF
R0lGODlhEAAQALMPAO/v74CJO295It/f3+Dj5uns7+/y9cTExObm5oyUTr7DnPf391pmAMzMzP///////yH5BAEAAA8ALAAAAAAQABAAAARo8JFGax0v57ac/85yYFoTLigAIA4ylie6qO
f7mDJqFGCzKQkBgyFIKAoFls8gZDiGHkaAAHD4AkOnNhvw+LLbZxZ1zYq3XeuDGXWKA4WO7wZsFhUd7wbE/8xNfXx/eYEhcxMWiRQEDxEAOw==
EOF;
			break;
		case 'manual.gif': $img = <<<EOF
R0lGODlhEgASALMIAMzMzN3d3aqqqnd3d7u7u4iIiP///+7u7v///wAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAAgALAAAAAASABIAAARgEMlJKw0kay0sIoZxiEd5FIEFmuzZVUTLFo
UqCgAulMWgyqbeD3jywYgB4VEWKA5bzQLhNYlBD5gQ9WNqekEhQ01i9WIDgrB4IqC532mDUYJT22ltSm/A7/NfeR6CCG0RADs=
EOF;
		   break;
		case 'mdb.gif': $img = <<<EOF
R0lGODlhEgASAKIAAISEhIQAhIQAAMbGxgAAAP///9TQyAAAACwAAAAAEgASAAADVFi63P4wNsJEsPgGBQpV2yMMHmEqwgJcXUAqwxcWV5GOS4wuGf0WOhpK0xkNSMEba+E6AmU0QYcHe+
4eIaQsw60GHcew8yvJfRji9PG8MLnf7jIkAQA7
EOF;
			break;
		case 'mp3.gif': $img = <<<EOF
R0lGODlhEAAQAOYAAAAPZNvb0EZnyZqGM02rVsTExHdjMKiuuaW05PD//yBBq7GMjGCflu3q5ouj1zJ/XbmiKH17Z42OgTVHiqFhfcfa/rOorm5lcomWxeyVjI6Rl1xqqmiIbtldXO2yr+
Lj5JCOPXWBm6aTXZaw//f390F0OczMzHA+RrTE9RI0jYWJi4yt40x/7mm2gcu0S97m7pG2nJV8gUFco7e7vTpneJZ3M3GMr6XEuf///1CA2V+2ZEBPc751cNLm//+noJKbrLnX/5ym9evr
59na3PH1/83P1rLB3djb84t/YVx0vGqfcsaNitbO1vTn673K8+Pq7ZChzRcuhLzCwavBsrrT89/f3+bm5u/v7/T2/6uvuevq68zf/52q+7HQ89DT1Orv8f///wAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEHAGAALAAAAAAQABAAAAezgGAfJoSFhVVg
iYkmJCQ4j5AkBYiKjI2NV1daOFaTlTiXmJuNnmAmoE8WMRwwXpA4Jos4Q0sZHjctOlNaVrCLXzw+TVhYTgwEUle+pgsdR10rVD1BD0rKsaYnFEAOKVEbXDQlrtgmOzJbUAATGCMXBjPLJj
8KKC9GCQkINUhF8kJJWFTAgSWAiwEaGpXD8cFGDgEiIIBQMUShLBwNDoSIICFLr0flHL0aSQLbIEMoCX0AEwgAOw==
EOF;
			break;
		case 'php.gif': $img = <<<EOF
R0lGODlhEAAQALMPAF5goaGixubm69/f331+s8TExODg6cjJ36+w0O/v79PU5vf391FTmczMzPr6/P///yH5BAEAAA8ALAAAAAAQABAAAAR58D3TqrVD6rac/16Rbc5iLkkiOIJInmZaLu
5TIADBMEBggI0HYsdQEHcBwaoxPCgQgqdzGEg4GgCGAXAQAAzbAwFgbeyMB0MRres0AoyFIjBEyOEBd4JgPPYJAm4PCQE6RwQIVh5BDw0gjx+MjpCPkpSVEhQXmxUGEQA7
EOF;
			break;
		case 'powerpoint.gif': $img = <<<EOF
R0lGODlhEAAQALMPAPTz8+Xl5eVHNOphR+53WMPDw/KOb/3s6fj4+Pv7+/r6+snJyfz8/MzMzP///////yH5BAEAAA8ALAAAAAAQABAAAAR98L3Qlr02yN1QYmCIFNq2JAACrEDABORjzA
ZRE7irkobj/76BK7ToIWY2hGOwCrgWBIfhgMD1mCwGVKpYDgYOgSK7JXzPYcUYoY0azoKoIIGoa8HVL+GQVtgXYEA/YmoKd18CiYpzCY1aEgshkpMLEh2TkwgNEgEYnhYNAREAOw==
EOF;
			break;
		case 'py.gif': $img = <<<EOF
R0lGODlhEAAQALMPAENtjv/RQqWoq0eArv/spf/sYu3t7bOzs7q6usLCwt7e3svLy/f39+Xk5P7+/v///yH5BAEAAA8ALAAAAAAQABAAAASE8KCJkl0rqccfWg3jjOPiLBuHKKLSGA3pGF
0COu+YDAOA1IqRaDRQAI4dTCwoSyA5GhFj1zseHQGCLebgVY8CRiAQFXkNJEZh/Al1AQZCYT5ms0QIgGO+rhMOIAZoI3MEHRwHLA0vaHQGAR0CCQcHAoAMBH4dgB0KlVyHoR0NCAICC4cR
ADs=
EOF;
			break;
		case 'rar.gif': $img = <<<EOF
R0lGODlhEAAQALMPAP/NBcvLy/X19WCv//93YEu5NS2X/+Hh4UGU5pubm9nZ2TmyMOZYQbCXKnR0dP///yH5BAEAAA8ALAAAAAAQABAAAAR88MnXqp2TadaAB83GHA9DnN3XnASTHKIjz2
KibI6g785mI8DcTAhE/AYDx8GBVDIHRgXCYHAoHFQr1hAtamfXom1BdgTOaAd5MU4UhrOCm61YFO6NA0AAuhfoawt5fAINgTYHCYoWFg6KiiQHCgoBj48BkyQYD5KTCpoTEQA7
EOF;
			break;
		case 'round.gif': $img = <<<EOF
R0lGODlhDQAQAOZhAAB3v2uwKHy9KAB2vgButgBttQBvt/7//tLr+NDr+dztvczju+z12AeDyvH442q96QB9xgCAyXm7IwBhq/P5526yLLfJtPv99gB/yI3FGmqwJ/j7/IXBHqnK4nu8Jk
KIw73c7+XyxeHx+e322svl9Ah1vHGzMYC/GABwtwB4v5fLRtDp9vD3+9Dj8fn6+Hu8Jfj87rfYoOfy3OLv2GmrPrTT6KXST7TXkLfb8O323/7+/8Ha7PX68pnF4iqBuwN7w6DPRebyzW2x
KpvNXgR6wna6IHiw1rveY0Ch2K3WeIvFL4rFL8/moo/HOQBrtO31+q/P5sTd7obCIXq8JVyjLQ2N0/b77bLP6Ljd8giFy1qVSenx5o/HOCSX1u/33PLz8jmIv////wAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAGEALAAAAAANABAAAAeFgGGCggmDhoYI
D4eHK1VdWCKLYThZEBgRDUgkhixEAykAoQM/IIM6PQUoBgUEBE4lT4Y1Ez47LVFQYEaHVx+HHWEhXoMbF0cwhiNATAwOFEEZNlaGCidKXE1LUhwqOYZJRVMvHgICEkOGBzcmAQEVQhoyiz
wLMVQ0M5KCLlpb+oJfLBwKBAA7
EOF;
			break;
		case 'sarrow.gif': $img = <<<EOF
R0lGODlhCAAeALMNAHV1dnNzdHh4ecPDw3l5esLCwsDAwHh4eM3NzXNzc3Z2d3V1de/v7////wAAAAAAACH5BAEAAA0ALAAAAAAIAB4AAAQzsLUhq1TFSgCMXguBWIsQMNZxagmquVosz3
Rt33iu77zFJAcQQEFhBAQLQEZiJHh8I0kEADs=
EOF;
			break;
		case 'sort.gif': $img = <<<EOF
R0lGODlhCwAlAJECAPX19f///////wAAACH5BAEAAAIALAAAAAALACUAAAI8lA2nCLnSoDuR0Wnr0s++yEnXqIDYdZbdyrbuCz8BG9SdbSu5sO8GPpMFY8Si8TfUzXy8YS/ZjAqhukUBADs=
EOF;
			break;
		case 'swf.gif': $img = <<<EOF
R0lGODlhEAAQALMPAPnKy/zn5/l2c/iKi/VlY/I7QPNXWfRITPaAgubm5t/f3+/v78zMzP////f39////yH5BAEAAA8ALAAAAAAQABAAAARr8ElGa1UyM+ea/w6DaVy5LEmTiFrDAQNyuu
HIuACBHPPHTI2AgWAQOE6oxu9xQwgEAdNsyQAYDoAArRSaDJ4FhIfbZRYEhfCYuywWAFkyhyEwFHRrOeNwP2jzbGkGAB+FhVQdhoVlChaOFxEAOw==
EOF;
			break;
		case 'th.gif': $img = <<<EOF
R0lGODlhBgAaAMQAAOeKGP/kjf/ef//ac//Wav/UZP/TYf/TYv/SX//PXf/NWv/LWv/KV//HVv/GU//DT//ATv+/S/+8R/+6Rv+5Q/+3Qv+1P/+yPP+wOf+tN/+uOf+xQP+4Tv+2S/++Wv
/GbCwAAAAABgAaAAAFSiAgjkFpCmg6rCzhvkQhz0ZdH0ieJ3yfKEDggkEkNhxI5GPJhESeT4l0OqFYrRWLdnvpejHgcGY8xmjO5416zem43Z64/EOvj0YhADs=
EOF;
			break;
		case 'txt.gif': $img = <<<EOF
R0lGODlhEAAQALMAAKSkpPf398zMzN3d3cXFxf///+bm5r29vdbW1re3t+/v762trf///wAAAAAAAAAAACH5BAEHAAwALAAAAAAQABAAAARekBlBax0sZxFC+WBAYBrXdYpiFMZYFieqwC
IpwMeRLAsAFoLN75RSAYUFAkG3482CjNuPFoNKKcpcrmOdfmKBbgGBqCgJXOQXnI5+BgOyHNGWen9Wz/0LnVj+FAYMEQA7
EOF;
			break;
		case 'up.gif': $img = <<<EOF
R0lGODlhDAAOAOZIAKPsJ4C5M87itPD16uz7yLjch6XMcG2zDIXIKmuxC7jbh2+2DbDSgX6/Jnq5Is34QLndiOX5r2muCunp6cv3Pu743pHQI7jlHdTvd5HOK5DPIsPpUavuKpLQLOD4Zd
T0f9LumLzqItz2hNb4Xtf2hpLMLInIII7NIbnnGuz6v/D6zPD80LjsKcr0Qdvyl+z7xsDyNb/1OMj3PKzuK+T1wdT4U9T5VNT4XLf0M7z1P9XxduP0yNb5X9fyepPQLfL5547WFdPtp6Dc
O5XeGbjkb4/YE5zlILfah////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAEgALAAAAAAMAA4AAAeFgEiCSBAICBCD
iQU+KykWBYlICh0EPDYRGgqDRxkEIw8PNREnR0hHJS83FDIxODkkJkcNKh4wHAC4My0iDQwOCx9GwkYsCw4MSBMHPUPNQyEHE4MJOkXWRSgJiRIYQN5AFxKJAS5C5kIbAYkGNETuRCAGiQ
IVQfZBOwKJA0g//j9I+CEJBAA7
EOF;
			break;
		case 'viewmode.gif': $img = 'R0lGODlhGwAIAIABAJmZmf///yH5BAEAAAEALAAAAAAbAAgAAAIhRICZwYxn3mu0NhmzVtP6D2IQtHGVKIKqVypnNnarA2MFADs='; break;
		case 'word.gif': $img = <<<EOF
R0lGODlhEAAQALMPAPT09FhvyuLj5piq3nCV1WGC0LzCzuzu9fj4+Pv7+/r6+vv8/snJyfz8/MzMzP///yH5BAEAAA8ALAAAAAAQABAAAASB8D3hmL1WyO1QamCIGNrGJEAKHIDQCORDzD
RR3EeDAIxAKIug8FDIhRiExWywLARWAhdSSTAUmE5Vbkor2AaBXarBvTkLgoACsSMXFk7s4BBIsBHuhldhO9AVa3gMRX6FB2mAgGQBjI2OCZAfDBIMIZaXkw8dl5cIDhIUGKIMDgIRADs=
EOF;
			break;
		case 'xml.gif': $img = <<<EOF
R0lGODlhEAAQAMQfAFe68v/QCNfr/f+qAMHwqbS10uX3/0KxUPT9///DPLCmoKmPadnb+ubo98LC4bXE8//ZXv/mffzvvsrY+rni7Onk08LB1ZzQ0o7I9f71ypukvoTXnfP/0sDP8P////
///yH5BAEAAB8ALAAAAAAQABAAAAWU4Nc5TGmaxqd+jue+r/A0awvDzYTR7A0jjMms90JQKBeDB2EQaIgNI4dwACAmCE+h14gCNtWDwNDxPFtdBABwAFMQDsTT4opiMGAAWfks3DoXGAwG
hHIffj8GTISEfRkcHBkZEhkVEhWYFU8KAZ0QnwkQCQkDpQsfnJ4REBISo6UDp6kBEasSEQkRsKcWC76/wL8KIQA7
EOF;
			break;
		case 'zip.gif': $img = <<<EOF
R0lGODlhEAAQANU6AJKSjI2NjOfp7MTExJ2dlPXuR5WVkvP0Y+fmMezrQWSW5IuRl/X1XOjv+Xui33ym55ubd6mbWO/myOfoOtrGe7GrfcyzO8fFv8LIzWZjT8GoN2+f6Gl2f+/pQ76+uu
3sN9HQyvj3bO7oNPbuP+vrVPf3SoKs6t/k69nZ1bi5tHGk8urrR9jUSMWsP9DQGo6MhMStR6Ojm+bm5s62QO/v797f3vf395mZmf///8zMzP///wAAAAAAAAAAAAAAAAAAACH5BAEAADoA
LAAAAAAQABAAAAaeQJ1OlisajTWhMmfDOZ842yC5jNquNJoAJ5vqJK3K7RYrEzzb6xTGgrzer4BcBs3NDodQqQCTjbMCdHYkLoUdfTEGWVk4dgwJCR98MolXlnYrhS4iMDUEAJZXjhMICC
Odn6E2ORQzrhYaESkxAao5Qhc1CwsAL70nDZdCOQIqDjkAGRwbJk+3OjkNCg41KCAYCg/Ow01Q3qtCNUfjSEEAOw==
EOF;
			break;
		default: $img = <<<EOF
R0lGODlhEAAQALMPAPLy8u/v7+rq6uDg4O3t7efn58PDw/b29vj4+Pr6+vn5+fv7+8nJyczMzPz8/P///yH5BAEAAA8ALAAAAAAQABAAAARq8I3Gqq3Dvf0aWk4oIkbGMQtwrEAgOEWpPY
xzKKxrA+VWK0DWS8TwOUBHxAFAEBQcRZootFC2CFDjNLkEZKVbRwJ3+NbCCyDCPF24EwnEOnqmLuB4dtvN14enUQ0KfyIKDRsTF4oMDQMPEQA7
EOF;
		}

		//让图片缓存一天
		nvHeader('Cache-Control: public');
		nvHeader('Pragma: cache');
		nvHeader('Expires: '.gmdate('D, d M Y H:i:s', $this->time + 86400).' GMT');

		$ns = explode('.', $file);
		switch (end($ns)) {
			case 'png': nvHeader('Content-Type: image/png'); break;
			case 'jpg': nvHeader('Content-Type: image/jpeg'); break;
			case 'gif': nvHeader('Content-Type: image/gif'); break;
		}

		nvHeader('Content-Disposition: inline');
		echo base64_decode($img);
	}

	private function filterDir($dir) {
		return trim(preg_replace('#/{1,}#', '/', $dir), './');
	}

	private function convertFileSize($size) {
		if ($size > 0) {
			$units = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
			return number_format($size/pow(1024, ($i = floor(log($size, 1024)))), 2, '.', '').' '.$units[$i];
		} elseif ($size == 0) {
			return '0 Bytes';
		} else {
			return '&gt;2 GB';
		}
	}

	private function encodeUrl($uri) {
		return join('/', array_map('rawurlencode', explode('/', $uri)));
	}

	private function getDisplayUri($filename) {
		//对非全角字符和URL涉及字符（#%&=?)进行转码
		$uri = preg_replace_callback('/([^\x20-\x22\x24\x27-\x3c\x3e\x40-\x7e]+)/', function($m) { return rawurlencode($m[0]); }, $filename);
		$code = mb_detect_encoding($filename, $this->nameEncodes);
		
		//Windows 下访问 URL 必须以 UTF-8 编码
		//$uri = ($code != 'UTF-8' && PHP_OS == 'WINNT') ? iconv($code, 'UTF-8', $filename) : $filename;
		//$uri = rawurlencode($uri);
		
		//因 iconv() 函数存在某些环境下转码出现进程崩溃的问题，所以这里改用 mb_convert_encoding()
		//文件名必须以 GBK 显示
		if ($code && $code != 'CP936') {
			$filename = mb_convert_encoding($filename, 'CP936', $code);
		}
		
		return array($filename, $uri);
	}

	private function footer() { //底部
		$SoftWare = explode(' ',$_SERVER['SERVER_SOFTWARE']);
		//php_uname().PHP_SAPI
		return '<hr /><address>One Explorer V'.self::VERSION.' & '.PHP_OS.' '.$SoftWare[0].' PHP/'.PHP_VERSION.'<br /><small>&copy; Copyrights <a href="http://www.vgot.net/" target="_blank">VGOT.NET</a> 2008-2015</small></address>';
	}

}
