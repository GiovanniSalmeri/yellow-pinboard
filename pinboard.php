<?php
// Pinboard extension
// Copyright (c) 2019 Giovanni Salmeri
// This file may be used and distributed under the terms of the public license.

class YellowPinboard {
    const VERSION = "0.8.4";
    const TYPE = "feature";
    public $yellow;         //access to API
    
    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        $this->yellow->system->setDefault("pinboardDir", "media/pinboard/");
        $this->yellow->system->setDefault("pinboardStyle", "plain");
        $path = $this->yellow->system->get("pinboardDir");
        if (!empty($path) && !is_dir($path)) @mkdir($path, 0777, true);
    }

    // Handle page content of shortcut
    public function onParseContentShortcut($page, $name, $text, $type) {
        $output = null;
        if ($name=="pinboard" && ($type=="block" || $type=="inline")) {
            list($noticeList, $timeSpan, $max, $tags) = $this->yellow->toolbox->getTextArgs($text);
            if ($timeSpan != "past") $timeSpan = "current";
            $tags = preg_split("/[\s,]+/", $tags, 0, PREG_SPLIT_NO_EMPTY);
            $noticeListName = $this->yellow->system->get("pinboardDir").$noticeList;

            // Read and sort notices
            $notices = $this->parseNotices($noticeListName);
            function compareNotice($a, $b) {
                if ($timeSpan == "past") {
                    return strcmp($a[1], $b[1]);
                } else {
                    if ($a[2] == "pinned" && $b[2] != "pinned") return -1;
                    elseif ($a[2] != "pinned" && $b[2] == "pinned") return 1;
                    else return strcmp($b[0], $a[0]);
                }
            }
            usort($notices, "compareNotice");

            $output .= "<ul class=\"pinboard $timeSpan\">\n";
            $noticesShown = 0;
            $ISORegEx = "/^\d\d\d\d-\d\d-\d\d$/";
            foreach ($notices as $notice) {
                // Syntax check
                if (!preg_match($ISORegEx, $notice[0]) || !preg_match($ISORegEx, $notice[1]) || !$notice[3]) {
                    $output .= "<li>Error in notice {$notice[0]}--{$notice[1]}</li>\n";
                    continue;
                }

                $noticeStartTime = strtotime($notice[0]);
                $noticeEndTime = strtotime($notice[1]) + 86400; // end date is inclusive
                $noticeTags = preg_split("/[\s,]+/", $notice[4], 0, PREG_SPLIT_NO_EMPTY);
                if (($noticeStartTime <= time()) &&  (($timeSpan == "current" && $noticeEndTime > time()) || ($timeSpan == "past" && $noticeEndTime <= time())) && (!$tags || array_intersect($noticeTags, $tags))) {

                    // Human readable notice date
                    $noticeDate = date($this->yellow->text->get("DateFormatMedium"), $noticeStartTime);

                    // Generate HTML
                    $output .= "<li". ($notice[2] ? " class=\"". $notice[2] . "\"" : "").">";
                    $output .= "<span class=\"desc\">".$this->toHTML($notice[3]). "</span>\n";
                    $output .= "<span class=\"date\">".$this->yellow->text->getHtml("pinboardPublished").": ".$noticeDate."</span>";
                    $output .= "</li>\n";
                    $noticesShown += 1;
                }
                if ($max && $noticesShown >= $max) break; 
            }
            if ($noticesShown == 0) {
                $output .= "<li>".$this->yellow->text->getHtml("pinboardNoNotice")."</li>";
            }
            $output .= "</ul>\n";
        }
        return $output;
    }

    function parseNotices($fileName) {
        $notices = [];
        if ($fileHandle = @fopen($fileName, "r")) {
            $type = $this->yellow->toolbox->getFileType($fileName);
            if ($type == "psv") {
                while (($data = fgetcsv($fileHandle, 0, "|", chr(0))) !== false) {
                    if ($data) $notices[] = array_map("trim", $data);
                }
            } elseif ($type == "csv") {
                while (($data = fgetcsv($fileHandle)) !== false) {
                    if ($data) $notices[] = array_map("trim", $data);
                }
            } elseif ($type == "tsv") {
                while (($data = fgetcsv($fileHandle, 0, "\t", chr(0))) !== false) {
                    if ($data) $notices[] = array_map("trim", $data);
                }
            } elseif ($type == "yaml") {
                $FIELD = [
                    "start" => 0,
                    "end" => 1, 
                    "class" => 2,
                    "content" => 3,
                    "tags" => 4,
                ];
                $currRec = -1;
                while (($line = fgets($fileHandle)) !== false) {
                    $line = rtrim($line);
                    if ($line == "---") { 
                        $currRec += 1;
                    } elseif ($line[0] == "#") {
                        continue;
                    } elseif ($currRec >= 0) {
                        preg_match("/^(.*?):\s+(.*?)\s*$/", $line, $matches);
                        if ($matches && isset($FIELD[$matches[1]])) {
                            $notices[$currRec][$FIELD[$matches[1]]] = $matches[2];
                        }
                    }
                }
            }
            fclose($fileHandle);
        }
        return $notices;
    }

    function toHTML($text) {
        $text = htmlspecialchars($text);
        $text = preg_replace_callback('/\\\[\\\n]/', function($m) { return $m[0] == "\\\\" ? "\\" : "<br />\n"; }, $text);
        $text = preg_replace("/\*\*(.+?)\*\*/", "<b>$1</b>", $text);
        $text = preg_replace("/\*(.+?)\*/", "<i>$1</i>", $text);
        $text = preg_replace("/(?<!\()(https?:\/\/[^ )]+)(?!\))/", "<a href=\"$1\">$1</a>", $text);
        $text = preg_replace("/\[(.*?)\]\((https?:\/\/[^ )]+)\)/", "<a href=\"$2\">$1</a>", $text);
        $text = preg_replace("/(\S+@\S+\.[a-z]+)/", "<a href=\"mailto:$1\">$1</a>", $text);
        return $text;
    }

    // Handle page extra data
    public function onParsePageExtra($page, $name) {
        $output = null;
        if ($name=="header") {
            $extensionLocation = $this->yellow->system->get("serverBase").$this->yellow->system->get("extensionLocation");
            $style = $this->yellow->system->get("pinboardStyle");
            $output .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$extensionLocation}pinboard-{$style}.css\" />\n";
        }
        return $output;
    }
}
