<?php
return [
  "version" => "9.1.8",
  'useCache' => False,
  'useCacheInDeveloperMode' => False,
  'isDeveloperMode' => True,
  'logger' => [
    'level' => 'DEBUG',
  ],
  "timeZone" => "America/Denver",
  "database" => [
    "host" => "127.0.0.1",
    "port" => 3306,
    "charset" => "utf8mb4",
    "dbname" => "ext-more-date-filters",
    "user" => "root",
    "password" => "",
  ],
  'dateFormat' => 'YYYY-MM-DD',
  'timeFormat' => 'HH:mm',
];