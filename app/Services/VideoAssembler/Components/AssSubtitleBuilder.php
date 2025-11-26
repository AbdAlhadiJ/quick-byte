<?php

namespace App\Services\VideoAssembler\Components;

use App\Helpers\FileHelpers;
use Illuminate\Support\Facades\Storage;

class AssSubtitleBuilder
{

    public function buildWordLevelSubtitles(array $wordAlignment,string $color = '&H00FFFFFF', string $animation = 'scale', float $offsetSeconds = 0): string
    {

        $formatTime = function(float $seconds) use ($offsetSeconds): string {
            $seconds += $offsetSeconds;
            $h = floor($seconds / 3600);
            $m = floor(($seconds % 3600) / 60);
            $s = floor($seconds % 60);
            $cs = floor(($seconds - floor($seconds)) * 100);
            return sprintf('%d:%02d:%02d.%02d', $h, $m, $s, $cs);
        };

        $ass = "[Script Info]\n"
            . "ScriptType: v4.00+\n"
            . "PlayResX: 1080\nPlayResY: 1920\nTimer: 100.0000\n\n";

        $ass .= "[V4+ Styles]\n"
            . "Format: Name,Fontname,Fontsize,PrimaryColour,OutlineColour,BackColour,Bold,Italic,"
            . "BorderStyle,Outline,Shadow,Alignment,MarginL,MarginR,MarginV,Encoding\n"
            . "Style: Base,a Atomic Md,100,{$color},&H00000000&,&H00000000&,1,0,1,4,0,5,0,0,0,1\n\n";

        $ass .= "[Events]\n"
            . "Format: Layer,Start,End,Style,Name,MarginL,MarginR,MarginV,Effect,Text\n";

        foreach ($wordAlignment as $wd) {
            $start = $formatTime((float) $wd['start_time']);
            $end   = $formatTime((float) $wd['end_time']);
            $text  = $this->escapeText((string) $wd['word']);
            $text  = strtoupper($text);
            $tags  = $this->buildOverrideTags($color, $animation);

            $ass .= "Dialogue: 0,{$start},{$end},Base,,0,0,0,,{$tags}{$text}\n";
        }

        $file = FileHelpers::createTempFilePath('captions_', 'ass', 'subtitles',true);

        file_put_contents($file, $ass);

        return $file;
    }

    protected function escapeText(string $text): string
    {
        $clean = strip_tags($text);

        $dashVariants = [
            "\xE2\x80\x94",
            "\xE2\x80\x93",
            "\xE2\x88\x92",
            "\xE2\x80\x90",
            "\xE2\x80\x91",
        ];
        $clean = str_replace($dashVariants, '-', $clean);
        $clean = preg_replace('/[^\p{L}\p{N}\- ]+/u', '', $clean);
        $clean = preg_replace('/[ \-]{2,}/', '-', $clean);

        return trim($clean, ' -');
    }
    protected function buildOverrideTags(string $color, string $animation): string
    {
        $tags = [
            "\\an5",
            "\\bord5",
            "\\shad0",
            "\\alpha&H00&",
            "\\t(1000,2000,\\c{$color},\\c&H00FFFF&)"
        ];


        switch ($animation) {
            case 'slide-up':
                $tags[] = "\\move(360,1400,360,1150,0,400)";
                break;

            case 'pop-in':
                $tags[] = "\\fad(100,0)";
                break;

            case 'scale':
                // Scale from 80% to 100% over ~300ms
                $tags[] = "\\fscx80\\fscy80\\t(\\fscx100\\fscy100)";
                break;

            case 'fade':
                // Slight fade in from 0 to visible
                $tags[] = "\\fade(0,255,255,0,0,200,400)";
                break;
        }

        return '{' . implode('', $tags) . '}';
    }

}
