<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Class block_mhaairs_log
 */
class block_mhaairs_log {
    /**
     * @var string
     */
    protected $filepath = null;

    /**
     * @var string
     */
    protected $dirpath = null;

    /**
     * @var null|bool
     */
    protected $logenabled = null;

    /**
     * @var block_mhaairs_log
     */
    private static $instance = null;

    /**
     * @param null|bool $enabled
     */
    private function __construct($enabled = null) {
        $this->logenabled = $enabled;
        $mdata = get_config('core', 'dataroot');
        $sep = DIRECTORY_SEPARATOR;
        $dir = make_writable_directory("{$mdata}{$sep}mhaairs", false);
        if ($dir !== false) {
            $this->dirpath = $dir;
            $fileprefix = userdate(time(), 'mhaairs_%Y-%m-%d_%H-%M-%S_');
            while (empty($this->filepath)) {
                $name = uniqid($fileprefix, true);
                $fullname = "{$dir}{$sep}{$name}.log";
                if (!file_exists($fullname)) {
                    $this->filepath = $fullname;
                }
            }
        }
    }

    /**
     * DTOR
     */
    public function __destruct() {
        $this->filepath = null;
        $this->logenabled = null;
    }

    /**
     * @return block_mhaairs_log
     */
    public static function instance() {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * @return bool
     */
    public function enabled() {
        if ($this->logenabled === null) {
            return (get_config('core', 'block_mhaairs_gradelog') == 1);
        }
        return $this->logenabled;
    }

    /**
     * @param string $data
     */
    public function log($data) {
        if ($this->enabled() && !empty($this->filepath)) {
            file_put_contents($this->filepath, $data.PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * Delete current log file
     */
    public function delete() {
        if (is_writable($this->filepath)) {
            unlink($this->filepath);
        }
    }

    /**
     * Delete entire log directory
     */
    public function deleteall() {
        if (is_dir($this->dirpath) && is_writable($this->dirpath)) {
            $fileleft = false;
            $it = new RecursiveDirectoryIterator($this->dirpath, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->getFilename() === '.' || $file->getFilename() === '..') {
                    continue;
                }
                $rpath = $file->getRealPath();
                if (is_writable($rpath)) {
                    if ($file->isDir()) {
                        rmdir($rpath);
                    } else {
                        unlink($rpath);
                    }
                } else {
                    $fileleft = true;
                }
            }
            if (!$fileleft) {
                rmdir($this->dirpath);
            }
        }
    }

}
