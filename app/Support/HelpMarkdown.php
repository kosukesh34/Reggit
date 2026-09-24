<?php

namespace App\Support;

/** ヘルプ本文用の最小Markdown。HTMLは出さない。 */
final class HelpMarkdown
{
    public function toHtml(string $markdown): string
    {
        $escaped = htmlspecialchars($markdown, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $lines = preg_split("/\r\n|\n|\r/", $escaped);
        if ($lines === false) {
            return '';
        }

        $html = '';
        $list = false;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                $html .= $this->closeList($list);

                continue;
            }
            if (preg_match('/^### (.+)$/', $line, $matches) === 1) {
                $html .= $this->closeList($list).'<h3>'.$this->inline($matches[1]).'</h3>';

                continue;
            }
            if (preg_match('/^## (.+)$/', $line, $matches) === 1) {
                $html .= $this->closeList($list).'<h2>'.$this->inline($matches[1]).'</h2>';

                continue;
            }
            if (preg_match('/^# (.+)$/', $line, $matches) === 1) {
                $html .= $this->closeList($list).'<h2>'.$this->inline($matches[1]).'</h2>';

                continue;
            }
            if (preg_match('/^- (.+)$/', $line, $matches) === 1) {
                if ($list === false) {
                    $html .= '<ul>';
                    $list = true;
                }
                $html .= '<li>'.$this->inline($matches[1]).'</li>';

                continue;
            }
            $html .= $this->closeList($list).'<p>'.$this->inline($line).'</p>';
        }

        return $html.$this->closeList($list);
    }

    private function closeList(bool &$list): string
    {
        if ($list === false) {
            return '';
        }
        $list = false;

        return '</ul>';
    }

    private function inline(string $text): string
    {
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text) ?? $text;

        return preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text) ?? $text;
    }
}
