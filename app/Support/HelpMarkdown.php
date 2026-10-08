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
        $sectionOpen = false;
        $headingIndex = 0;
        $toc = [];
        foreach ($lines as $line) {
            if (preg_match('/^## (.+)$/', $line, $heading) === 1) {
                $toc[] = '<li><a href="#help-section-'.(count($toc) + 1).'">'.$this->inline($heading[1]).'</a></li>';
            }
        }
        if (count($toc) > 1) {
            $html .= '<nav class="help-toc" aria-label="この記事の手順"><h2>この記事で確認できること</h2><ol>'.implode('', $toc).'</ol></nav>';
        }
        foreach ($lines as $line) {
            if (trim($line) === '') {
                $html .= $this->closeList($list);

                continue;
            }
            if (preg_match('~^!\[([^\]]*)\]\((/images/help/[a-zA-Z0-9_-]+\.png)\)$~', $line, $matches) === 1) {
                $html .= $this->closeList($list).'<figure class="help-shot"><a class="help-shot__frame" href="'.$matches[2].'" target="_blank" rel="noopener" aria-label="画面画像を拡大する"><img src="'.$matches[2].'" alt="'.$matches[1].'" loading="lazy"><span class="help-shot__zoom">画像を拡大 ↗</span></a><figcaption>'.$matches[1].'</figcaption></figure>';

                continue;
            }
            if (preg_match('/^### (.+)$/', $line, $matches) === 1) {
                $html .= $this->closeList($list).'<h3>'.$this->inline($matches[1]).'</h3>';

                continue;
            }
            if (preg_match('/^## (.+)$/', $line, $matches) === 1) {
                $html .= $this->closeList($list).($sectionOpen ? '</section>' : '');
                $sectionOpen = true;
                $headingIndex++;
                $class = $matches[1] === '注意' ? 'help-guide-section help-guide-section--caution' : 'help-guide-section';
                $html .= '<section class="'.$class.'" id="help-section-'.$headingIndex.'"><h2>'.$this->inline($matches[1]).'</h2>';

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

        return $html.$this->closeList($list).($sectionOpen ? '</section>' : '');
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
