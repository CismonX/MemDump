# MemDump

A simple memcached ~~persistence~~(backup) implementation based on `Acast\CronService`.

## 1. Configuration

Configuration items shall be set with `Acast\Config`.

**MEMCACHED_ITEMS_ONCE** : The number of items to be fetched each time.

**MEMCACHED_SAVE_DURATION** : The duration between task executions.

**MEMCACHED_BAK_FILE** : The name of file which dumped data will be saved to.

* Timestamp will be appended to the end of the filename.

## 2. Notice

* The integrity of dumped data is not guaranteed. Content of the remaining data can be changed when the task is in process.
* The function for restoring dumped data is not yet implemented.