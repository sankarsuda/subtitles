<?php namespace Done\Subtitles;

class SccConverter implements ConverterContract
{

    /**
     * Converts file's content (.srt) to library's "internal format" (array)
     *
     * @param string $file_content      Content of file that will be converted
     * @return array                    Internal format
     */
    public function fileContentToInternalFormat($file_content)
    {
        $internal_format = []; // array - where file content will be stored

        $blocks = explode("\n\n", trim($file_content)); // each block contains: start and end times + text
        foreach ($blocks as $block) {
            preg_match('/(?<start>.*) --> (?<end>.*)\n(?<text>(\n*.*)*)/m', $block, $matches);

            // if block doesn't contain text (invalid srt file given)
            if (empty($matches)) {
                continue;
            }

            $internal_format[] = [
                'start' => static::srtTimeToInternal($matches['start']),
                'end' => static::srtTimeToInternal($matches['end']),
                'lines' => explode("\n", $matches['text']),
            ];
        }

        return $internal_format;
    }

    /**
     * Convert library's "internal format" (array) to file's content
     *
     * @param array $internal_format    Internal format
     * @return string                   Converted file content
     */
    public function internalFormatToFileContent(array $internal_format)
    {
        $content = 'Scenarist_SCC V1.0'."\r\n\n";

        foreach ($internal_format as $block) {
            $lines = implode("\r\n", $block['lines']);

            $content .= static::internalTimeToSrt($block['start']);
            $content .= "\t94ae 94ae 9420 9420 ";
            $content .= static::convert($lines);
            $content .= " 942c 942c 8080 8080 942f 942f\r\n\n";
            $content .= static::internalTimeToSrt($block['end']);
            $content .= "\t942c 942c\r\n\n";
        }

        $content = trim($content);

        return $content;
    }

    // ------------------------------ private --------------------------------------------------------------------------

    /**
     * Convert .srt file format to internal time format (float in seconds)
     * Example: 00:02:17,440 -> 137.44
     *
     * @param $srt_time
     *
     * @return float
     */
    protected static function srtTimeToInternal($srt_time)
    {
        $parts = explode(',', $srt_time);

        $only_seconds = strtotime("1970-01-01 {$parts[0]} UTC");
        $milliseconds = (float)('0.' . $parts[1]);

        $time = $only_seconds + $milliseconds;

        return $time;
    }

    /**
     * Convert internal time format (float in seconds) to .srt time format
     * Example: 137.44 -> 00:02:17,440
     *
     * @param float $internal_time
     *
     * @return string
     */
    protected static function internalTimeToSrt($time)
    {
        $parts = explode('.', $time); // 1.23
        $whole = $parts[0]; // 1
        $decimal = isset($parts[1]) ? substr((substr($parts[1], 0, 3) / 40) * 100, 0, 2) : 0; // 23

        $srt_time = gmdate("H:i:s", floor($whole)) . ':' . str_pad($decimal, 2, '0', STR_PAD_RIGHT);

        return $srt_time;
    }

    protected static function convert($text)
    {
        $string = "";
        $lines = str_split($text, 32);

        foreach ($lines as $i => $line) {
            if ($i == 0) {
                $string .="1340 1340 ";
            } elseif ($i == 1) {
                $string .="13e0 13e0 ";
            } elseif ($i == 2) {
                $string .="9440 9440 ";
            } elseif ($i == 3) {
                $string .="94e0 94e0 ";
            }

            $string .= self::encodeChars($line). ' ';
        }

        return trim($string);
    }

    protected static function encodeChars($line)
    {
        $chars = str_split($line);

        $characters = array_flip(static::getChars());
        
        $string = "";
        foreach ($chars as $i => $char) {
            $string .= isset($characters[$char]) ? $characters[$char] : "7f";
        }
        
        if ($i % 2 == 0) {
            $string .= "80";
        }

        $string = implode(' ', str_split($string, 4));

        return $string;
    }

