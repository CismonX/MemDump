<?php

namespace cron\Tasks;
use Acast\ {
    Console, Config, Server,
    CronService\TaskInterface
};
/**
 * Class MemDump
 * @package cron\Tasks
 */
class MemDump extends TaskInterface {
    public $time;
    public $persistent = true;
    public $params;
    public $name = 'memcached-dump';
    protected $_mem_list;
    /**
     * File handle.
     * @var resource
     */
    protected $_handle;
    /**
     * @var self
     */
    protected static $_singleton;
    /**
     * Constructor.
     */
    protected function __construct() {
        $this->time = time() + Config::get('MEMCACHED_SAVE_DURATION');
    }
    /**
     * {@inheritdoc}
     */
    static function init() : ?self {
        if (self::$_singleton instanceof self)
            return null;
        return self::$_singleton = new self;
    }
    /**
     * {@inheritdoc}
     */
    function execute(int &$when, bool &$persistent, &$param) {
        $this->_mem_list = Server::$memcached->getAllKeys();
        $count = count($this->_mem_list);
        $items = Config::get('MEMCACHED_ITEMS_ONCE');
        $this->openBakFile();
        for ($i = 0; $i < $count; $i += $items)
            $this->save(array_slice($this->_mem_list, $i, $items));
        $this->closeBakFile();
        $this->time += Config::get('MEMCACHED_SAVE_DURATION');
        $when = $this->time;
    }
    /**
     * Write memcached items to file.
     *
     * @param array $keys
     */
    protected function save(array $keys) {
        $data = Server::$memcached->getMulti($keys);
        $this->writeLn(serialize($data));
    }
    /**
     * Open file for write.
     */
    protected function openBakFile() {
        $this->_handle = fopen(Config::get('MEMCACHED_BAK_FILE').$this->time, 'a');
        if ($this->_handle === false) {
            Console::warning('Failed to open memcached backup file.');
            return;
        }
    }
    /**
     * Write a line of compressed data to the file.
     *
     * @param string $data
     */
    protected function writeLn(string $data) {
        $data = zlib_encode($data, ZLIB_ENCODING_DEFLATE);
        $len = pack('L', strlen($data));
        if (fwrite($this->_handle, $len.$data) === false)
            Console::warning('Failed to write to memcached backup file.');
    }
    /**
     * Close file.
     */
    protected function closeBakFile() {
        if (!fclose($this->_handle))
            Console::warning('Failed to close memcached backup file.');
    }
    /**
     * {@inheritdoc}
     */
    function destroy() {
        if (is_resource($this->_handle))
            fclose($this->_handle);
    }
}