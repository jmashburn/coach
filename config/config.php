<?php

return array(
        'base_dir' => ROOT_DIR,
        'view_paths' => glob(ROOT_DIR . '/{module/*/views/,module/*/views/*,views/*,views/}/', GLOB_BRACE),
        'config_dir' => glob(ROOT_DIR . '/{module/*/config/,config/,tests/config/}', GLOB_BRACE),
        'db_dir' =>  ROOT_DIR . '/var/db',
        'log_dir' =>  ROOT_DIR . '/var/log',
        'install_dir' => ROOT_DIR . '/var/install/sql',
);