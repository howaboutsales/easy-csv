<?php

namespace EasyCSV;

class Reader extends AbstractBase
{
    private $headersInFirstRow = true;
    private $headers;
    private $line;
    private $init;

    public function __construct($path, $mode = 'r+', $headersInFirstRow = true)
    {
        parent::__construct($path, $mode);
        $this->headersInFirstRow = $headersInFirstRow;
        $this->line = 0;
    }

    public function getHeaders()
    {
        $this->init();
        return $this->headers;
    }

    public function getRow()
    {
        $this->init();
        if ($this->handle->eof()) {
            return false;
        }

        if (($row = $this->handle->fgetcsv($this->delimiter, $this->enclosure)) !== false && $row != null) {
            $this->line++;
            return $this->headers ? array_combine($this->headers, $row) : $row;
        } else {
            return false;
        }
    }

    public function getAll()
    {
        $data = array();
        while ($row = $this->getRow()) {
            $data[] = $row;
        }
        return $data;
    }

    public function getLineNumber()
    {
        return $this->line;
    }

    protected function init()
    {
        if (true === $this->init) {
            return;
        }
        $this->init    = true;
        $this->headers = $this->headersInFirstRow === true ? $this->getRow() : false;
    }

    protected function incrementLine()
    {
        $this->line++;
    }

    /**
     * Auto detect the line endings of a file (Unix, DOS/Win, Mac)
     *
     * Quoting the php manual
     * http://www.php.net/manual/en/filesystem.configuration.php#ini.auto-detect-line-endings)
     *
     * "When turned on, PHP will examine the data read by fgets(),and file()
     * to see if it is using Unix, MS-Dos or Macintosh line-ending conventions.
     * This enables PHP to interoperate with Macintosh systems, but defaults to
     * Off, as there is a very small performance penalty when detecting the EOL
     * conventions for the first line, and also because people using
     * carriage-returns as item separators under Unix systems would experience
     * non-backwards-compatible behaviour."
     *
     * @param bool $enable
     * @return bool
     */
    public static function setAutoDetectLineEndings($enable = true)
    {
        return ini_set('auto_detect_line_endings', $enable);
    }

    /**
     * Detect CSV delimiter
     *
     * We look for these delimiters by default:
     * ',', "\t", '|', ':', ';', '^'
     * @param string file
     * @param int testLines how many lines to read
     * @param array $delimiters extra list of delimiters to test for
     * @return string $character detected CSV delimiter
     */
    public static function detectDelimiter($file, $testLines = 10, $delimiters = array())
    {
        $delimiters = array_merge(array(',', "\t", '|', ':', ';', '^'), (array)$delimiters);
        $counts = array();
        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = 0;
            $line = 0;
            if (($handle = fopen($file, "r")) !== FALSE) {
                $headerSize = count(fgetcsv($handle, 1000, $delimiter));
                while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                    $lineSize = count($data);
                    if (is_array($data) && ($lineSize > 1) && ($headerSize == $lineSize)) {
                        $counts[$delimiter]++;
                    }
                    $line++;
                    if($line > $testLines){
                        break;
                    }
                }
                fclose($handle);
            }
        }
        if (empty($counts)){
            return false;
        }
        $character = current(array_keys($counts, max($counts)));
        return $character;
    }
}
