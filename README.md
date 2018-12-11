# Zero downtime ElasticSearch reindexing using Laravel

## Setup

### Database migration
Please create the `world` database and populate data from the following url.

https://dev.mysql.com/doc/world-setup/en/world-setup-installation.html


### Index creation

```
php artisan elastic:create-index
```

The above command will create an index like `world_1544094130` with a timestamp appended. After that, it will create two aliases world_write and world_read. Both aliases will point at the index `world_1544094130`.

## Zero downtime reindexing

```
php artisan elastic:reindex
```

