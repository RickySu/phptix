# Symfony Framework PHPTix 售票系統

## 初始安裝

* `git clone https://github.com/RickySu/phptix.git`
* `composer install`
* `app/console server:run` 或 `app/console s:r` 應該就可以把 server run 起來
* 資料庫設定訊息：

```
Creating the "app/config/parameters.yml" file
Some parameters are missing. Please provide them.
database_driver (pdo_mysql):
```

* 如果有打錯，可以去改 `app/config/parameters.yml`
* 如果要看有哪些項目，可以參考 `app/config/parameters.yml.dist`

### 測試資料庫連線是否正常：

* `app/console s:r` 打開 Server 並用瀏覽器瀏覽
* `app/console propel:database:create` 會根據 config 自動建立 database。(註：不是建立 table 唷，是建 database。)
* 點下排 profiler 列，之後開 `propel`，如果沒有報錯，應該就是連上了。

##
