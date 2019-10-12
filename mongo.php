<?php
    $m = new MongoClient(); // 连接默认主机和端口为：mongodb://localhost:27017
    $db = $m->test; // 获取名称为 "test" 的数据库