    protected static function getChars()
    {
        return [
            "20"=> " ",
            "a1"=> "!",
            "a2" => "\"",
            "23" => "#",
            "a4" => "$",
            "25" => "%",
            "26" => "&",
            "a7" => "'",
            "a8" => "(",
            "29" => ")",
            "2a" => "á",
            "ab" => "+",
            "2c" => ",",
            "ad" => "-",
            "ae" => ".",
            "2f" => "/",
            "b0" => "0",
            "31" => "1",
            "32" => "2",
            "b3" => "3",
            "34" => "4",
            "b5" => "5",
            "b6" => "6",
            "37" => "7",
            "38" => "8",
            "b9" => "9",
            "ba" => ":",
            "3b" => ";",
            "bc" => "<",
            "3d" => "=",
            "3e" => ">",
            "bf" => "?",
            "40" => "@",
            "c1" => "A",
            "c2" => "B",
            "43" => "C",
            "c4" => "D",
            "45" => "E",
            "46" => "F",
            "c7" => "G",
            "c8" => "H",
            "49" => "I",
            "4a" => "J",
            "cb" => "K",
            "4c" => "L",
            "cd" => "M",
            "ce" => "N",
            "4f" => "O",
            "d0" => "P",
            "51" => "Q",
            "52" => "R",
            "d3" => "S",
            "54" => "T",
            "d5" => "U",
            "d6" => "V",
            "57" => "W",
            "58" => "X",
            "d9" => "Y",
            "da" => "Z",
            "5b" => "[",
            "dc" => "é",
            "5d" => "]",
            "5e" => "í",
            "df" => "ó",
            "e0" => "ú",
            "61" => "a",
            "62" => "b",
            "e3" => "c",
            "64" => "d",
            "e5" => "e",
            "e6" => "f",
            "67" => "g",
            "68" => "h",
            "e9" => "i",
            "ea" => "j",
            "6b" => "k",
            "ec" => "l",
            "6d" => "m",
            "6e" => "n",
            "ef" => "o",
            "70" => "p",
            "f1" => "q",
            "f2" => "r",
            "73" => "s",
            "f4" => "t",
            "75" => "u",
            "76" => "v",
            "f7" => "w",
            "f8" => "x",
            "79" => "y",
            "7a" => "z",
            "fb" => "ç",
            "7c" => "÷",
            "fd" => "Ñ",
            "fe" => "ñ",
            "7f" => "",
            "80" => "",
            "91b0" => "®",
            "9131" => "°",
            "9132" => "½",
            "91b3" => "¿",
            "91b4" => "™",
            "91b5" => "¢",
            "91b6" => "£",
            "9137" => "♪",
            "9138" => "à",
            "91b9" => "",
            "91ba" => "è",
            "913b" => "â",
            "91bc" => "ê",
            "913d" => "î",
            "913e" => "ô",
            "91bf" => "û",
            "9220" => "Á",
            "92a1" => "É",
            "92a2" => "Ó",
            "9223" => "Ú",
            "92a4" => "Ü",
            "9225" => "ü",
            "9226" => "‘",
            "92a7" => "¡",
            "92a8" => "*",
            "9229" => "’",
            "922a" => "—",
            "92ab" => "©",
            "922c" => "℠",
            "92ad" => "•",
            "92ae" => "“",
            "922f" => "”",
            "92b0" => "À",
            "9231" => "Â",
            "9232" => "Ç",
            "92b3" => "È",
            "9234" => "Ê",
            "92b5" => "Ë",
            "92b6" => "ë",
            "9237" => "Î",
            "9238" => "Ï",
            "92b9" => "ï",
            "92ba" => "Ô",
            "923b" => "Ù",
            "92bc" => "ù",
            "923d" => "Û",
            "923e" => "«",
            "92bf" => "»",
            "1320" => "Ã",
            "13a1" => "ã",
            "13a2" => "Í",
            "1323" => "Ì",
            "13a4" => "ì",
            "1325" => "Ò",
            "1326" => "ò",
            "13a7" => "Õ",
            "13a8" => "õ",
            "1329" => "{",
            "132a" => "}",
            "13ab" => "\\",
            "132c" => "^",
            "13ad" => "_",
            "13ae" => "¦",
            "132f" => "~",
            "13b0" => "Ä",
            "1331" => "ä",
            "1332" => "Ö",
            "13b3" => "ö",
            "1334" => "ß",
            "13b5" => "¥",
            "13b6" => "¤",
            "1337" => "|",
            "1338" => "Å",
            "13b9" => "å",
            "13ba" => "Ø",
            "133b" => "ø",
            "13bc" => "┌",
            "133d" => "┐",
            "133e" => "└",
            "13bf" => "┘"
        ];
    }
}
